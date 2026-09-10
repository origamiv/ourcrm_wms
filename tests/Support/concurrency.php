<?php

declare(strict_types=1);
// Отдельная локальная БД: реальные таблицы проекта никогда не используются.
function connection(): PDO
{
    return new PDO('pgsql:host=/var/run/postgresql;dbname=wms_concurrency_test', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
if (($argv[1] ?? '') === 'writer') {
    $db = connection();
    $db->exec("SET application_name = 'wms_sync_writer'; SET statement_timeout = '8s'; UPDATE public.users SET name = 'Второй commit' WHERE id = 2");
    exit;
}
$db = connection();
try {
    $db->beginTransaction();
    $db->exec(file_get_contents(__DIR__.'/schema.sql'));
    $db->exec(file_get_contents(__DIR__.'/../../database/sql/user_sync.sql'));
    $db->exec("INSERT INTO public.users(id,name,tenant_id) VALUES (1,'Первый','a'),(2,'Второй','a')");
    $db->commit();
    $reader = connection();
    $before = $reader->query('SELECT revision FROM wms.sync_state')->fetchColumn();
    $db->beginTransaction();
    $db->exec("UPDATE public.users SET name='Первый commit' WHERE id=1");
    $process = proc_open([PHP_BINARY, __FILE__, 'writer'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $deadline = microtime(true) + 5;
    do {
        $waiting = $reader->query("SELECT count(*) FROM pg_stat_activity WHERE application_name='wms_sync_writer' AND wait_event_type='Lock'")->fetchColumn();
        if ($waiting) {
            break;
        }
        usleep(50000);
    } while (microtime(true) < $deadline);
    if (! $waiting || $reader->query('SELECT revision FROM wms.sync_state')->fetchColumn() !== $before) {
        throw new RuntimeException('Нарушена видимость незавершённой транзакции');
    }
    $db->commit();
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($stderr);
    }
    $ids = $reader->query('SELECT user_id FROM wms.user_changes WHERE revision > '.(int) $before.' ORDER BY revision')->fetchAll(PDO::FETCH_COLUMN);
    if (array_map('intval', $ids) !== [1, 2]) {
        throw new RuntimeException('Нарушен порядок commit');
    }
    echo "Параллельные записи: ревизии следуют порядку commit, незавершённые изменения не видны.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('DROP SCHEMA IF EXISTS wms CASCADE; DROP SCHEMA IF EXISTS main CASCADE; DROP TABLE IF EXISTS public.users; DROP TABLE IF EXISTS public.personal_access_tokens;');
}

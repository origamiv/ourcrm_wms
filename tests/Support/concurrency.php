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
if (($argv[1] ?? '') === 'visibility_writer') {
    $db = connection();
    $db->exec("SET application_name = 'wms_visibility_writer'; SET statement_timeout = '8s'");
    $query = $db->prepare('INSERT INTO main.tenant_entity (entity_type, entity_id, tenant_id) VALUES (?, 4, ?)');
    $query->execute(['App\\Models\\User', 'a']);
    exit;
}
$db = connection();
try {
    $db->beginTransaction();
    $db->exec(file_get_contents(__DIR__.'/schema.sql'));
    $db->exec(file_get_contents(__DIR__.'/../../database/sql/user_sync.sql'));
    foreach (['entity_sync_upgrade.sql', 'entity_sync_functions.sql', 'entity_sync_users.sql', 'tenant_sync_upgrade.sql', 'tenant_sync_append.sql'] as $sql) {
        $db->exec(file_get_contents(__DIR__.'/../../database/sql/'.$sql));
    }
    $db->exec('CREATE TABLE main.tenant_entity (id bigserial PRIMARY KEY, entity_type text, entity_id bigint, tenant_id text)');
    $db->exec('ALTER TABLE public.sync_state ADD COLUMN shared_initialized boolean NOT NULL DEFAULT false');
    $db->exec(file_get_contents(__DIR__.'/../../database/sql/tenant_entity_visibility.sql'));
    $db->exec("INSERT INTO public.users(id,name,tenant_id) VALUES (1,'Первый','a'),(2,'Второй','a'),(3,'Другой тенант','b')");
    $db->commit();
    $reader = connection();
    $before = $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'a'")->fetchColumn();
    $db->beginTransaction();
    $db->exec("UPDATE public.users SET name='Первый commit' WHERE id=1");
    // Запись другого тенанта завершается, пока транзакция a удерживает свой счётчик.
    $reader->exec("SET statement_timeout = '1s'; UPDATE public.users SET name='Независимая запись' WHERE id=3");
    if ((int) $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'b'")->fetchColumn() !== 2) {
        throw new RuntimeException('Счётчик другого тенанта не обновился');
    }

    $process = proc_open([PHP_BINARY, __FILE__, 'writer'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $deadline = microtime(true) + 5;
    do {
        $waiting = $reader->query("SELECT count(*) FROM pg_stat_activity WHERE application_name='wms_sync_writer' AND wait_event_type='Lock'")->fetchColumn();
        if ($waiting) {
            break;
        }
        usleep(50000);
    } while (microtime(true) < $deadline);
    if (! $waiting || $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'a'")->fetchColumn() !== $before) {
        throw new RuntimeException('Нарушена видимость незавершённой транзакции');
    }
    $db->commit();
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($stderr);
    }
    $ids = $reader->query("SELECT entity_id FROM public.entity_changes WHERE tenant_id = 'a' AND revision > ".(int) $before.' ORDER BY revision')->fetchAll(PDO::FETCH_COLUMN);
    if (array_map('intval', $ids) !== [1, 2]) {
        throw new RuntimeException('Нарушен порядок commit');
    }
    $db->exec("INSERT INTO public.users(id,name,tenant_id) VALUES (4, 'Общий', NULL)");
    $bBefore = (int) $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'b'")->fetchColumn();
    $db->beginTransaction();
    $db->exec("UPDATE public.users SET name = 'Изменение общего' WHERE id = 4");
    $process = proc_open([PHP_BINARY, __FILE__, 'visibility_writer'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $deadline = microtime(true) + 5;
    do {
        $waiting = $reader->query("SELECT count(*) FROM pg_stat_activity WHERE application_name = 'wms_visibility_writer' AND wait_event_type = 'Lock'")->fetchColumn();
        if ($waiting) {
            break;
        }
        usleep(50000);
    } while (microtime(true) < $deadline);
    if (! $waiting) {
        throw new RuntimeException('Изменение доступа не сериализовано с общей записью');
    }
    $db->commit();
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($stderr);
    }
    $events = $reader->query("SELECT operation FROM public.entity_changes WHERE tenant_id = 'b' AND revision > ".$bBefore.' ORDER BY revision')->fetchAll(PDO::FETCH_COLUMN);
    if ($events !== ['upsert', 'remove']) {
        throw new RuntimeException('После отзыва доступа осталась общая запись');
    }
    $reader->exec("SELECT wms.initialize_tenant_shares('new_tenant')");
    if ((int) $reader->query("SELECT count(*) FROM public.entity_changes WHERE tenant_id = 'new_tenant' AND entity_id = '4' AND operation = 'upsert'")->fetchColumn() !== 0) {
        throw new RuntimeException('Новый тенант получил запрещённую запись');
    }
    echo "Общие записи и отзыв доступа сериализованы; новый тенант получает только разрешённые записи.\n";
    echo "Параллельные записи: внутри тенанта соблюдён порядок commit; другой тенант пишет без ожидания.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('DROP SCHEMA IF EXISTS goods CASCADE; DROP SCHEMA IF EXISTS clients CASCADE; DROP SCHEMA IF EXISTS wms CASCADE; DROP SCHEMA IF EXISTS main CASCADE; DROP TABLE IF EXISTS public.sync_state; DROP TABLE IF EXISTS public.entity_changes; DROP TABLE IF EXISTS public.users; DROP TABLE IF EXISTS public.personal_access_tokens;');
}

<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\EntityChangeRecorder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Отдельная локальная БД: реальные таблицы проекта никогда не используются.
function connection(): PDO
{
    return new PDO('pgsql:host=/var/run/postgresql;dbname=wms_concurrency_test', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function application(): Illuminate\Foundation\Application
{
    $environment = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'pgsql',
        'DB_HOST' => '/var/run/postgresql',
        'DB_PORT' => '5432',
        'DB_DATABASE' => 'wms_concurrency_test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_URL' => '',
        'DB_SCHEMA' => 'public',
    ];
    foreach ($environment as $key => $value) {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
    require_once __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.url' => null,
        'database.connections.pgsql.host' => '/var/run/postgresql',
        'database.connections.pgsql.port' => '5432',
        'database.connections.pgsql.database' => 'wms_concurrency_test',
        'database.connections.pgsql.username' => 'root',
        'database.connections.pgsql.password' => '',
        'database.connections.pgsql.search_path' => 'public',
    ]);
    DB::purge('pgsql');

    return $app;
}

if (($argv[1] ?? '') === 'writer') {
    application();
    DB::statement("SET application_name = 'wms_sync_writer'");
    DB::statement("SET statement_timeout = '8s'");
    DB::transaction(function (): void {
        DB::table('public.users')->where('id', 2)->update(['name' => 'Второй commit']);
        app(EntityChangeRecorder::class)->publishCurrent(User::class, 2);
    });
    exit;
}
if (($argv[1] ?? '') === 'visibility_writer') {
    application();
    DB::statement("SET application_name = 'wms_visibility_writer'");
    DB::statement("SET statement_timeout = '8s'");
    DB::transaction(function (): void {
        DB::table('main.tenant_entity')->insert(['entity_type' => User::class, 'entity_id' => 4, 'tenant_id' => 'a']);
        app(EntityChangeRecorder::class)->refreshVisibility(User::class, 4);
    });
    exit;
}
if (($argv[1] ?? '') === 'other_tenant_writer') {
    application();
    if (DB::scalar('SELECT current_database()') !== 'wms_concurrency_test') {
        throw new RuntimeException('Дочерний процесс подключён не к тестовой БД');
    }
    DB::statement("SET statement_timeout = '8s'");
    DB::transaction(function (): void {
        if (DB::table('public.users')->where('id', 3)->update(['name' => 'Независимая запись']) !== 1) {
            throw new RuntimeException('Дочерний процесс не обновил пользователя другого тенанта');
        }
        app(EntityChangeRecorder::class)->publishCurrent(User::class, 3);
    });
    fwrite(STDOUT, (string) DB::table('public.sync_state')->where('tenant_id', 'b')->value('revision'));
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
    application();
    (require __DIR__.'/../../database/migrations/2026_09_23_000002_move_entity_changes_to_laravel.php')->up();
    $changes = app(EntityChangeRecorder::class);
    DB::statement('INSERT INTO public.sync_state (tenant_id) VALUES (NULL) ON CONFLICT (tenant_id) DO NOTHING');
    $reader = connection();
    $before = $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'a'")->fetchColumn();
    $otherTenantBefore = (int) $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'b'")->fetchColumn();
    DB::beginTransaction();
    DB::table('public.users')->where('id', 1)->update(['name' => 'Первый commit']);
    $changes->publishCurrent(User::class, 1);
    // Запись другого тенанта завершается, пока транзакция a удерживает свой счётчик.
    $otherTenant = proc_open([PHP_BINARY, __FILE__, 'other_tenant_writer'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $otherTenantPipes);
    $otherTenantOutput = stream_get_contents($otherTenantPipes[1]);
    $otherTenantError = stream_get_contents($otherTenantPipes[2]);
    fclose($otherTenantPipes[1]);
    fclose($otherTenantPipes[2]);
    if (proc_close($otherTenant) !== 0) {
        throw new RuntimeException($otherTenantError);
    }
    $otherTenantAfter = (int) $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'b'")->fetchColumn();
    if ($otherTenantAfter !== $otherTenantBefore + 1) {
        throw new RuntimeException("Счётчик другого тенанта изменился с {$otherTenantBefore} на {$otherTenantAfter}; дочерний процесс увидел {$otherTenantOutput}");
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
    DB::commit();
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
    DB::transaction(function () use ($changes): void {
        DB::table('public.users')->insert(['id' => 4, 'name' => 'Общий', 'tenant_id' => null]);
        $changes->publishCurrent(User::class, 4);
    });
    $bBefore = (int) $reader->query("SELECT revision FROM public.sync_state WHERE tenant_id = 'b'")->fetchColumn();
    DB::beginTransaction();
    DB::table('public.users')->where('id', 4)->update(['name' => 'Изменение общего']);
    $changes->publishCurrent(User::class, 4);
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
    DB::commit();
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
    $changes->initializeTenantShares('new_tenant');
    if ((int) $reader->query("SELECT count(*) FROM public.entity_changes WHERE tenant_id = 'new_tenant' AND entity_id = '4' AND operation = 'upsert'")->fetchColumn() !== 0) {
        throw new RuntimeException('Новый тенант получил запрещённую запись');
    }
    echo "Общие записи и отзыв доступа сериализованы; новый тенант получает только разрешённые записи.\n";
    echo "Параллельные записи: внутри тенанта соблюдён порядок commit; другой тенант пишет без ожидания.\n";
} finally {
    if (class_exists(DB::class) && DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->exec('DROP SCHEMA IF EXISTS goods CASCADE; DROP SCHEMA IF EXISTS clients CASCADE; DROP SCHEMA IF EXISTS wms CASCADE; DROP SCHEMA IF EXISTS main CASCADE; DROP TABLE IF EXISTS public.sync_state; DROP TABLE IF EXISTS public.entity_changes; DROP TABLE IF EXISTS public.users; DROP TABLE IF EXISTS public.personal_access_tokens; DROP TABLE IF EXISTS public.tenants;');
}

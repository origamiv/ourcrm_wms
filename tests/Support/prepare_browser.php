<?php

declare(strict_types=1);
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
use Illuminate\Support\Facades\DB;

config(['database.default' => 'pgsql', 'database.connections.pgsql.url' => null, 'database.connections.pgsql.host' => '/var/run/postgresql', 'database.connections.pgsql.port' => 5432, 'database.connections.pgsql.database' => 'wms_browser_test', 'database.connections.pgsql.username' => 'root', 'database.connections.pgsql.password' => '', 'database.connections.pgsql.search_path' => 'public']);
DB::purge('pgsql');
if (DB::selectOne('select current_database() as name')->name !== 'wms_browser_test') {
    throw new RuntimeException('Неверная тестовая БД');
}
DB::transaction(function () {
    DB::unprepared('DROP SCHEMA IF EXISTS wms CASCADE; DROP SCHEMA IF EXISTS main CASCADE; DROP TABLE IF EXISTS public.sync_state; DROP TABLE IF EXISTS public.entity_changes; DROP TABLE IF EXISTS public.users; DROP TABLE IF EXISTS public.personal_access_tokens;');
    DB::unprepared(file_get_contents(__DIR__.'/schema.sql'));
    DB::unprepared(file_get_contents(database_path('sql/user_sync.sql')));
    (require database_path('migrations/2026_09_10_000003_create_shared_entity_changes.php'))->up();
    (require database_path('migrations/2026_09_10_000004_move_sync_state_to_public.php'))->up();
    foreach ([['Анна', 'Смирнова', 'admin@example.test'], ['Михаил', 'Иванов', 'operator@example.test'], ['Елена', 'Петрова', 'elena@example.test'], ['Сотрудник', 'Без почты', null]] as $i => [$name, $last, $email]) {
        $u = new User;
        $u->forceFill(['name' => $name, 'last_name' => $last, 'email' => $email, 'password' => 'Test_password_123', 'status' => 1, 'tenant_id' => 'test_org'])->save();
        if ($i === 0) {
            $r = DB::table('main.roles')->insertGetId(['slug' => 'admin', 'status' => 1]);
            DB::table('main.role_user')->insert(['role_id' => $r, 'user_id' => $u->id, 'tenant_id' => 'test_org', 'status' => 1]);
        }
    }
});
echo "Тестовые пользователи подготовлены в локальной wms_browser_test.\n";
DB::transaction(function () {
    DB::table('main.roles')->insert(['name' => 'Кладовщик', 'slug' => 'warehouse_operator', 'description' => 'Операции склада', 'status' => 1, 'tenant_id' => 'test_org']);
    DB::table('main.permissions')->insert(['name' => 'Просмотр остатков', 'slug' => 'inventory_view', 'resource' => 'inventory', 'system' => true, 'status' => 1, 'tenant_id' => 'test_org']);
    (require database_path('migrations/2026_09_10_000005_sync_access_catalogs.php'))->up();
    (require database_path('migrations/2026_09_10_000006_sync_permission_roles.php'))->up();
});

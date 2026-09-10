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
    DB::unprepared('DROP SCHEMA IF EXISTS goods CASCADE; DROP SCHEMA IF EXISTS clients CASCADE; DROP SCHEMA IF EXISTS wms CASCADE; DROP SCHEMA IF EXISTS main CASCADE; DROP TABLE IF EXISTS public.sync_state; DROP TABLE IF EXISTS public.entity_changes; DROP TABLE IF EXISTS public.users; DROP TABLE IF EXISTS public.personal_access_tokens;');
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

DB::transaction(function () {
    $company = DB::table('main.companies')->insertGetId(['name' => 'ООО Тестовый склад', 'shortname' => 'Склад', 'inn' => '1234567890', 'status' => 1, 'tenant_id' => 'test_org']);
    DB::table('main.company_contacts')->insert(['name' => 'Иван Тестовый', 'shortname' => 'Иван', 'company_id' => $company, 'val' => 'ivan@example.test', 'status' => 1, 'tenant_id' => 'test_org']);
    $otherCompany = DB::table('main.companies')->insertGetId(['name' => 'ООО Другая компания', 'shortname' => 'Другая', 'status' => 1, 'tenant_id' => 'test_org']);
    DB::table('main.company_contacts')->insert(['name' => 'Другой контакт', 'shortname' => 'Другой', 'company_id' => $otherCompany, 'status' => 1, 'tenant_id' => 'test_org']);
    (require database_path('migrations/2026_09_10_000007_sync_companies.php'))->up();
});

DB::transaction(function () {
    DB::table('clients.clients')->insert(['name' => 'Тестовый клиент', 'shortname' => 'Клиент', 'status' => 1, 'tenant_id' => 'test_org']);
    (require database_path('migrations/2026_09_10_000008_sync_clients.php'))->up();
});

DB::transaction(function () {
    DB::table('main.modules')->insert(['name' => 'Склад', 'shortname' => 'WMS', 'status' => 1]);
    DB::table('main.features')->insert(['name' => 'Приёмка', 'shortname' => 'Приёмка', 'status' => 1, 'module_id' => 1]);
    DB::table('main.icons')->insert(['name' => 'Значок склада', 'category' => 'Склад', 'status' => 1, 'tenant_id' => 'test_org']);
    DB::table('main.files')->insert(['name' => 'Документ', 'category' => 'Склад', 'status' => 1, 'tenant_id' => 'test_org']);
    (require database_path('migrations/2026_09_10_000009_sync_references.php'))->up();
});

DB::transaction(function () {
    DB::table('clients.companies')->insert(['name' => 'ООО Клиентское юрлицо', 'shortname' => 'Клиентское', 'status' => 1, 'tenant_id' => 'test_org', 'client_id' => 1]);
    DB::table('clients.individuals')->insert(['name' => 'Тестовое физлицо', 'firstname' => 'Тестовое', 'lastname' => 'Физлицо', 'status' => 1, 'tenant_id' => 'test_org', 'client_id' => 1]);
    (require database_path('migrations/2026_09_10_000010_sync_client_parties.php'))->up();
});

DB::transaction(function () {
    $other = DB::table('clients.clients')->insertGetId(['name' => 'Другой клиент', 'status' => 1, 'tenant_id' => 'test_org']);
    DB::table('clients.companies')->insert(['name' => 'Юрлицо другого клиента', 'shortname' => 'Другое', 'client_id' => $other, 'status' => 1, 'tenant_id' => 'test_org']);
    DB::table('clients.individuals')->insert(['name' => 'Физлицо другого клиента', 'client_id' => $other, 'status' => 1, 'tenant_id' => 'test_org']);
});

DB::transaction(function () {
    (require database_path('migrations/2026_09_10_000011_create_client_documents.php'))->up();
    (require database_path('migrations/2026_09_10_000012_sync_client_documents.php'))->up();
    DB::table('clients.documents')->insert(['name' => 'Счёт тестового клиента', 'client_id' => 1, 'doc_type_id' => 1, 'status' => 0, 'doc_date' => '2026-09-10', 'tenant_id' => 'test_org']);
});

DB::transaction(function () {
    (require database_path('migrations/2026_09_10_000013_add_document_parties.php'))->up();
    (require database_path('migrations/2026_09_10_000015_add_document_amount.php'))->up();
    (require database_path('migrations/2026_09_10_000014_add_document_type_print_settings.php'))->up();
    DB::table('main.companies')->where('id', 1)->update(['src' => json_encode(['is_own' => true])]);
});

DB::transaction(function () {
    (require database_path('migrations/2026_09_10_000016_relate_good_cards_to_goods.php'))->up();
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->up();
    DB::table('goods.type_goods')->insert(['name' => 'Товар', 'tenant_id' => 'test_org']);
    DB::table('goods.unit_goods')->insert(['name' => 'Штука', 'shortname' => 'шт.', 'tenant_id' => 'test_org']);
});

DB::transaction(function () {
    (require database_path('migrations/2026_09_10_000018_tenant_entity_visibility.php'))->up();
});

DB::transaction(function () {
    (require database_path('migrations/2026_09_10_000019_calculate_goods_hierarchy.php'))->up();
});

DB::transaction(function () {
    (require database_path('migrations/2026_09_10_000020_manual_good_categories.php'))->up();
    (require database_path('migrations/2026_09_10_000021_create_kind_kiz.php'))->up();
    (require database_path('migrations/2026_09_10_000022_create_kizes.php'))->up();
    (require database_path('migrations/2026_09_10_000023_create_fulfillment_catalogs.php'))->up();
    (require database_path('migrations/2026_09_10_000024_seed_delivery_services.php'))->up();
    (require database_path('migrations/2026_09_10_000025_seed_marketplaces_and_delivery_links.php'))->up();
});

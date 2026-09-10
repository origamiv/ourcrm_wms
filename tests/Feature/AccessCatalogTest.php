<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
});
afterEach(function () {
    DB::rollBack();
});

it('shows tenant catalogs to admins and captures direct SQL changes', function () {
    $admin = $this->makeUser([], true);
    $role = DB::table('main.roles')->insertGetId(['name' => 'Склад', 'slug' => 'warehouse', 'tenant_id' => 'tenant_a']);
    DB::table('main.roles')->insert(['name' => 'Чужая роль', 'tenant_id' => 'tenant_b']);
    $permission = DB::table('main.permissions')->insertGetId(['name' => 'Просмотр', 'slug' => 'view', 'resource' => 'inventory', 'tenant_id' => 'tenant_a']);
    DB::table('main.permissions')->insert(['name' => 'Чужое право', 'slug' => 'foreign', 'resource' => 'inventory', 'tenant_id' => 'tenant_b']);
    $migration = require database_path('migrations/2026_09_10_000005_sync_access_catalogs.php');
    $migration->up();
    $this->loginUser($admin);
    $this->get('/roles')->assertOk();
    $this->get('/permissions')->assertOk();
    $roles = $this->getJson('/web/sync/roles')->assertOk()->assertJsonCount(1, 'changes')->assertJsonPath('changes.0.data.name', 'Склад')->json();
    $permissions = $this->getJson('/web/sync/permissions')->assertOk()->assertJsonCount(1, 'changes')->assertJsonPath('changes.0.data.resource', 'inventory')->json();
    DB::table('main.roles')->where('id', $role)->update(['name' => 'Обновлённая роль', 'deleted_at' => now()]);
    $this->getJson('/web/sync/roles?cursor='.urlencode($roles['cursor']))->assertOk()->assertJsonPath('changes.0.data.name', 'Обновлённая роль')->assertJsonCount(1, 'changes');
    DB::table('main.permissions')->where('id', $permission)->delete();
    $this->getJson('/web/sync/permissions?cursor='.urlencode($permissions['cursor']))->assertOk()->assertJsonPath('changes.0.operation', 'remove');
    $migration->down();
    expect(DB::table('main.roles')->where('id', $role)->exists())->toBeTrue();
    expect(DB::table('public.entity_changes')->where('entity', App\Models\Role::class)->exists())->toBeFalse();
    $this->loginUser($this->makeUser());
    $this->get('/roles')->assertForbidden();
    $this->get('/permissions')->assertForbidden();
    $this->getJson('/web/sync/roles')->assertForbidden();
    $this->getJson('/web/sync/permissions')->assertForbidden();
});

it('creates and edits catalogs with tenant isolation and version conflicts', function () {
    $admin = $this->makeUser([], true);
    $foreign = DB::table('main.roles')->insertGetId(['name' => 'Foreign', 'slug' => 'foreign', 'tenant_id' => 'tenant_b']);
    (require database_path('migrations/2026_09_10_000005_sync_access_catalogs.php'))->up();
    $this->loginUser($admin);
    foreach (['roles' => ['description' => 'Описание'], 'permissions' => ['resource' => 'inventory']] as $catalog => $extra) {
        $input = ['name' => 'Новая запись', 'slug' => 'new_'.$catalog, 'status' => 1, ...$extra];
        $this->postJson('/web/'.$catalog, [...$input, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
        $row = $this->postJson('/web/'.$catalog, $input)->assertCreated()->assertJsonPath('data.tenant_id', 'tenant_a')->assertJsonPath('data.system', false)->json('data');
        $this->postJson('/web/'.$catalog, $input)->assertUnprocessable();
        $updated = $this->putJson('/web/'.$catalog.'/'.$row['id'], [...$input, 'name' => 'Изменено', 'version' => $row['version']])->assertOk()->assertJsonPath('data.name', 'Изменено')->json('data');
        $this->putJson('/web/'.$catalog.'/'.$row['id'], [...$input, 'version' => $row['version']])->assertConflict()->assertJsonPath('current.version', $updated['version']);
    }
    $this->putJson('/web/roles/'.$foreign, ['name' => 'Denied', 'slug' => 'foreign', 'status' => 1, 'version' => '1'])->assertNotFound();
    $this->postJson('/web/roles', ['name' => 'Admin', 'slug' => 'admin', 'status' => 1])->assertUnprocessable();
    $this->loginUser($this->makeUser());
    $this->postJson('/web/roles', ['name' => 'Denied', 'slug' => 'denied', 'status' => 1])->assertForbidden();
});

it('protects admin and system identifiers and exposes writes through Bearer API', function () {
    $admin = $this->makeUser([], true);
    $role = DB::table('main.roles')->insertGetId(['name' => 'Администратор', 'slug' => 'admin', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $permission = DB::table('main.permissions')->insertGetId(['name' => 'Системное', 'slug' => 'system_view', 'resource' => 'system', 'system' => true, 'status' => 1, 'tenant_id' => 'tenant_a']);
    (require database_path('migrations/2026_09_10_000005_sync_access_catalogs.php'))->up();
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token);
    $sync = app(App\Services\EntitySyncService::class);
    $version = $sync->current(App\Models\Role::class, 'tenant_a', $role)['version'];
    $this->putJson('/api/roles/'.$role, ['name' => 'Admin', 'slug' => 'admin', 'status' => 0, 'version' => $version])->assertUnprocessable();
    $this->putJson('/api/roles/'.$role, ['name' => 'Название', 'slug' => 'admin', 'status' => 1, 'version' => $version])->assertOk();
    $version = $sync->current(App\Models\Permission::class, 'tenant_a', $permission)['version'];
    $this->putJson('/api/permissions/'.$permission, ['name' => 'Системное', 'slug' => 'system_view', 'resource' => 'changed', 'status' => 1, 'version' => $version])->assertUnprocessable();
    $this->postJson('/api/permissions', ['name' => 'Новое', 'slug' => 'new_api', 'resource' => 'inventory', 'status' => 1])->assertCreated();
});

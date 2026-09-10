<?php

declare(strict_types=1);

use App\Models\PermissionRole;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000005_sync_access_catalogs.php'))->up();
    (require database_path('migrations/2026_09_10_000006_sync_permission_roles.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('grants revokes restores and rejects stale versions in the role matrix', function () {
    $admin = $this->makeUser([], true);
    $role = DB::table('main.roles')->insertGetId(['name' => 'Склад', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $permission = DB::table('main.permissions')->insertGetId(['name' => 'Просмотр', 'slug' => 'view', 'resource' => 'inventory', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $this->loginUser($admin);
    $this->get('/roles_rights')->assertOk();
    $url = "/web/roles/$role/permissions/$permission";
    $created = $this->putJson($url, ['enabled' => true, 'version' => '0'])->assertOk()->assertJsonPath('enabled', true)->json();
    expect(DB::table('main.permission_role')->where('role_id', $role)->count())->toBe(1);
    $this->putJson($url, ['enabled' => false, 'version' => '0'])->assertConflict();
    $revoked = $this->putJson($url, ['enabled' => false, 'version' => $created['version']])->assertOk()->assertJsonPath('enabled', false)->json();
    expect(DB::table('main.permission_role')->where('role_id', $role)->value('deleted_at'))->not->toBeNull();
    $this->putJson($url, ['enabled' => true, 'version' => $revoked['version']])->assertOk();
    expect(DB::table('main.permission_role')->where('role_id', $role)->count())->toBe(1);
    $cursor = $this->getJson('/web/sync/permission_roles')->assertOk()->json('cursor');
    DB::table('main.permission_role')->where('role_id', $role)->delete();
    $this->getJson('/web/sync/permission_roles?cursor='.urlencode($cursor))->assertOk()->assertJsonPath('changes.0.operation', 'remove');
});

it('enforces role and permission tenant ownership through both transports', function () {
    $admin = $this->makeUser([], true);
    $role = DB::table('main.roles')->insertGetId(['name' => 'Склад', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $permission = DB::table('main.permissions')->insertGetId(['name' => 'Чужое', 'slug' => 'view', 'resource' => 'inventory', 'status' => 1, 'tenant_id' => 'tenant_b']);
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->json('token');
    $this->withToken($token)->putJson("/api/roles/$role/permissions/$permission", ['enabled' => true, 'version' => '0'])->assertNotFound();
    DB::table('main.permissions')->where('id', $permission)->update(['tenant_id' => 'tenant_a']);
    $this->putJson("/api/roles/$role/permissions/$permission", ['enabled' => true, 'version' => '0', 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $this->putJson("/api/roles/$role/permissions/$permission", ['enabled' => true, 'version' => '0'])->assertOk();
    $this->loginUser($this->makeUser());
    $this->get('/roles_rights')->assertForbidden();
    $this->putJson("/web/roles/$role/permissions/$permission", ['enabled' => false, 'version' => '0'])->assertForbidden();
});

it('revokes duplicate assignments without leaving an active copy', function () {
    $admin = $this->makeUser([], true);
    $role = DB::table('main.roles')->insertGetId(['name' => 'Склад', 'status' => 1, 'tenant_id' => 'tenant_a']);
    $permission = DB::table('main.permissions')->insertGetId(['name' => 'Просмотр', 'slug' => 'view', 'resource' => 'inventory', 'status' => 1, 'tenant_id' => 'tenant_a']);
    DB::table('main.permission_role')->insert(['role_id' => $role, 'permission_id' => $permission, 'tenant_id' => 'tenant_a']);
    $id = DB::table('main.permission_role')->insertGetId(['role_id' => $role, 'permission_id' => $permission, 'tenant_id' => 'tenant_a']);
    $version = app(EntitySyncService::class)->current(PermissionRole::class, 'tenant_a', $id)['version'];
    $this->loginUser($admin);
    $this->putJson("/web/roles/$role/permissions/$permission", ['enabled' => false, 'version' => $version])->assertOk()->assertJsonCount(2, 'data');
    expect(DB::table('main.permission_role')->where('status', 1)->whereNull('deleted_at')->count())->toBe(0);
});

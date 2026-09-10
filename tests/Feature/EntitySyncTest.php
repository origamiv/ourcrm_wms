<?php

declare(strict_types=1);

use App\Services\EntitySyncService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
});
afterEach(function () {
    DB::rollBack();
});

function registerTestItems(): void
{
    config(['sync.entities.items' => ['entity' => 'Tests\\Item', 'table' => 'public.sync_test_items', 'fields' => ['id', 'name', 'tenant_id'], 'authorize' => [App\Services\AccessService::class, 'isAdmin']]]);
    DB::unprepared(<<<'SQL'
CREATE TABLE public.sync_test_items (id text PRIMARY KEY, tenant_id text, name text, secret text);
CREATE TRIGGER test_items_change AFTER INSERT OR UPDATE OR DELETE ON public.sync_test_items
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('Tests\Item', '["name", "tenant_id"]');
CREATE TRIGGER test_items_truncate BEFORE TRUNCATE ON public.sync_test_items
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('Tests\Item');
SQL);
}

it('isolates polymorphic IDs, UUIDs, tenants and cursors through the shared endpoints', function () {
    $admin = $this->makeUser([], true);
    registerTestItems();
    DB::table('public.sync_test_items')->insert([
        ['id' => (string) $admin->id, 'tenant_id' => 'tenant_a', 'name' => 'Item', 'secret' => 'excluded'],
        ['id' => '00000000-0000-4000-8000-000000000001', 'tenant_id' => 'tenant_a', 'name' => 'UUID', 'secret' => 'excluded'],
        ['id' => 'foreign', 'tenant_id' => 'tenant_b', 'name' => 'Foreign', 'secret' => 'excluded'],
    ]);
    $this->loginUser($admin);
    $items = $this->getJson('/web/sync/items')->assertOk()->assertJsonPath('entity_type', 'items')->assertJsonCount(2, 'changes')->assertJsonMissing(['name' => 'Foreign'])->json();
    expect($items['changes'][0]['data'])->not->toHaveKey('secret');
    $users = $this->getJson('/web/sync/users')->assertOk()->assertJsonCount(1, 'changes')->json();
    expect($users['changes'][0]['data']['name'])->toBe($admin->name);
    $this->getJson('/web/sync/users?cursor='.urlencode($items['cursor']))->assertForbidden();
    $this->getJson('/web/sync/unknown')->assertNotFound();
    $this->getJson('/web/users/sync')->assertOk()->assertJsonMissingPath('entity_type');

    $this->loginUser($this->makeUser());
    $this->getJson('/web/sync/items')->assertForbidden();
});

it('tracks UUID changes, tenant moves and truncate using the generic trigger', function () {
    registerTestItems();
    $id = '00000000-0000-4000-8000-000000000001';
    DB::table('public.sync_test_items')->insert(['id' => $id, 'tenant_id' => 'tenant_a', 'name' => 'Original', 'secret' => 'excluded']);
    $sync = app(EntitySyncService::class);
    $snapshot = $sync->page('Tests\\Item', 'tenant_a', 'viewer', null, null);
    DB::table('public.sync_test_items')->where('id', $id)->update(['id' => 'new_uuid', 'tenant_id' => 'tenant_b']);
    $delta = $sync->page('Tests\\Item', 'tenant_a', 'viewer', $snapshot['cursor'], null);
    expect($delta['changes'][0]['operation'])->toBe('remove');
    expect($delta['changes'][0]['id'])->toBe($id);
    $other = $sync->page('Tests\\Item', 'tenant_b', 'viewer', null, null);
    DB::statement('TRUNCATE public.sync_test_items');
    $delta = $sync->page('Tests\\Item', 'tenant_b', 'viewer', $other['cursor'], null);
    expect($delta['changes'][0]['operation'])->toBe('remove');
    expect($delta['changes'][0]['id'])->toBe('new_uuid');
});

it('preserves events across migration and refuses rollback with other entities', function () {
    $user = $this->makeUser();
    $migration = require database_path('migrations/2026_09_10_000003_create_shared_entity_changes.php');
    $before = DB::table('public.entity_changes')->orderBy('revision')->get()->toJson();
    $migration->down();
    expect(DB::table('wms.user_changes')->count())->toBe(1);
    $migration->up();
    expect(DB::table('public.entity_changes')->orderBy('revision')->get()->toJson())->toBe($before);
    expect(DB::selectOne("select to_regclass('wms.user_changes') as relation")->relation)->toBeNull();
    registerTestItems();
    DB::table('public.sync_test_items')->insert(['id' => '1', 'tenant_id' => 'tenant_a']);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Откат запрещён');
    expect(DB::table('public.entity_changes')->count())->toBe(2);
});

it('resets old cache cursors with 409 instead of revoking access', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $cursor = Crypt::encryptString(json_encode(['tenant' => $admin->tenant_id, 'user' => (string) $admin->id, 'format' => 1, 'revision' => '0']));
    $this->getJson('/web/sync/users?cursor='.urlencode($cursor))->assertConflict();
});

it('authorizes shared API sync using a Bearer token', function () {
    $admin = $this->makeUser([], true);
    $this->getJson('/api/sync/users')->assertUnauthorized();
    $token = $this->postJson('/api/auth/token', ['email' => $admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    auth()->forgetGuards();
    $this->withToken($token)->getJson('/api/sync/users')->assertOk()->assertJsonPath('entity_type', 'users');
});

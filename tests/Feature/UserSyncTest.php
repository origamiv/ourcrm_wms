<?php

declare(strict_types=1);
use App\Services\UserSyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
});
afterEach(function () {
    DB::rollBack();
});
it('keeps snapshot pagination stable while SQL changes accumulate', function () {
    $admin = $this->makeUser([], true);
    $second = $this->makeUser(['name' => 'До изменения']);
    $third = $this->makeUser();
    $sync = app(UserSyncService::class);
    $page = $sync->page('tenant_a', (string) $admin->id, null, null);
    expect($page['mode'])->toBe('snapshot');
    expect($page['continuation'])->not->toBeNull();
    DB::table('public.users')->where('id', $third->id)->update(['name' => 'После снимка']);
    $last = $sync->page('tenant_a', (string) $admin->id, null, $page['continuation']);
    expect($last['changes'][0]['data']['name'])->toBe('Тест');
    $delta = $sync->page('tenant_a', (string) $admin->id, $last['cursor'], null);
    expect($delta['changes'])->toHaveCount(1);
    expect($delta['changes'][0]['data']['name'])->toBe('После снимка');
    expect($sync->page('tenant_a', (string) $admin->id, $delta['cursor'], null)['changes'])->toBe([]);
});
it('captures hard deletion soft deletion restoration and tenant moves without timestamps', function () {
    $u = $this->makeUser();
    $sync = app(UserSyncService::class);
    $initial = $sync->page('tenant_a', (string) $u->id, null, null);
    DB::table('public.users')->where('id', $u->id)->update(['deleted_at' => now()]);
    $delta = $sync->page('tenant_a', (string) $u->id, $initial['cursor'], null);
    expect($delta['changes'][0]['data']['deleted_at'])->not->toBeNull();
    DB::table('public.users')->where('id', $u->id)->update(['deleted_at' => null]);
    DB::table('public.users')->where('id', $u->id)->update(['tenant_id' => 'tenant_b']);
    $page = $sync->page('tenant_a', (string) $u->id, $delta['cursor'], null);
    expect($page['changes'][1]['operation'])->toBe('remove');
    $other = $sync->page('tenant_b', '99', null, null);
    expect($other['changes'][0]['data']['tenant_id'])->toBe('tenant_b');
    DB::table('public.users')->where('id', $u->id)->delete();
    expect($sync->page('tenant_b', '99', $other['cursor'], null)['changes'][0]['operation'])->toBe('remove');
});
it('rolls back revisions together with changes and never journals secrets', function () {
    $u = $this->makeUser();
    $sync = app(UserSyncService::class);
    $revision = $sync->revision('tenant_a');
    DB::beginTransaction();
    DB::table('public.users')->where('id', $u->id)->update(['name' => 'Откат']);
    DB::rollBack();
    expect($sync->revision('tenant_a'))->toBe($revision);
    $data = json_decode(DB::table('public.entity_changes')->first()->data, true);
    expect($data)->not->toHaveKeys(['password', 'remember_token']);
});
it('binds cursors to the authenticated user and tenant', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $cursor = $this->getJson('/web/users/sync')->assertOk()->json('cursor');
    $other = $this->makeUser(['tenant_id' => 'tenant_b'], true);
    $this->loginUser($other);
    $this->getJson('/web/users/sync?cursor='.urlencode($cursor))->assertForbidden();
});

it('captures primary key changes and truncation as removals', function () {
    $user = $this->makeUser();
    $sync = app(UserSyncService::class);
    $initial = $sync->page('tenant_a', 'viewer', null, null);
    DB::table('public.users')->where('id', $user->id)->update(['id' => 999]);
    $delta = $sync->page('tenant_a', 'viewer', $initial['cursor'], null);
    expect($delta['changes'][0]['operation'])->toBe('remove');
    expect($delta['changes'][1]['id'])->toBe('999');
    DB::unprepared('TRUNCATE public.users');
    $last = $sync->page('tenant_a', 'viewer', $delta['cursor'], null);
    expect($last['changes'][0]['operation'])->toBe('remove');
});

it('rolls back only WMS infrastructure and preserves shared users', function () {
    $user = $this->makeUser();
    $migration = require database_path('migrations/2026_09_10_000001_create_wms_user_sync.php');
    $shared = require database_path('migrations/2026_09_10_000003_create_shared_entity_changes.php');
    $tenants = require database_path('migrations/2026_09_10_000004_move_sync_state_to_public.php');
    $tenants->down();
    $shared->down();
    $migration->down();
    expect(DB::table('public.users')->where('id', $user->id)->exists())->toBeTrue();
    expect(DB::selectOne("select to_regclass('public.entity_changes') as relation")->relation)->toBeNull();
    $migration->up();
    $shared->up();
    $tenants->up();
    expect(app(UserSyncService::class)->current($user->id)['name'])->toBe('Тест');
});

it('invalidates old cursors when the journal is recreated', function () {
    $admin = $this->makeUser([], true);
    $this->loginUser($admin);
    $cursor = $this->getJson('/web/users/sync')->json('cursor');
    DB::table('public.sync_state')->where('tenant_id', 'tenant_a')->update(['generation' => 'new_generation']);
    $this->getJson('/web/users/sync?cursor='.urlencode($cursor))->assertConflict();
});

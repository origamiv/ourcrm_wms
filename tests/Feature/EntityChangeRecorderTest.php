<?php

declare(strict_types=1);

use App\Models\ClientAccount;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_23_000002_move_entity_changes_to_laravel.php'))->up();
});

afterEach(function () {
    DB::rollBack();
});

it('records the Eloquent lifecycle in PHP without synchronization triggers', function () {
    $user = new User;
    $user->forceFill([
        'name' => 'Первоначальное имя',
        'email' => 'observer@example.test',
        'password' => 'Test_password_123',
        'status' => 1,
        'tenant_id' => 'tenant_a',
    ])->save();

    $events = DB::table('public.entity_changes')->where('entity', User::class)->where('entity_id', $user->id)->orderBy('revision')->get();
    expect($events)->toHaveCount(1)
        ->and(json_decode($events[0]->data, true)['name'])->toBe('Первоначальное имя')
        ->and(json_decode($events[0]->data, true))->not->toHaveKey('password');

    $user->forceFill(['name' => 'Новое имя'])->save();
    $user->delete();
    $user->restore();
    $user->forceDelete();

    $events = DB::table('public.entity_changes')->where('entity', User::class)->where('entity_id', $user->id)->orderBy('revision')->get();
    expect($events)->toHaveCount(5)
        ->and($events[1]->operation)->toBe('upsert')
        ->and(json_decode($events[2]->data, true)['deleted_at'])->not->toBeNull()
        ->and(json_decode($events[3]->data, true)['deleted_at'])->toBeNull()
        ->and($events[4]->operation)->toBe('remove')
        ->and($events[4]->data)->toBeNull();
});

it('fans a shared record out to initialized tenants in PHP', function () {
    DB::table('public.sync_state')->insertOrIgnore([
        ['tenant_id' => 'tenant_a', 'shared_initialized' => true],
        ['tenant_id' => 'tenant_b', 'shared_initialized' => true],
    ]);
    $module = new Module;
    $module->forceFill(['name' => 'Общий модуль', 'shortname' => 'shared_module', 'status' => 1])->save();

    $events = DB::table('public.entity_changes')
        ->where('entity', Module::class)
        ->where('entity_id', $module->id)
        ->orderByRaw('tenant_id COLLATE "C" NULLS FIRST')
        ->get();

    expect($events)->toHaveCount(3)
        ->and($events->pluck('operation')->all())->toBe(['upsert', 'upsert', 'upsert'])
        ->and($events->pluck('tenant_id')->all())->toBe([null, 'tenant_a', 'tenant_b']);
});

it('keeps explicitly configured credentials in the synchronized payload', function () {
    DB::statement('CREATE TABLE clients.accounts (id bigserial PRIMARY KEY, name text, pass text, token text, tenant_id text, created_at timestamp, updated_at timestamp, deleted_at timestamp)');
    $account = new ClientAccount;
    $account->forceFill([
        'name' => 'Учётная запись',
        'pass' => 'account_password',
        'token' => 'account_token',
        'tenant_id' => 'tenant_a',
    ])->save();

    $payload = json_decode((string) DB::table('public.entity_changes')
        ->where('entity', ClientAccount::class)
        ->where('entity_id', $account->id)
        ->value('data'), true);

    expect($payload['pass'])->toBe('account_password')
        ->and($payload['token'])->toBe('account_token');
});

it('does not treat direct SQL as an application synchronization write', function () {
    $id = DB::table('public.users')->insertGetId([
        'name' => 'SQL',
        'email' => 'sql@example.test',
        'password' => 'hash',
        'status' => 1,
        'tenant_id' => 'tenant_a',
    ]);

    expect(DB::table('public.entity_changes')->where('entity', User::class)->where('entity_id', $id)->exists())->toBeFalse();
});

it('removes only synchronization triggers', function () {
    $syncTriggers = DB::selectOne(<<<'SQL'
SELECT count(*)::integer AS amount
FROM pg_trigger t
JOIN pg_proc p ON p.oid = t.tgfoid
WHERE NOT t.tgisinternal
  AND p.proname IN (
    'capture_entity_change', 'capture_document_change', 'capture_good_card_change',
    'capture_user_with_roles_change', 'capture_role_user_change',
    'capture_tenant_entity_change', 'service_changed'
  )
SQL)->amount;
    $businessTriggers = DB::table('pg_trigger')->where('tgisinternal', false)->whereIn('tgname', [
        'wms_goods_manual_category', 'wms_goods_hierarchy', 'wms_goods_hierarchy_lock',
    ])->count();

    expect($syncTriggers)->toBe(0)
        ->and($businessTriggers)->toBeGreaterThan(0);
});

it('restores the previous trigger writer on migration rollback', function () {
    $migration = require database_path('migrations/2026_09_23_000002_move_entity_changes_to_laravel.php');
    $migration->down();

    $id = DB::table('public.users')->insertGetId([
        'name' => 'Rollback',
        'email' => 'rollback@example.test',
        'password' => 'hash',
        'status' => 1,
        'tenant_id' => 'tenant_a',
    ]);

    expect(DB::table('public.entity_changes')->where('entity', User::class)->where('entity_id', $id)->exists())->toBeTrue();
});

<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\EntitySyncService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
});
afterEach(function () {
    DB::rollBack();
});

it('keeps counters and cursor generations independent for each tenant', function () {
    $a = $this->makeUser();
    $b = $this->makeUser(['tenant_id' => 'tenant_b']);
    $sync = app(EntitySyncService::class);
    expect($sync->revision('tenant_a'))->toBe('1');
    expect($sync->revision('tenant_b'))->toBe('1');
    $aPage = $sync->page(User::class, 'tenant_a', 'viewer', null, null);
    $bPage = $sync->page(User::class, 'tenant_b', 'viewer', null, null);
    $a->update(['name' => 'Updated']);
    expect($sync->revision('tenant_a'))->toBe('2');
    expect($sync->revision('tenant_b'))->toBe('1');
    DB::table('public.sync_state')->where('tenant_id', 'tenant_a')->update(['generation' => 'reset_a']);
    expect($sync->page(User::class, 'tenant_b', 'viewer', $bPage['cursor'], null)['changes'])->toBe([]);
    try {
        $sync->page(User::class, 'tenant_a', 'viewer', $aPage['cursor'], null);
        $this->fail('Ожидался сброс курсора');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(409);
    }
    $empty = $sync->page(User::class, 'empty_tenant', 'viewer', null, null);
    expect($empty['changes'])->toBe([]);
    expect($sync->revision('empty_tenant'))->toBe('0');
});

it('keeps a single null-tenant counter and records moves into a tenant', function () {
    $user = $this->makeUser(['tenant_id' => null]);
    $this->makeUser(['tenant_id' => null]);
    expect(DB::table('public.sync_state')->whereNull('tenant_id')->count())->toBe(1);
    expect(DB::table('public.sync_state')->whereNull('tenant_id')->value('revision'))->toBe(2);
    $user->forceFill(['tenant_id' => 'tenant_a'])->save();
    expect(DB::table('public.sync_state')->whereNull('tenant_id')->value('revision'))->toBe(3);
    // The new tenant also receives the other shared user.
    expect(app(EntitySyncService::class)->revision('tenant_a'))->toBe('2');
    expect(DB::table('public.entity_changes')->whereNull('tenant_id')->where('revision', 3)->value('operation'))->toBe('remove');
});

it('migrates existing revisions without rewriting events and resets previous cursors', function () {
    $migration = require database_path('migrations/2026_09_10_000004_move_sync_state_to_public.php');
    $migration->down();
    $this->makeUser();
    $this->makeUser(['tenant_id' => 'tenant_b']);
    $this->makeUser(['tenant_id' => null]);
    $before = DB::table('public.entity_changes')->orderBy('revision')->get()->toJson();
    $generation = DB::table('wms.sync_state')->value('generation');
    $migration->up();
    expect(DB::table('public.entity_changes')->orderBy('revision')->get()->toJson())->toBe($before);
    expect(DB::selectOne("select to_regclass('wms.sync_state') as relation")->relation)->toBeNull();
    expect(app(EntitySyncService::class)->revision('tenant_a'))->toBe('1');
    expect(app(EntitySyncService::class)->revision('tenant_b'))->toBe('2');
    expect(DB::table('public.sync_state')->whereNull('tenant_id')->value('revision'))->toBe(3);
    expect(DB::table('public.sync_state')->where('generation', $generation)->exists())->toBeFalse();
    $migration->down();
    expect(DB::table('wms.sync_state')->value('revision'))->toBe(3);
});

it('refuses rollback when revisions overlap between tenants', function () {
    $this->makeUser();
    $this->makeUser(['tenant_id' => 'tenant_b']);
    $migration = require database_path('migrations/2026_09_10_000004_move_sync_state_to_public.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'ревизии разных организаций совпадают');
    expect(DB::table('public.entity_changes')->count())->toBe(2);
});

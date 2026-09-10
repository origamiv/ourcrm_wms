<?php

declare(strict_types=1);

use App\Models\KindKiz;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000021_create_kind_kiz.php'))->up();
    config(['wms.sync_page_size' => 100]);
    $this->loginUser($this->makeUser([], true));
});
afterEach(function () {
    DB::rollBack();
});

it('shares seeded marking kinds and retains null ownership on edits', function () {
    expect(KindKiz::whereNull('tenant_id')->orderBy('id')->pluck('name')->all())->toBe(['Серийный номер', 'Честный знак', 'IMEI', 'УИН']);
    $this->get('/goods/kind_kiz')->assertOk();
    $this->getJson('/web/sync/kind_kiz')->assertOk()->assertJsonCount(4, 'changes');
    $id = KindKiz::firstOrFail()->id;
    $row = app(App\Services\EntitySyncService::class)->current(KindKiz::class, 'tenant_a', $id);
    $payload = ['name' => 'Серийный номер', 'status' => 1, 'shortname' => 'СН', 'version' => $row['version']];
    $updated = $this->putJson('/web/goods/kind_kiz/'.$id, $payload)->assertOk()->json('data');
    expect(KindKiz::findOrFail($id)->tenant_id)->toBeNull();
    $this->putJson('/web/goods/kind_kiz/'.$id, $payload)->assertConflict();
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->getJson('/web/sync/kind_kiz')->assertOk()->assertJsonCount(4, 'changes');
    $this->get('/goods/kind_kiz/'.$id.'/view')->assertOk();
    $this->loginUser($this->makeUser());
    $this->putJson('/web/goods/kind_kiz/'.$id, [...$payload, 'version' => $updated['version']])->assertForbidden();
});

it('isolates new kinds and supports soft deletion with delta sync', function () {
    $payload = ['name' => 'Локальный код', 'status' => 1];
    $this->postJson('/web/goods/kind_kiz', [...$payload, 'status' => null])->assertUnprocessable();
    $this->postJson('/web/goods/kind_kiz', [...$payload, 'tenant_id' => 'tenant_b'])->assertUnprocessable();
    $row = $this->postJson('/web/goods/kind_kiz', $payload)->assertCreated()->assertJsonPath('data.tenant_id', 'tenant_a')->json('data');
    $cursor = $this->getJson('/web/sync/kind_kiz')->assertOk()->json('cursor');
    $this->deleteJson('/web/goods/kind_kiz/'.$row['id'], ['version' => $row['version']])->assertOk();
    expect(KindKiz::withTrashed()->findOrFail($row['id'])->trashed())->toBeTrue();
    $this->getJson('/web/sync/kind_kiz?cursor='.urlencode($cursor))->assertOk()->assertJsonCount(1, 'changes');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->get('/goods/kind_kiz/'.$row['id'].'/edit')->assertNotFound();
    $this->getJson('/web/sync/kind_kiz')->assertOk()->assertJsonCount(4, 'changes');
});

it('applies tenant assignments to shared marking kinds', function () {
    $id = KindKiz::firstOrFail()->id;
    DB::table('main.tenant_entity')->insert(['entity_type' => KindKiz::class, 'entity_id' => $id, 'tenant_id' => 'tenant_b']);
    $this->get('/goods/kind_kiz/'.$id.'/view')->assertNotFound();
    $this->getJson('/web/sync/kind_kiz')->assertOk()->assertJsonCount(3, 'changes');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->get('/goods/kind_kiz/'.$id.'/view')->assertOk();
});

<?php

declare(strict_types=1);

use App\Models\DeliveryService;
use App\Models\Marketplace;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000023_create_fulfillment_catalogs.php'))->up();
    $this->admin = $this->makeUser([], true);
    $this->loginUser($this->admin);
});
afterEach(function () {
    DB::rollBack();
});

it('manages fulfillment catalogs and marketplace links with versioned sync', function () {
    $market = $this->postJson('/web/fulfillment/marketplaces', ['name' => 'Маркет', 'status' => 1, 'icon' => 'market'])->assertCreated()->json('data');
    $payload = ['name' => 'Доставка', 'shortname' => 'ДС', 'status' => 1, 'marketplace_id' => $market['id'], 'icon' => 'truck', 'color' => '#123456', 'is_order_edit' => 1, 'prefix' => 'DS', 'folder' => 'delivery'];
    $row = $this->postJson('/web/fulfillment/delivery_services', $payload)->assertCreated()->assertJsonPath('data.prefix', 'DS')->json('data');
    expect((string) DeliveryService::find($row['id'])->marketplace->id)->toBe($market['id']);
    $this->get('/fulfillment/marketplaces')->assertOk();
    $this->get('/fulfillment/delivery_services/'.$row['id'].'/edit')->assertOk();
    $cursor = $this->getJson('/web/sync/delivery_services')->assertOk()->json('cursor');
    $updated = $this->putJson('/web/fulfillment/delivery_services/'.$row['id'], [...$payload, 'prefix' => 'NEW', 'version' => $row['version']])->assertOk()->json('data');
    $this->putJson('/web/fulfillment/delivery_services/'.$row['id'], [...$payload, 'version' => $row['version']])->assertConflict();
    $this->getJson('/web/sync/delivery_services?cursor='.urlencode($cursor))->assertOk()->assertJsonPath('changes.0.data.prefix', 'NEW');
    $this->deleteJson('/web/fulfillment/marketplaces/'.$market['id'], ['version' => $market['version']])->assertUnprocessable();
    $this->deleteJson('/web/fulfillment/delivery_services/'.$row['id'], ['version' => $updated['version']])->assertOk();
    $this->deleteJson('/web/fulfillment/marketplaces/'.$market['id'], ['version' => $market['version']])->assertOk();
    expect(DeliveryService::withTrashed()->find($row['id'])->trashed())->toBeTrue();
});

it('enforces tenant visibility for catalogs and their relationships', function () {
    $market = $this->postJson('/web/fulfillment/marketplaces', ['name' => 'Маркет', 'status' => 1])->assertCreated()->json('data');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->get('/fulfillment/marketplaces/'.$market['id'].'/edit')->assertNotFound();
    $this->getJson('/web/sync/marketplaces')->assertOk()->assertJsonCount(0, 'changes');
    $this->postJson('/web/fulfillment/delivery_services', ['name' => 'Доставка', 'status' => 1, 'marketplace_id' => $market['id']])->assertUnprocessable()->assertJsonValidationErrors('marketplace_id');
    $this->postJson('/web/fulfillment/marketplaces', ['name' => 'Маркет', 'status' => 1, 'tenant_id' => 'tenant_a'])->assertUnprocessable();
    $this->postJson('/web/fulfillment/delivery_services', ['name' => 'Доставка', 'status' => 1, 'is_order_edit' => 3])->assertUnprocessable();
    $this->loginUser($this->makeUser());
    $this->postJson('/web/fulfillment/marketplaces', ['name' => 'Маркет', 'status' => 1])->assertForbidden();
});

it('preserves shared ownership and exposes bearer endpoints', function () {
    $id = DB::table('wms.marketplaces')->insertGetId(['name' => 'Общий маркет', 'status' => 1, 'tenant_id' => null]);
    $row = app(App\Services\EntitySyncService::class)->current(Marketplace::class, 'tenant_a', $id);
    $this->putJson('/web/fulfillment/marketplaces/'.$id, ['name' => 'Общий', 'status' => 1, 'version' => $row['version']])->assertOk();
    expect(Marketplace::find($id)->tenant_id)->toBeNull();
    auth()->forgetGuards();
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');
    $this->withToken($token)->postJson('/api/fulfillment/delivery_services', ['name' => 'API доставка', 'status' => 1, 'marketplace_id' => $id])->assertCreated();
});

<?php

declare(strict_types=1);

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000028_create_warehouses.php'))->up();
    $this->admin = $this->makeUser([], true);
    $this->loginUser($this->admin);
});

afterEach(function () {
    DB::rollBack();
});

it('creates warehouse types and warehouses with a visible type lookup', function () {
    $type = $this->postJson('/web/fulfillment/type_warehouses', ['name' => 'Распределительный центр', 'shortname' => 'distribution_center', 'status' => 1])->assertCreated()->json('data');
    $warehouse = $this->postJson('/web/fulfillment/warehouses', ['name' => 'Основной склад', 'shortname' => 'main', 'type_warehouse_id' => $type['id'], 'status' => 1])->assertCreated()->json('data');
    expect((string) Warehouse::find($warehouse['id'])->typeWarehouse->id)->toBe((string) $type['id']);
    $this->get('/fulfillment/warehouses')->assertOk();
    $this->get('/fulfillment/type_warehouses')->assertOk();
    $this->getJson('/web/sync/warehouses')->assertOk()->assertJsonCount(1, 'changes');
    $this->getJson('/web/sync/type_warehouses')->assertOk()->assertJsonCount(1, 'changes');
    $this->postJson('/web/fulfillment/warehouses', ['name' => 'Дубликат', 'shortname' => 'main', 'status' => 1])->assertUnprocessable()->assertJsonValidationErrors('shortname');
    $this->postJson('/web/fulfillment/warehouses', ['name' => 'Чужой тип', 'type_warehouse_id' => $type['id'] + 100, 'status' => 1])->assertUnprocessable()->assertJsonValidationErrors('type_warehouse_id');
    $this->deleteJson('/web/fulfillment/type_warehouses/'.$type['id'], ['version' => $type['version']])->assertUnprocessable();
});

it('keeps warehouse data isolated by tenant', function () {
    $row = $this->postJson('/web/fulfillment/warehouses', ['name' => 'Склад A', 'shortname' => 'warehouse_a', 'status' => 1])->assertCreated()->json('data');
    $this->loginUser($this->makeUser(['tenant_id' => 'tenant_b'], true));
    $this->get('/fulfillment/warehouses/'.$row['id'].'/view')->assertNotFound();
    $this->getJson('/web/sync/warehouses')->assertOk()->assertJsonCount(0, 'changes');
});

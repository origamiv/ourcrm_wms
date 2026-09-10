<?php

declare(strict_types=1);

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000028_create_warehouses.php'))->up();
    (require database_path('migrations/2026_09_10_000029_seed_warehouse_types.php'))->up();
});

afterEach(function () {
    DB::rollBack();
});

it('seeds the shared main warehouse with the universal type', function () {
    (require database_path('migrations/2026_09_10_000030_seed_main_warehouse.php'))->up();

    $warehouse = Warehouse::where('name', 'Основной')->firstOrFail();
    expect($warehouse->shortname)->toBe('main')
        ->and($warehouse->status)->toBe(1)
        ->and($warehouse->tenant_id)->toBeNull()
        ->and($warehouse->typeWarehouse->name)->toBe('Универсальный');
});

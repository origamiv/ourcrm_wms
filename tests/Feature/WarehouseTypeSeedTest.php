<?php

declare(strict_types=1);

use App\Models\TypeWarehouse;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000028_create_warehouses.php'))->up();
});

afterEach(function () {
    DB::rollBack();
});

it('seeds universal first and common fulfillment warehouse types for every tenant', function () {
    (require database_path('migrations/2026_09_10_000029_seed_warehouse_types.php'))->up();

    $types = TypeWarehouse::orderBy('id')->get();
    expect($types)->toHaveCount(10)
        ->and($types->first()->name)->toBe('Универсальный')
        ->and($types->first()->shortname)->toBe('universal')
        ->and($types->every(fn (TypeWarehouse $type): bool => $type->tenant_id === null))->toBeTrue()
        ->and($types->pluck('shortname')->unique()->count())->toBe(10);
});

<?php

declare(strict_types=1);

use App\Models\DeliveryService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000023_create_fulfillment_catalogs.php'))->up();
});
afterEach(function () {
    DB::rollBack();
});

it('imports shared delivery services with normalized folders statuses and integration flags', function () {
    (require database_path('migrations/2026_09_10_000024_seed_delivery_services.php'))->up();
    expect(DeliveryService::whereNull('tenant_id')->count())->toBe(20)
        ->and(DeliveryService::orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all())->toBe(range(1, 20))
        ->and(DeliveryService::where('status', 1)->count())->toBe(12)
        ->and(DeliveryService::where('status', 2)->count())->toBe(8)
        ->and(DeliveryService::where('from_integration_only', 1)->count())->toBe(9)
        ->and(DeliveryService::where('folder', 'like', '%-%')->count())->toBe(0)
        ->and(DeliveryService::find(5)->folder)->toBe('orders_internal_labels')
        ->and(DeliveryService::find(5)->is_order_edit)->toBe(1)
        ->and(DeliveryService::find(20)->name)->toBe('dubaiexpress.ru');
    config(['wms.sync_page_size' => 100]);
    foreach (['tenant_a', 'tenant_b'] as $tenant) {
        $this->loginUser($this->makeUser(['tenant_id' => $tenant], true));
        $this->getJson('/web/sync/delivery_services')->assertOk()->assertJsonCount(20, 'changes');
    }
    $current = app(App\Services\EntitySyncService::class)->current(DeliveryService::class, 'tenant_b', '1');
    $this->putJson('/web/fulfillment/delivery_services/1', ['name' => 'Wildberries FBS', 'status' => 1, 'from_integration_only' => 0, 'version' => $current['version']])->assertOk()->assertJsonPath('data.from_integration_only', 0);
    expect(DeliveryService::find(1)->tenant_id)->toBeNull();
    $created = $this->postJson('/web/fulfillment/delivery_services', ['name' => 'Новая доставка', 'status' => 1])->assertCreated()->json('data.id');
    expect((int) $created)->toBeGreaterThan(20);
});

it('refuses to overwrite existing IDs', function () {
    DB::table('wms.delivery_services')->insert(['id' => 5, 'name' => 'Существующая доставка', 'status' => 1]);
    expect(fn () => (require database_path('migrations/2026_09_10_000024_seed_delivery_services.php'))->up())->toThrow(RuntimeException::class);
    expect(DeliveryService::find(5)->name)->toBe('Существующая доставка');
});

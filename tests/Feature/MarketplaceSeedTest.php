<?php

declare(strict_types=1);

use App\Models\DeliveryService;
use App\Models\Marketplace;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000023_create_fulfillment_catalogs.php'))->up();
    (require database_path('migrations/2026_09_10_000024_seed_delivery_services.php'))->up();
});

afterEach(function () {
    DB::rollBack();
});

it('fills Latin short names, shared marketplaces and delivery links', function () {
    (require database_path('migrations/2026_09_10_000025_seed_marketplaces_and_delivery_links.php'))->up();
    (require database_path('migrations/2026_09_10_000026_lowercase_fulfillment_shortnames.php'))->up();
    (require database_path('migrations/2026_09_10_000027_unique_fulfillment_shortnames.php'))->up();

    expect(Marketplace::whereNull('tenant_id')->count())->toBe(8)
        ->and(Marketplace::whereNull('shortname')->count())->toBe(0)
        ->and(DeliveryService::whereNull('shortname')->count())->toBe(0)
        ->and(DeliveryService::whereNull('marketplace_id')->count())->toBe(6);

    expect(DB::table('wms.delivery_services')->where('shortname', 'wb_fbs')->count())->toBe(1)
        ->and(DB::table('wms.delivery_services')->where('shortname', 'oz_fbo')->count())->toBe(1);

    foreach (Marketplace::pluck('shortname')->merge(DeliveryService::pluck('shortname')) as $shortname) {
        expect($shortname)->toMatch('/^[a-z0-9_.]+$/');
    }
    expect(DeliveryService::find(1)->marketplace->name)->toBe('Wildberries')
        ->and(DeliveryService::find(8)->marketplace->name)->toBe('OZON')
        ->and(DeliveryService::find(9)->marketplace->name)->toBe('Yandex Market')
        ->and(DeliveryService::find(15)->marketplace->name)->toBe('Сайт')
        ->and(DeliveryService::find(5)->marketplace_id)->toBeNull();
});

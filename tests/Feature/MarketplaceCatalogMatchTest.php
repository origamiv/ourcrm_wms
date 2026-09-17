<?php

declare(strict_types=1);

use App\Models\GoodMarketplace;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
    (require database_path('migrations/2026_09_10_000016_relate_good_cards_to_goods.php'))->up();
    (require database_path('migrations/2026_09_10_000017_sync_goods.php'))->up();
    (require database_path('migrations/2026_09_10_000020_manual_good_categories.php'))->up();
    (require database_path('migrations/2026_09_15_001000_create_marketplace_catalog.php'))->up();
    $this->admin = $this->makeUser([], true);
    $this->loginUser($this->admin);
});

afterEach(function () {
    DB::rollBack();
});

it('binds a marketplace row to a product and rejects a category', function () {
    $product = DB::table('goods.goods')->insertGetId(['name' => 'Товар', 'code' => 'GOOD-1', 'is_category' => 0, 'level' => 0, 'status' => 1, 'tenant_id' => 'tenant_a']);
    $category = DB::table('goods.goods')->insertGetId(['name' => 'Категория', 'code' => 'CAT-1', 'is_category' => 1, 'level' => 0, 'status' => 1, 'tenant_id' => 'tenant_a']);
    $marketplace = DB::table('wms.goods_marketplace')->insertGetId([
        'integration_id' => 1, 'webhook_id' => 1, 'marketplace' => 'ozon', 'external_id' => 'external-1',
        'name' => 'Позиция МП', 'status' => 1, 'tenant_id' => 'tenant_a', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $version = app(App\Services\EntitySyncService::class)->current(GoodMarketplace::class, 'tenant_a', $marketplace)['version'];

    $this->putJson('/web/fulfillment/goods_marketplace/'.$marketplace, ['good_id' => $product, 'version' => $version])
        ->assertOk()->assertJsonPath('data.good_id', $product)->assertJsonPath('data.match_type', 'manual');
    expect(DB::table('wms.goods_marketplace')->where('id', $marketplace)->value('good_id'))->toBe($product);

    $current = app(App\Services\EntitySyncService::class)->current(GoodMarketplace::class, 'tenant_a', $marketplace);
    $this->putJson('/web/fulfillment/goods_marketplace/'.$marketplace, ['good_id' => $category, 'version' => $current['version']])
        ->assertUnprocessable();
});

it('uses the same marketplace match operation through bearer API', function () {
    $product = DB::table('goods.goods')->insertGetId(['name' => 'API товар', 'is_category' => 0, 'level' => 0, 'status' => 1, 'tenant_id' => 'tenant_a']);
    $marketplace = DB::table('wms.goods_marketplace')->insertGetId([
        'integration_id' => 1, 'webhook_id' => 1, 'marketplace' => 'wb', 'external_id' => 'external-api',
        'name' => 'API позиция', 'status' => 1, 'tenant_id' => 'tenant_a', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $version = app(App\Services\EntitySyncService::class)->current(GoodMarketplace::class, 'tenant_a', $marketplace)['version'];
    auth()->forgetGuards();
    $token = $this->postJson('/api/auth/token', ['email' => $this->admin->email, 'password' => 'Test_password_123'])->assertOk()->json('token');

    $this->withToken($token)->putJson('/api/fulfillment/goods_marketplace/'.$marketplace, ['good_id' => $product, 'version' => $version])
        ->assertOk()->assertJsonPath('data.good_id', $product);
});

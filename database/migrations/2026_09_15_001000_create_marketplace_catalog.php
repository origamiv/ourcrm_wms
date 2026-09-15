<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms.goods_marketplace', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->unsignedBigInteger('webhook_id');
            $table->string('marketplace', 32);
            $table->string('external_id');
            $table->string('external_sku')->nullable();
            $table->string('offer_id')->nullable();
            $table->string('name')->nullable();
            $table->json('barcodes')->nullable();
            $table->json('raw_data')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('good_id')->nullable();
            $table->string('match_type', 32)->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->string('tenant_id');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'webhook_id', 'external_id'], 'goods_marketplace_unique');
            $table->index(['tenant_id', 'marketplace', 'good_id']);
        });
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_goods_marketplace_change AFTER INSERT OR UPDATE OR DELETE ON wms.goods_marketplace
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\GoodMarketplace', '["id","integration_id","webhook_id","marketplace","external_id","external_sku","offer_id","name","barcodes","status","good_id","match_type","matched_at","synced_at","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_goods_marketplace_truncate BEFORE TRUNCATE ON wms.goods_marketplace FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\GoodMarketplace');
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS wms_goods_marketplace_change ON wms.goods_marketplace');
        DB::statement('DROP TRIGGER IF EXISTS wms_goods_marketplace_truncate ON wms.goods_marketplace');
        Schema::dropIfExists('wms.goods_marketplace');
    }
};

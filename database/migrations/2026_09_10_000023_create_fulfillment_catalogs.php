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
        DB::statement("SET LOCAL lock_timeout = '5s'");
        foreach (['marketplaces', 'delivery_services'] as $catalog) {
            Schema::create('wms.'.$catalog, function (Blueprint $table) use ($catalog) {
                $table->id();
                $table->string('name');
                $table->string('shortname')->nullable();
                $table->smallInteger('status')->default(1);
                $table->string('icon')->nullable();
                if ($catalog === 'delivery_services') {
                    $table->foreignId('marketplace_id')->nullable()->constrained('wms.marketplaces')->restrictOnDelete();
                    $table->index('marketplace_id');
                    $table->string('color', 32)->nullable();
                    $table->smallInteger('is_order_edit')->nullable();
                    $table->string('prefix')->nullable();
                    $table->string('folder')->nullable();
                }
                $table->string('tenant_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_marketplaces_change AFTER INSERT OR UPDATE OR DELETE ON wms.marketplaces FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Marketplace', '["name","shortname","status","icon","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_marketplaces_truncate BEFORE TRUNCATE ON wms.marketplaces FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Marketplace');
CREATE TRIGGER wms_delivery_services_change AFTER INSERT OR UPDATE OR DELETE ON wms.delivery_services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\DeliveryService', '["name","shortname","status","icon","color","is_order_edit","prefix","folder","marketplace_id","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_delivery_services_truncate BEFORE TRUNCATE ON wms.delivery_services FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\DeliveryService');
SQL);
    }

    public function down(): void
    {
        Schema::drop('wms.delivery_services');
        Schema::drop('wms.marketplaces');
        DB::unprepared(<<<'SQL'
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity IN ('App\Models\Marketplace', 'App\Models\DeliveryService') AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity IN ('App\Models\Marketplace', 'App\Models\DeliveryService');
SQL);
    }
};

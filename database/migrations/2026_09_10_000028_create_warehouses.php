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
        Schema::create('wms.type_warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('wms.warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->foreignId('type_warehouse_id')->nullable()->constrained('wms.type_warehouses')->restrictOnDelete();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index('type_warehouse_id');
        });
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_type_warehouses_change AFTER INSERT OR UPDATE OR DELETE ON wms.type_warehouses FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\TypeWarehouse', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_type_warehouses_truncate BEFORE TRUNCATE ON wms.type_warehouses FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\TypeWarehouse');
CREATE TRIGGER wms_warehouses_change AFTER INSERT OR UPDATE OR DELETE ON wms.warehouses FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Warehouse', '["name","shortname","type_warehouse_id","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_warehouses_truncate BEFORE TRUNCATE ON wms.warehouses FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Warehouse');
SQL);
        DB::statement('CREATE UNIQUE INDEX wms_type_warehouses_shortname_unique ON wms.type_warehouses (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX wms_warehouses_shortname_unique ON wms.warehouses (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
    }

    public function down(): void
    {
        Schema::drop('wms.warehouses');
        Schema::drop('wms.type_warehouses');
        DB::unprepared(<<<'SQL'
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity IN ('App\Models\Warehouse', 'App\Models\TypeWarehouse') AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity IN ('App\Models\Warehouse', 'App\Models\TypeWarehouse');
SQL);
    }
};

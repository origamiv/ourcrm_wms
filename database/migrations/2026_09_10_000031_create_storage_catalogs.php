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
        Schema::create('wms.type_storage', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms.zones', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms.cells', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('wms.warehouses')->restrictOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('wms.zones')->nullOnDelete();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->integer('row')->nullable();
            $table->integer('level')->nullable();
            $table->integer('number')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['warehouse_id', 'zone_id']);
        });

        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_type_storage_change AFTER INSERT OR UPDATE OR DELETE ON wms.type_storage FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\TypeStorage', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_type_storage_truncate BEFORE TRUNCATE ON wms.type_storage FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\TypeStorage');
CREATE TRIGGER wms_zones_change AFTER INSERT OR UPDATE OR DELETE ON wms.zones FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Zone', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_zones_truncate BEFORE TRUNCATE ON wms.zones FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Zone');
CREATE TRIGGER wms_cells_change AFTER INSERT OR UPDATE OR DELETE ON wms.cells FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Cell', '["warehouse_id","zone_id","name","shortname","row","level","number","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_cells_truncate BEFORE TRUNCATE ON wms.cells FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Cell');
SQL);

        DB::statement('CREATE UNIQUE INDEX wms_type_storage_shortname_unique ON wms.type_storage (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX wms_zones_shortname_unique ON wms.zones (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX wms_cells_shortname_unique ON wms.cells (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL');
    }

    public function down(): void
    {
        Schema::drop('wms.cells');
        Schema::drop('wms.zones');
        Schema::drop('wms.type_storage');
        DB::unprepared(<<<'SQL'
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity IN ('App\Models\Cell', 'App\Models\Zone', 'App\Models\TypeStorage') AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity IN ('App\Models\Cell', 'App\Models\Zone', 'App\Models\TypeStorage');
SQL);
    }
};

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
        Schema::create('wms.cell_goods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('wms.warehouses')->restrictOnDelete();
            $table->foreignId('cell_id')->constrained('wms.cells')->restrictOnDelete();
            $table->bigInteger('cnt')->default(0);
            $table->dateTime('put_at')->nullable();
            $table->dateTime('leave_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('tenant_id')->nullable()->index();
            $table->jsonb('src')->nullable();
            $table->index(['warehouse_id', 'cell_id']);
        });

        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_cell_goods_change AFTER INSERT OR UPDATE OR DELETE ON wms.cell_goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\CellGood', '["warehouse_id","cell_id","cnt","put_at","leave_at","tenant_id","src","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_cell_goods_truncate BEFORE TRUNCATE ON wms.cell_goods FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\CellGood');
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.cell_goods');
        DB::unprepared(<<<'SQL'
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\CellGood' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\CellGood';
SQL);
    }
};

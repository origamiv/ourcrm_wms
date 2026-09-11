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
        Schema::table('wms.cell_goods', function (Blueprint $table): void {
            $table->foreignId('good_id')->nullable()->after('cell_id')->constrained('goods.goods')->nullOnDelete();
            $table->index('good_id');
        });

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_cell_goods_change ON wms.cell_goods;
CREATE TRIGGER wms_cell_goods_change AFTER INSERT OR UPDATE OR DELETE ON wms.cell_goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\CellGood', '["warehouse_id","cell_id","good_id","user_id","cnt","put_at","leave_at","tenant_id","src","created_at","updated_at","deleted_at"]');
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_cell_goods_change ON wms.cell_goods;
CREATE TRIGGER wms_cell_goods_change AFTER INSERT OR UPDATE OR DELETE ON wms.cell_goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\CellGood', '["warehouse_id","cell_id","user_id","cnt","put_at","leave_at","tenant_id","src","created_at","updated_at","deleted_at"]');
SQL);

        Schema::table('wms.cell_goods', function (Blueprint $table): void {
            $table->dropForeign(['good_id']);
            $table->dropIndex(['good_id']);
            $table->dropColumn('good_id');
        });
    }
};

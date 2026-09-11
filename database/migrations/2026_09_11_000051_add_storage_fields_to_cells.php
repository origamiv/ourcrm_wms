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
        Schema::table('wms.cells', function (Blueprint $table): void {
            $table->integer('priority')->nullable()->after('number');
            $table->foreignId('type_storage_id')->nullable()->after('priority')->constrained('wms.type_storage')->nullOnDelete();
            $table->index('type_storage_id');
        });

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_cells_change ON wms.cells;
CREATE TRIGGER wms_cells_change AFTER INSERT OR UPDATE OR DELETE ON wms.cells FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Cell', '["warehouse_id","zone_id","name","shortname","row","level","number","priority","type_storage_id","status","tenant_id","created_at","updated_at","deleted_at"]');
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_cells_change ON wms.cells;
CREATE TRIGGER wms_cells_change AFTER INSERT OR UPDATE OR DELETE ON wms.cells FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Cell', '["warehouse_id","zone_id","name","shortname","row","level","number","status","tenant_id","created_at","updated_at","deleted_at"]');
SQL);

        Schema::table('wms.cells', function (Blueprint $table): void {
            $table->dropForeign(['type_storage_id']);
            $table->dropIndex(['type_storage_id']);
            $table->dropColumn(['priority', 'type_storage_id']);
        });
    }
};

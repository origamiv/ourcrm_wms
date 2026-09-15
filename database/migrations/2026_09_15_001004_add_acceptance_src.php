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
        if (! Schema::hasColumn('wms.acceptances', 'src')) {
            Schema::table('wms.acceptances', function (Blueprint $table): void {
                $table->json('src')->nullable()->after('code');
            });
        }

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_acceptances_change ON wms.acceptances;
CREATE TRIGGER wms_acceptances_change AFTER INSERT OR UPDATE OR DELETE ON wms.acceptances
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Acceptance', '["code","client_id","warehouse_id","task_id","plan_count","fact_count","progress","type_acceptance_id","started_at","finished_at","status","tenant_id","src","created_at","updated_at","deleted_at"]');
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS wms_acceptances_change ON wms.acceptances;');
        Schema::table('wms.acceptances', function (Blueprint $table): void {
            $table->dropColumn('src');
        });

        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_acceptances_change AFTER INSERT OR UPDATE OR DELETE ON wms.acceptances
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Acceptance', '["code","client_id","warehouse_id","task_id","plan_count","fact_count","progress","type_acceptance_id","started_at","finished_at","status","tenant_id","created_at","updated_at","deleted_at"]');
SQL);
    }
};

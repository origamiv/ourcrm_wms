<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function refreshTrigger(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_tasks_change ON wms.tasks;
CREATE TRIGGER wms_tasks_change AFTER INSERT OR UPDATE OR DELETE ON wms.tasks FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Task', '["name","shortname","client_id","task_type_id","status_id","planned_at","started_at","completed_at","charged_at","charged_sum","confirmed_at","comment","internal_comment","priority_id","src","order_id","warehouse_id","user_id","created_by_user_id","fact_count","status","tenant_id","created_at","updated_at","deleted_at"]');
SQL);
    }

    public function up(): void
    {
        Schema::table('wms.tasks', function (Blueprint $table): void {
            $table->timestamp('charged_at')->nullable();
            $table->decimal('charged_sum', 15, 2)->nullable();
            $table->timestamp('confirmed_at')->nullable();
        });
        $this->refreshTrigger();
    }

    public function down(): void
    {
        Schema::table('wms.tasks', function (Blueprint $table): void {
            $table->dropColumn(['charged_at', 'charged_sum', 'confirmed_at']);
        });
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_tasks_change ON wms.tasks;
CREATE TRIGGER wms_tasks_change AFTER INSERT OR UPDATE OR DELETE ON wms.tasks FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Task', '["name","shortname","client_id","task_type_id","status_id","planned_at","started_at","completed_at","comment","internal_comment","priority_id","src","order_id","warehouse_id","user_id","created_by_user_id","fact_count","status","tenant_id","created_at","updated_at","deleted_at"]');
SQL);
    }
};

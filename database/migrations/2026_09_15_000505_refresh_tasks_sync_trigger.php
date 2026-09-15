<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void { DB::unprepared("DROP TRIGGER IF EXISTS wms_tasks_change ON wms.tasks; CREATE TRIGGER wms_tasks_change AFTER INSERT OR UPDATE OR DELETE ON wms.tasks FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\Task', '[\"id\",\"code\",\"name\",\"shortname\",\"client_id\",\"task_type_id\",\"task_stage_id\",\"status_id\",\"planned_at\",\"started_at\",\"completed_at\",\"charged_at\",\"charged_sum\",\"confirmed_at\",\"comment\",\"internal_comment\",\"priority_id\",\"src\",\"order_id\",\"warehouse_id\",\"user_id\",\"created_by_user_id\",\"fact_count\",\"status\",\"tenant_id\",\"created_at\",\"updated_at\",\"deleted_at\"]');"); }
    public function down(): void { }
};

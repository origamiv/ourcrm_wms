<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::unprepared("DROP TRIGGER IF EXISTS wms_task_stages_change ON wms.task_stages; CREATE TRIGGER wms_task_stages_change AFTER INSERT OR UPDATE OR DELETE ON wms.task_stages FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\TaskStage', '[\"id\",\"name\",\"shortname\",\"status\",\"icon\",\"tenant_id\",\"src\",\"created_at\",\"updated_at\",\"deleted_at\"]');");
        DB::unprepared("DROP TRIGGER IF EXISTS wms_task_stages_truncate ON wms.task_stages; CREATE TRIGGER wms_task_stages_truncate BEFORE TRUNCATE ON wms.task_stages FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\\Models\\TaskStage');");
    }
    public function down(): void { DB::unprepared('DROP TRIGGER IF EXISTS wms_task_stages_change ON wms.task_stages; DROP TRIGGER IF EXISTS wms_task_stages_truncate ON wms.task_stages;'); }
};

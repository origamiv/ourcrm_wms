<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared("CREATE TRIGGER wms_scheduler_tasks_change AFTER INSERT OR UPDATE OR DELETE ON public.scheduler_tasks FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\SchedulerTask', '[\"name\",\"shortname\",\"module\",\"task_type\",\"target\",\"options\",\"status\",\"tenant_id\",\"created_at\",\"updated_at\",\"deleted_at\"]');");
    }
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS wms_scheduler_tasks_change ON public.scheduler_tasks;");
        DB::table('public.entity_changes')->where('entity', App\Models\SchedulerTask::class)->delete();
    }
};

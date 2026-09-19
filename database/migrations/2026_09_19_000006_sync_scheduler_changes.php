<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared("CREATE TRIGGER wms_scheduler_change AFTER INSERT OR UPDATE OR DELETE ON public.scheduler FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\Scheduler', '[\"name\",\"module\",\"tenant_id\",\"task_key\",\"task_type\",\"params\",\"schedule\",\"status\",\"next_run_at\",\"last_run_at\",\"created_at\",\"updated_at\",\"deleted_at\"]');");
        DB::statement('UPDATE public.scheduler SET updated_at = updated_at');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS wms_scheduler_change ON public.scheduler');
        DB::table('public.entity_changes')->where('entity', App\Models\Scheduler::class)->delete();
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('UPDATE public.scheduler_tasks SET updated_at = updated_at');
    }

    public function down(): void
    {
        DB::table('public.entity_changes')->where('entity', App\Models\SchedulerTask::class)->delete();
    }
};

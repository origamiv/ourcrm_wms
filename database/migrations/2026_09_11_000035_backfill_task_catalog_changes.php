<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Seed rows were created before the change triggers in the initial migration.
        // Touching them now records a complete snapshot for IndexedDB synchronization.
        foreach (['task_types', 'task_statuses', 'priorities'] as $table) {
            DB::statement("UPDATE wms.{$table} SET updated_at = COALESCE(updated_at, CURRENT_TIMESTAMP)");
        }
    }

    public function down(): void
    {
        DB::table('public.entity_changes')
            ->whereIn('entity', ['App\\Models\\TaskType', 'App\\Models\\TaskStatus', 'App\\Models\\Priority'])
            ->delete();
    }
};

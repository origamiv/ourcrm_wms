<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '60s'");
        DB::unprepared(file_get_contents(database_path('sql/reference_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement('LOCK TABLE main.modules, main.features, main.icons, main.files IN SHARE ROW EXCLUSIVE MODE');
        foreach (['modules' => App\Models\Module::class, 'features' => App\Models\Feature::class, 'icons' => App\Models\Icon::class, 'files' => App\Models\File::class] as $table => $entity) {
            DB::unprepared("DROP TRIGGER wms_{$table}_change ON main.{$table}; DROP TRIGGER wms_{$table}_truncate ON main.{$table};");
            DB::update('UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = ? AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id)', [$entity]);
            DB::table('public.entity_changes')->where('entity', $entity)->delete();
        }
        DB::statement('DROP FUNCTION wms.capture_global_entity_truncate()');
    }
};

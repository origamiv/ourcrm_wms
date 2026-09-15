<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX entity_changes_entity_record_revision ON public.entity_changes (entity, entity_id, revision DESC)',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS public.entity_changes_entity_record_revision');
    }
};

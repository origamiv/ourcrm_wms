<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '120s'");
        if (! Schema::hasTable('main.tenant_entity')) {
            Schema::create('main.tenant_entity', function ($table) {
                $table->id();
                $table->string('tenant_id')->nullable();
                $table->string('entity_type');
                $table->bigInteger('entity_id');
                $table->timestamps();
            });
        }
        DB::statement('CREATE INDEX IF NOT EXISTS wms_tenant_entity_lookup ON main.tenant_entity (entity_type, (entity_id::text), tenant_id)');
        DB::statement('LOCK TABLE public.sync_state, public.entity_changes, main.tenant_entity IN SHARE ROW EXCLUSIVE MODE');
        Schema::table('public.sync_state', fn ($table) => $table->boolean('shared_initialized')->default(false));
        DB::unprepared(file_get_contents(database_path('sql/tenant_entity_visibility.sql')));
        DB::statement('SELECT wms.initialize_tenant_shares(tenant_id) FROM public.sync_state WHERE tenant_id IS NOT NULL ORDER BY tenant_id COLLATE "C"');
        DB::statement('UPDATE public.sync_state SET generation = md5(random()::text || clock_timestamp()::text)');
    }

    public function down(): void
    {
        throw new RuntimeException('Откат правил видимости требует согласованного восстановления журнала и версии приложения; main.tenant_entity сохраняется.');
    }
};

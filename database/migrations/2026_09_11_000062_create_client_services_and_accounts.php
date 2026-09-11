<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients.services', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('shortname')->nullable();
            $table->integer('status')->default(0)->nullable();
            $table->foreignId('client_id')->constrained('clients.clients')->restrictOnDelete();
            $table->string('tenant_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'client_id']);
        });
        Schema::create('clients.accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('shortname')->nullable();
            $table->string('host')->nullable();
            $table->string('login')->nullable();
            $table->string('pass')->nullable();
            $table->text('token')->nullable();
            $table->text('descr')->nullable();
            $table->integer('status')->default(0)->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('server_id')->nullable();
            $table->json('src')->nullable();
            $table->foreignId('client_id')->constrained('clients.clients')->restrictOnDelete();
            $table->string('tenant_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'client_id']);
        });
        DB::unprepared(file_get_contents(database_path('sql/client_catalog_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
        LOCK TABLE clients.services, clients.accounts IN SHARE ROW EXCLUSIVE MODE;
        DROP TRIGGER wms_client_services_change ON clients.services;
        DROP TRIGGER wms_client_services_truncate ON clients.services;
        DROP TRIGGER wms_client_accounts_change ON clients.accounts;
        DROP TRIGGER wms_client_accounts_truncate ON clients.accounts;
        UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text)
        WHERE EXISTS (SELECT 1 FROM public.entity_changes e
            WHERE e.entity IN ('App\Models\ClientService', 'App\Models\ClientAccount')
                AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
        DELETE FROM public.entity_changes WHERE entity IN ('App\Models\ClientService', 'App\Models\ClientAccount');
        SQL);
        Schema::dropIfExists('clients.accounts');
        Schema::dropIfExists('clients.services');
    }
};

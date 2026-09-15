<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms.tswms_imports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('source_client_id')->nullable();
            $table->string('source_system', 32)->default('tswms');
            $table->string('status', 20)->default('queued');
            $table->string('current_stage', 64)->nullable();
            $table->unsignedSmallInteger('total_stages')->default(0);
            $table->unsignedSmallInteger('completed_stages')->default(0);
            $table->unsignedInteger('total_jobs')->default(0);
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->jsonb('options')->nullable();
            $table->jsonb('warnings')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('last_job_id')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'source_client_id', 'status'], 'tswms_imports_scope_status_idx');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.tswms_imports');
    }
};

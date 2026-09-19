<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public.scheduler', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('module', 64)->default('wms')->index();
            $table->string('tenant_id')->index();
            $table->string('task_key');
            $table->string('task_type', 16);
            $table->jsonb('params')->nullable();
            $table->jsonb('schedule');
            $table->smallInteger('status')->default(1);
            $table->timestampTz('next_run_at')->nullable();
            $table->timestampTz('last_run_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['module', 'status', 'next_run_at']);
        });

        Schema::create('public.scheduler_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scheduler_id')->constrained('public.scheduler')->cascadeOnDelete();
            $table->string('tenant_id')->index();
            $table->string('module', 64)->default('wms');
            $table->string('task_key');
            $table->string('status', 16)->default('queued')->index();
            $table->timestampTz('queued_at');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('result')->nullable();
            $table->timestampsTz();
            $table->index(['scheduler_id', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public.scheduler_runs');
        Schema::dropIfExists('public.scheduler');
    }
};

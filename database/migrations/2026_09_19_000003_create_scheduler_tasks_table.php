<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public.scheduler_tasks', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('shortname')->index(); $table->string('module', 64)->default('wms')->index();
            $table->string('task_type', 16); $table->string('target'); $table->jsonb('options')->default('{}'); $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->index(); $table->timestampsTz(); $table->softDeletesTz();
            $table->unique(['tenant_id', 'shortname']); $table->index(['tenant_id', 'module', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('public.scheduler_tasks'); }
};

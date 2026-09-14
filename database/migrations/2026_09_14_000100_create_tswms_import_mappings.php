<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms.tswms_import_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('source_system', 32)->default('tswms');
            $table->unsignedBigInteger('source_client_id');
            $table->string('source_database')->nullable();
            $table->string('source_table');
            $table->string('source_id');
            $table->string('target_entity');
            $table->string('target_id');
            $table->timestamp('source_updated_at')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['source_system', 'source_client_id', 'source_table', 'source_id', 'target_entity'], 'tswms_mapping_unique');
            $table->index(['target_entity', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.tswms_import_mappings');
    }
};

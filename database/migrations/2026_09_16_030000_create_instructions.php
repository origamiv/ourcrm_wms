<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms.instructions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname', 120);
            $table->string('section_key', 32);
            $table->string('content_type', 16);
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_path');
            $table->string('original_filename');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('status')->default(1);
            $table->uuid('tenant_id');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'shortname']);
            $table->index(['tenant_id', 'section_key', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.instructions');
    }
};

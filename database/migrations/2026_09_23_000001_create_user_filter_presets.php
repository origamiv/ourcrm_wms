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
        Schema::create('main.user_filter_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('user_id')->constrained('public.users');
            $table->string('screen_key', 100);
            $table->string('name', 80);
            $table->jsonb('rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'user_id', 'screen_key', 'is_active'], 'user_filter_presets_scope_idx');
        });

        DB::statement('CREATE UNIQUE INDEX user_filter_presets_name_unique ON main.user_filter_presets (tenant_id, user_id, screen_key, lower(name)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('main.user_filter_presets');
    }
};

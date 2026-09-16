<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('clients.clients', function (Blueprint $table): void { $table->string('code')->nullable(); $table->jsonb('src')->nullable(); $table->unique(['tenant_id', 'code']); }); }
    public function down(): void { Schema::table('clients.clients', function (Blueprint $table): void { $table->dropUnique(['clients_clients_tenant_id_code_unique']); $table->dropColumn(['code', 'src']); }); }
};

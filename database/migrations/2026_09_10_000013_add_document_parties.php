<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        Schema::table('clients.documents', function (Blueprint $table) {
            $table->foreignId('executor_id')->nullable()->constrained('main.companies')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('clients.companies')->restrictOnDelete();
        });
        DB::unprepared(file_get_contents(database_path('sql/document_parties_sync.sql')));
    }

    public function down(): void
    {
        DB::unprepared(explode('LOCK TABLE', file_get_contents(database_path('sql/document_sync.sql')))[0]);
        Schema::table('clients.documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('executor_id');
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};

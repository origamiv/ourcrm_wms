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
        DB::statement("SET LOCAL lock_timeout = '5s'");
        Schema::table('clients.documents', fn (Blueprint $table) => $table->decimal('amount', 18, 2)->nullable());
        DB::unprepared(file_get_contents(database_path('sql/document_amount_sync.sql')));
    }

    public function down(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/document_parties_sync.sql')));
        Schema::table('clients.documents', fn (Blueprint $table) => $table->dropColumn('amount'));
    }
};

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
        Schema::table('clients.doc_types', fn (Blueprint $table) => $table->json('settings')->nullable());
        DB::unprepared("DROP TRIGGER IF EXISTS wms_doc_types_change ON clients.doc_types;
            CREATE TRIGGER wms_doc_types_change AFTER INSERT OR UPDATE OR DELETE ON clients.doc_types FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\DocType', '[\"name\",\"shortname\",\"status\",\"settings\",\"created_at\",\"updated_at\",\"deleted_at\"]');");
        foreach (require config_path('document_print.php') as $id => $print) {
            DB::table('clients.doc_types')->where('id', $id)->update(['settings' => json_encode(['print' => $print], JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('clients.doc_types', fn (Blueprint $table) => $table->dropColumn('settings'));
    }
};

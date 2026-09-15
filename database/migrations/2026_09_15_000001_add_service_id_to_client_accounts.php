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
        if (! Schema::hasColumn('clients.accounts', 'service_id')) {
            Schema::table('clients.accounts', function (Blueprint $table): void {
                $table->foreignId('service_id')->nullable()->after('client_id')
                    ->constrained('clients.services')->nullOnDelete();
                $table->index(['tenant_id', 'service_id']);
            });
        }
        DB::unprepared(<<<'SQL'
        DROP TRIGGER IF EXISTS wms_client_accounts_change ON clients.accounts;
        CREATE TRIGGER wms_client_accounts_change AFTER INSERT OR UPDATE OR DELETE ON clients.accounts
        FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\ClientAccount', '["name","shortname","host","login","pass","token","descr","status","group_id","server_id","src","client_id","service_id","tenant_id","created_at","updated_at","deleted_at"]');
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS wms_client_accounts_change ON clients.accounts');
        if (Schema::hasColumn('clients.accounts', 'service_id')) {
            Schema::table('clients.accounts', function (Blueprint $table): void {
                $table->dropForeign(['service_id']);
                $table->dropIndex(['tenant_id', 'service_id']);
                $table->dropColumn('service_id');
            });
        }
    }
};

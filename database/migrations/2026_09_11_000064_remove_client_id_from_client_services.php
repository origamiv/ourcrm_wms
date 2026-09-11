<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('LOCK TABLE clients.services IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('DROP TRIGGER wms_client_services_change ON clients.services');
        DB::statement('ALTER TABLE clients.services DROP CONSTRAINT clients_services_client_id_foreign');
        DB::statement('DROP INDEX IF EXISTS clients_services_tenant_id_client_id_index');
        DB::statement('ALTER TABLE clients.services DROP COLUMN client_id');
        DB::statement("CREATE TRIGGER wms_client_services_change AFTER INSERT OR UPDATE OR DELETE ON clients.services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\ClientService', '[\"name\",\"shortname\",\"status\",\"tenant_id\",\"created_at\",\"updated_at\",\"deleted_at\"]')");
        DB::table('public.entity_changes')->where('entity', 'App\Models\ClientService')->update([
            'data' => DB::raw("CASE WHEN data IS NULL THEN NULL ELSE data - 'client_id' END"),
        ]);
    }

    public function down(): void
    {
        DB::statement('LOCK TABLE clients.services IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('DROP TRIGGER wms_client_services_change ON clients.services');
        DB::statement('ALTER TABLE clients.services ADD COLUMN client_id bigint NULL');
        DB::statement('ALTER TABLE clients.services ADD CONSTRAINT clients_services_client_id_foreign FOREIGN KEY (client_id) REFERENCES clients.clients (id) ON DELETE RESTRICT');
        DB::statement('CREATE INDEX clients_services_tenant_id_client_id_index ON clients.services (tenant_id, client_id)');
        DB::statement("CREATE TRIGGER wms_client_services_change AFTER INSERT OR UPDATE OR DELETE ON clients.services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\ClientService', '[\"name\",\"shortname\",\"status\",\"client_id\",\"tenant_id\",\"created_at\",\"updated_at\",\"deleted_at\"]')");
    }
};

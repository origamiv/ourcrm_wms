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
        if (! Schema::hasColumn('integration.webhooks', 'client_id')) {
            Schema::table('integration.webhooks', function (Blueprint $table): void {
                $table->foreignId('client_id')->nullable()->after('status')
                    ->constrained('clients.clients')->nullOnDelete();
                $table->index(['tenant_id', 'client_id']);
            });
        }

        DB::unprepared(<<<'SQL'
        DROP TRIGGER IF EXISTS wms_integration_webhooks_change ON integration.webhooks;
        CREATE TRIGGER wms_integration_webhooks_change AFTER INSERT OR UPDATE OR DELETE ON integration.webhooks
        FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationWebhook', '["id","name","shortname","status","tenant_id","created_at","updated_at","deleted_at","client_id","service_id","type_hook_id","rules_id","cnt","dat_last_run"]');
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS wms_integration_webhooks_change ON integration.webhooks');
        if (Schema::hasColumn('integration.webhooks', 'client_id')) {
            Schema::table('integration.webhooks', function (Blueprint $table): void {
                $table->dropForeign(['client_id']);
                $table->dropIndex(['tenant_id', 'client_id']);
                $table->dropColumn('client_id');
            });
        }
    }
};

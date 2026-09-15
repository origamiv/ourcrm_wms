<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms.warehouses', function (Blueprint $table): void { $table->string('code')->nullable()->after('shortname'); });
        DB::statement('DROP TRIGGER IF EXISTS wms_warehouses_change ON wms.warehouses');
        DB::statement("CREATE TRIGGER wms_warehouses_change AFTER INSERT OR UPDATE OR DELETE ON wms.warehouses FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\Warehouse', '[\"name\",\"shortname\",\"code\",\"type_warehouse_id\",\"kind_warehouse_id\",\"address\",\"timezone\",\"contact_name\",\"contact_phone\",\"working_hours\",\"width\",\"height\",\"scheme_json\",\"status\",\"tenant_id\",\"created_at\",\"updated_at\",\"deleted_at\"]')");
    }
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS wms_warehouses_change ON wms.warehouses');
        Schema::table('wms.warehouses', function (Blueprint $table): void { $table->dropColumn('code'); });
    }
};

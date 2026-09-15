<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms.kind_warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('pgsql')->getConnection()->statement("CREATE UNIQUE INDEX wms_kind_warehouses_shortname_unique ON wms.kind_warehouses (shortname) WHERE deleted_at IS NULL AND shortname IS NOT NULL");
        DB::statement("CREATE TRIGGER wms_kind_warehouses_change AFTER INSERT OR UPDATE OR DELETE ON wms.kind_warehouses FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\KindWarehouse', '[\"name\",\"shortname\",\"status\",\"tenant_id\",\"created_at\",\"updated_at\",\"deleted_at\"]')");
        DB::statement("CREATE TRIGGER wms_kind_warehouses_truncate BEFORE TRUNCATE ON wms.kind_warehouses FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\\Models\\KindWarehouse')");
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.kind_warehouses');
    }
};

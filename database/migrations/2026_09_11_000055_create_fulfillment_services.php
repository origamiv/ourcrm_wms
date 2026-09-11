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
        Schema::create('wms.type_services', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1); $table->string('tenant_id')->nullable()->index();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('wms.services_ff', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1); $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('price', 15, 2)->nullable(); $table->unsignedBigInteger('type_service_ff')->nullable();
            $table->string('tenant_id')->nullable()->index(); $table->integer('is_visible')->default(1);
            $table->timestamps(); $table->softDeletes(); $table->index(['tenant_id', 'status']);
        });
        DB::table('wms.type_services')->insert([
            ['id' => 1, 'name' => 'Разовая', 'shortname' => 'one_time', 'status' => 1],
            ['id' => 2, 'name' => 'Периодическая', 'shortname' => 'recurring', 'status' => 1],
            ['id' => 3, 'name' => 'Для задачи', 'shortname' => 'task', 'status' => 1],
            ['id' => 4, 'name' => 'Для товаров в задаче', 'shortname' => 'task_goods', 'status' => 1],
            ['id' => 5, 'name' => 'Для FBS заказов', 'shortname' => 'fbs_orders', 'status' => 1],
        ]);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_type_services_change AFTER INSERT OR UPDATE OR DELETE ON wms.type_services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\TypeService', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_type_services_truncate BEFORE TRUNCATE ON wms.type_services FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\TypeService');
CREATE TRIGGER wms_services_ff_change AFTER INSERT OR UPDATE OR DELETE ON wms.services_ff FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\ServiceFf', '["name","shortname","status","unit_id","price","type_service_ff","tenant_id","is_visible","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_services_ff_truncate BEFORE TRUNCATE ON wms.services_ff FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\ServiceFf');
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.services_ff'); Schema::dropIfExists('wms.type_services');
        DB::unprepared("DELETE FROM public.entity_changes WHERE entity IN ('App\\Models\\ServiceFf','App\\Models\\TypeService');");
    }
};

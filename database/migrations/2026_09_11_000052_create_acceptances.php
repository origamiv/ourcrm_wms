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
        Schema::create('wms.type_acceptance', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('wms.acceptances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->integer('plan_count')->nullable();
            $table->integer('fact_count')->nullable();
            $table->integer('progress')->nullable();
            $table->unsignedBigInteger('type_acceptance_id')->nullable();
            $table->timestamps();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['client_id', 'warehouse_id']);
        });
        DB::table('wms.type_acceptance')->insert([
            ['id' => 1, 'name' => 'Сканирование', 'shortname' => 'scanning', 'status' => 1],
            ['id' => 2, 'name' => 'Ручная', 'shortname' => 'manual', 'status' => 1],
        ]);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_type_acceptance_change AFTER INSERT OR UPDATE OR DELETE ON wms.type_acceptance FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\TypeAcceptance', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_type_acceptance_truncate BEFORE TRUNCATE ON wms.type_acceptance FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\TypeAcceptance');
CREATE TRIGGER wms_acceptances_change AFTER INSERT OR UPDATE OR DELETE ON wms.acceptances FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Acceptance', '["client_id","warehouse_id","task_id","plan_count","fact_count","progress","type_acceptance_id","started_at","finished_at","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_acceptances_truncate BEFORE TRUNCATE ON wms.acceptances FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Acceptance');
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('wms.acceptances');
        Schema::dropIfExists('wms.type_acceptance');
        DB::unprepared("DELETE FROM public.entity_changes WHERE entity IN ('App\\Models\\Acceptance','App\\Models\\TypeAcceptance');");
    }
};

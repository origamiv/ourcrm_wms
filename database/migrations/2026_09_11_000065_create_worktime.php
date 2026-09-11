<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('main.worktime', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('tenant_id'); $table->date('work_date');
            $table->string('event_type', 20); $table->timestampTz('event_at'); $table->timestamps(); $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('public.users')->restrictOnDelete();
            $table->index(['tenant_id', 'work_date']); $table->index(['user_id', 'event_at']);
        });
        DB::statement("ALTER TABLE main.worktime ADD CONSTRAINT worktime_event_type_check CHECK (event_type IN ('start','pause_start','pause_end','finish'))");
        DB::unprepared("CREATE TRIGGER wms_worktime_change AFTER INSERT OR UPDATE OR DELETE ON main.worktime FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\Worktime', '[\"user_id\",\"tenant_id\",\"work_date\",\"event_type\",\"event_at\",\"created_at\",\"updated_at\",\"deleted_at\"]');");
    }
    public function down(): void { DB::unprepared('DROP TRIGGER IF EXISTS wms_worktime_change ON main.worktime'); Schema::dropIfExists('main.worktime'); DB::table('public.entity_changes')->where('entity', 'App\\Models\\Worktime')->delete(); }
};

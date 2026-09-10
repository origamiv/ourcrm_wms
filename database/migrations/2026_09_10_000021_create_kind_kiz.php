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
        Schema::create('goods.kind_kiz', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_kind_kiz_change AFTER INSERT OR UPDATE OR DELETE ON goods.kind_kiz FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\KindKiz', '["name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_kind_kiz_truncate BEFORE TRUNCATE ON goods.kind_kiz FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\KindKiz');
SQL);
        foreach (['Серийный номер', 'Честный знак', 'IMEI', 'УИН'] as $name) {
            DB::table('goods.kind_kiz')->insert(['name' => $name, 'tenant_id' => null, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::drop('goods.kind_kiz');
        DB::unprepared(<<<'SQL'
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\KindKiz' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\KindKiz';
SQL);
    }
};

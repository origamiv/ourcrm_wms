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
        Schema::create('goods.kizes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->foreignId('kind_kiz_id')->nullable()->constrained('goods.kind_kiz')->restrictOnDelete();
            $table->foreignId('good_id')->nullable()->constrained('goods.goods')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients.clients')->restrictOnDelete();
            $table->index('client_id');
            $table->dateTime('entranced_at')->nullable();
            $table->dateTime('leaving_at')->nullable();
            $table->dateTime('printed_at')->nullable();
            $table->index('kind_kiz_id');
            $table->index('good_id');
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::unprepared(<<<'SQL'
CREATE TRIGGER wms_kizes_change AFTER INSERT OR UPDATE OR DELETE ON goods.kizes FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Kiz', '["code", "client_id", "kind_kiz_id", "good_id", "entranced_at", "leaving_at", "printed_at", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_kizes_truncate BEFORE TRUNCATE ON goods.kizes FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Kiz');
SQL);

    }

    public function down(): void
    {
        Schema::drop('goods.kizes');
        DB::unprepared(<<<'SQL'
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\Kiz' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\Kiz';
SQL);
    }
};

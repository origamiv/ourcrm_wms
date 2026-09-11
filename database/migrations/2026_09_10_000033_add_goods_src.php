<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods.goods', function ($table): void {
            $table->json('src')->nullable();
        });
        $fields = json_encode(array_values(array_diff(config('sync.entities.goods.fields'), ['id'])));
        DB::statement('DROP TRIGGER IF EXISTS wms_goods_change ON goods.goods');
        DB::unprepared("CREATE TRIGGER wms_goods_change AFTER INSERT OR UPDATE OR DELETE ON goods.goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\\Models\\Good', '".$fields."')");
        DB::statement('UPDATE goods.goods SET src = src');
    }

    public function down(): void
    {
        throw new RuntimeException('Откат удаления src из goods.goods требует сохранения пользовательских свойств.');
    }
};

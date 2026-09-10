<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement('LOCK TABLE goods.goods IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('ALTER TABLE goods.goods ADD COLUMN category_manual boolean NOT NULL DEFAULT false, ADD COLUMN has_children boolean NOT NULL DEFAULT false');
        DB::statement('UPDATE goods.goods g SET category_manual = true WHERE is_category = 1 AND NOT EXISTS (SELECT 1 FROM goods.goods c WHERE c.parent_id = g.id AND c.deleted_at IS NULL)');
        DB::unprepared(file_get_contents(database_path('sql/goods_manual_category.sql')));
        $fields = json_encode(array_values(array_diff(config('sync.entities.goods.fields'), ['id'])));
        DB::statement('DROP TRIGGER wms_goods_change ON goods.goods');
        DB::unprepared("CREATE TRIGGER wms_goods_change AFTER INSERT OR UPDATE OR DELETE ON goods.goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Good', '".$fields."')");
        // Re-publish all goods so existing cached leaves also receive has_children=false.
        DB::statement('UPDATE goods.goods SET has_children = has_children');
    }

    public function down(): void
    {
        throw new RuntimeException('Откат удалит выбор пустых категорий; требуется сохранение ручных признаков перед откатом.');
    }
};

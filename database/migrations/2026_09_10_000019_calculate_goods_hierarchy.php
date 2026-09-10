<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '120s'");
        DB::statement('LOCK TABLE goods.goods IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('CREATE INDEX IF NOT EXISTS wms_goods_live_parent ON goods.goods (parent_id) WHERE deleted_at IS NULL');
        DB::statement('ALTER TABLE goods.goods ALTER COLUMN level SET DEFAULT 0');
        DB::unprepared(file_get_contents(database_path('sql/goods_hierarchy.sql')));
        DB::statement('SELECT wms.recalculate_goods_hierarchy()');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER wms_goods_hierarchy ON goods.goods');
        DB::statement('DROP TRIGGER wms_goods_hierarchy_lock ON goods.goods');
        DB::statement('DROP FUNCTION wms.capture_goods_hierarchy()');
        DB::statement('DROP FUNCTION wms.lock_goods_hierarchy()');
        DB::statement('DROP FUNCTION wms.recalculate_goods_hierarchy()');
        DB::statement('DROP INDEX goods.wms_goods_live_parent');
        DB::statement('ALTER TABLE goods.goods ALTER COLUMN level DROP DEFAULT');
    }
};

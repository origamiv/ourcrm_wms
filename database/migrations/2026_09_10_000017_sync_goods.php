<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '60s'");
        DB::unprepared(file_get_contents(database_path('sql/goods_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
LOCK TABLE goods.goods, goods.good_cards, goods.type_goods, goods.unit_goods IN SHARE ROW EXCLUSIVE MODE;
DROP TRIGGER wms_goods_change ON goods.goods;
DROP TRIGGER wms_goods_truncate ON goods.goods;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\Good' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\Good';
DROP TRIGGER wms_good_cards_change ON goods.good_cards;
DROP TRIGGER wms_good_cards_truncate ON goods.good_cards;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\GoodCard' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\GoodCard';
DROP TRIGGER wms_type_goods_change ON goods.type_goods;
DROP TRIGGER wms_type_goods_truncate ON goods.type_goods;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\GoodType' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\GoodType';
DROP TRIGGER wms_unit_goods_change ON goods.unit_goods;
DROP TRIGGER wms_unit_goods_truncate ON goods.unit_goods;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\GoodUnit' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\GoodUnit';

DROP FUNCTION wms.capture_good_card_change();
SQL);
    }
};

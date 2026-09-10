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
        DB::statement('LOCK TABLE wms.delivery_services IN ACCESS EXCLUSIVE MODE');
        if (DB::table('wms.delivery_services')->whereBetween('id', [1, 20])->exists()) {
            throw new RuntimeException('ID 1–20 служб доставки уже заняты. Автоматическая перезапись запрещена.');
        }
        Schema::table('wms.delivery_services', function (Blueprint $table) {
            $table->smallInteger('from_integration_only')->nullable()->default(0);
        });
        DB::unprepared(<<<'SQL'
DROP TRIGGER wms_delivery_services_change ON wms.delivery_services;
CREATE TRIGGER wms_delivery_services_change AFTER INSERT OR UPDATE OR DELETE ON wms.delivery_services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\DeliveryService', '["name","shortname","status","icon","color","is_order_edit","from_integration_only","prefix","folder","marketplace_id","tenant_id","created_at","updated_at","deleted_at"]');
UPDATE wms.delivery_services SET from_integration_only = from_integration_only;
SQL);
        $rows = [
            [1, 'Wildberries FBS', 0, 1, 1, 'WB', 'wb_stickers'],
            [2, 'OZON FBS', 0, 1, 1, 'OZ', 'ozon_stickers'],
            [3, 'СберМегаМаркет', 0, 2, 0, 'SMM', 'sber_stickers'],
            [4, 'ЛеруаМерлен', 0, 2, 0, 'LM', 'lerua_stickers'],
            [5, 'Курьер', 1, 1, 0, 'COURIER', 'orders_internal_labels'],
            [6, 'Wildberries FBO', 0, 2, 0, 'WB', 'wb_stickers'],
            [7, 'OZON FBO', 0, 2, 0, 'OZ', 'ozon_stickers'],
            [8, 'OZON Real FBS', 0, 1, 1, 'OZ', 'ozon_stickers'],
            [9, 'YandexMarket FBS', 0, 1, 1, 'YA', 'yandex_stickers'],
            [10, 'YandexMarket DBS', 0, 1, 1, 'YA', 'yandex_stickers'],
            [11, 'OZON FBP', 0, 1, 1, 'OZ', 'ozon_stickers'],
            [12, 'AliExpress', 0, 1, 1, 'Ali', 'ali_stickers'],
            [13, 'МВидео', 0, 1, 1, 'MVideo', 'mvideo_stickers'],
            [14, 'Yandex FBP', 0, 1, 1, 'YA', 'yandex_stickers'],
            [15, 'Сайт', 0, 1, 0, 'SITE', 'site_stickers'],
            [16, 'Далли', 0, 2, 0, 'DALLI', 'dalli_stickers'],
            [17, 'СДЭК', 0, 2, 0, 'CDEK', 'cdek_stickers'],
            [18, 'Почта РФ', 0, 2, 0, 'POSTRF', 'post_rf_stickers'],
            [19, 'Пятёрочка', 0, 2, 0, 'PYAT', 'pyaterochka_stickers'],
            [20, 'dubaiexpress.ru', 0, 1, 0, 'DUBAI', 'dubaiexpress_stickers'],
        ];
        foreach ($rows as [$id, $name, $edit, $status, $integration, $prefix, $folder]) {
            DB::table('wms.delivery_services')->insert([
                'id' => $id, 'name' => $name, 'is_order_edit' => $edit, 'status' => $status,
                'from_integration_only' => $integration, 'prefix' => $prefix, 'folder' => $folder,
                'tenant_id' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::statement("SELECT setval(pg_get_serial_sequence('wms.delivery_services', 'id'), GREATEST((SELECT MAX(id) FROM wms.delivery_services), (SELECT last_value FROM wms.delivery_services_id_seq)), true)");
    }

    public function down(): void
    {
        throw new RuntimeException('Автоматический откат заполненного справочника запрещён: записи могут использоваться.');
    }
};

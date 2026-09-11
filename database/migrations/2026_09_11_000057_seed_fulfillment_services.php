<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('wms.services_ff')->insert([
            ['id'=>1, 'name'=>'Обработка заказа', 'shortname'=>'order_processing', 'status'=>1, 'unit_id'=>21, 'price'=>3.50, 'type_service_ff'=>5, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>2, 'name'=>'Приемка перемещения', 'shortname'=>'transfer_in', 'status'=>1, 'unit_id'=>21, 'price'=>55.00, 'type_service_ff'=>3, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>3, 'name'=>'Транспортировка на склад', 'shortname'=>'transportation_to_warehouse', 'status'=>1, 'unit_id'=>21, 'price'=>20.00, 'type_service_ff'=>3, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>4, 'name'=>'Стандартный операционный сбор (обработка заказа)', 'shortname'=>'standard_order_processing_fee', 'status'=>1, 'unit_id'=>21, 'price'=>3.50, 'type_service_ff'=>2, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>5, 'name'=>'Инспекция', 'shortname'=>'inspection', 'status'=>1, 'unit_id'=>21, 'price'=>0.35, 'type_service_ff'=>2, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>6, 'name'=>'Приемка SKU', 'shortname'=>'sku_acceptance', 'status'=>1, 'unit_id'=>21, 'price'=>1.00, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>7, 'name'=>'Печать и наклейка штрихкодов товаров по запросу, маркировка', 'shortname'=>'on_demand_barcode_labeling', 'status'=>1, 'unit_id'=>21, 'price'=>0.35, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>8, 'name'=>'Отгрузка перемещения', 'shortname'=>'transfer_out', 'status'=>1, 'unit_id'=>21, 'price'=>60.00, 'type_service_ff'=>5, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>9, 'name'=>'Инспекция', 'shortname'=>'inspection_goods', 'status'=>1, 'unit_id'=>21, 'price'=>0.35, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>10, 'name'=>'Приемка SKU', 'shortname'=>'sku_acceptance_task', 'status'=>1, 'unit_id'=>21, 'price'=>1.00, 'type_service_ff'=>3, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>11, 'name'=>'Дополнительное хранение 14–30 дней', 'shortname'=>'extra_storage_14_30_days', 'status'=>1, 'unit_id'=>15, 'price'=>14.50, 'type_service_ff'=>2, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>12, 'name'=>'Дополнительное хранение 30–45 дней', 'shortname'=>'extra_storage_30_45_days', 'status'=>1, 'unit_id'=>15, 'price'=>54.50, 'type_service_ff'=>2, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>13, 'name'=>'Обработка возвратов (FBS)', 'shortname'=>'fbs_returns_processing', 'status'=>1, 'unit_id'=>21, 'price'=>2.75, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>14, 'name'=>'Приемка перемещения (BoE)', 'shortname'=>'transfer_in_boe', 'status'=>1, 'unit_id'=>21, 'price'=>55.00, 'type_service_ff'=>1, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>15, 'name'=>'Обработка возвратов (Tanais)', 'shortname'=>'tanais_returns_processing', 'status'=>1, 'unit_id'=>21, 'price'=>null, 'type_service_ff'=>1, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>16, 'name'=>'Отгрузка перемещения', 'shortname'=>'transfer_out_regular', 'status'=>1, 'unit_id'=>21, 'price'=>60.00, 'type_service_ff'=>1, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>17, 'name'=>'Обработка возвратов (FBP)', 'shortname'=>'fbp_returns_processing', 'status'=>1, 'unit_id'=>21, 'price'=>2.25, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>18, 'name'=>'Размещение на хранение (FBP)', 'shortname'=>'fbp_putaway', 'status'=>1, 'unit_id'=>21, 'price'=>0.15, 'type_service_ff'=>3, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>19, 'name'=>'Нанесение наклейки, карточки', 'shortname'=>'sticker_card_application', 'status'=>1, 'unit_id'=>21, 'price'=>0.15, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>20, 'name'=>'Повторная упаковка', 'shortname'=>'reseal', 'status'=>1, 'unit_id'=>21, 'price'=>2.50, 'type_service_ff'=>1, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>21, 'name'=>'Подготовка товара к выдаче со склада', 'shortname'=>'goods_preparation_for_withdrawal', 'status'=>1, 'unit_id'=>21, 'price'=>2.00, 'type_service_ff'=>4, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>22, 'name'=>'Перемещение Jafza — Dafza', 'shortname'=>'jafza_dafza_transfer', 'status'=>1, 'unit_id'=>21, 'price'=>475.00, 'type_service_ff'=>3, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['id'=>23, 'name'=>'Перемещение от локального поставщика', 'shortname'=>'transfer_from_local', 'status'=>1, 'unit_id'=>21, 'price'=>200.00, 'type_service_ff'=>3, 'is_visible'=>1, 'created_at'=>$now, 'updated_at'=>$now],
        ]);
        DB::statement("SELECT setval(pg_get_serial_sequence('wms.services_ff', 'id'), (SELECT MAX(id) FROM wms.services_ff), true)");
    }

    public function down(): void
    {
        DB::table('wms.services_ff')->whereBetween('id', [1, 23])->delete();
    }
};

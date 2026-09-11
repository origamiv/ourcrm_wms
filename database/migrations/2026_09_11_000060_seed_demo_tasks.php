<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenant = DB::table('clients.clients')->whereNotNull('tenant_id')->orderBy('id')->value('tenant_id');
        if (! $tenant) {
            return;
        }
        $client = DB::table('clients.clients')->where('tenant_id', $tenant)->orderBy('id')->value('id')
            ?? DB::table('clients.clients')->whereNull('tenant_id')->orderBy('id')->value('id');
        $user = DB::table('public.users')->where('tenant_id', $tenant)->orderBy('id')->value('id');
        $warehouse = DB::table('wms.warehouses')->whereNull('deleted_at')->orderBy('id')->value('id');
        if (! $client || ! $user || ! $warehouse) {
            return;
        }

        $now = now();
        $rows = [
            [1001, 'Приемка товаров', 'receiving_goods', 1, 1, 3, 8, 56, 1, 35, '2026-09-11 08:00:00', '2026-09-11 08:30:00', null, '2026-09-11 10:00:00', 1250.00, null, 'Проверить поставку и разместить принятые товары.', 'Приоритетная приемка для утренней поставки.', ['goods' => [1, 1101], 'goods_count' => 2, 'pieces_count' => 56, 'total_pieces' => 160, 'progress' => 35, 'supplier' => 'ООО Ромашка']],
            [1002, 'Размещение на хранение', 'putaway_goods', 2, 2, 2, 5, 120, null, null, '2026-09-12 09:00:00', null, null, null, 980.00, null, 'Разместить товары по ячейкам склада.', 'Использовать зону Основная.', ['goods' => [1, 1781, 1101], 'goods_count' => 3, 'pieces_count' => 120, 'total_pieces' => 120, 'warehouse_zone' => 'Основная']],
            [1003, 'Комплектация заказа клиента', 'pick_customer_order', 3, 3, 1, 6, 42, null, null, null, null, null, null, 630.50, null, 'Собрать заказ из трех SKU.', 'Проверить сроки годности перед передачей на упаковку.', ['goods' => [1, 1782, 1834], 'goods_count' => 3, 'pieces_count' => 42, 'total_pieces' => 42, 'order_number' => 'ORD-2026-0042']],
            [1004, 'Упаковка заказа', 'pack_customer_order', 4, 4, 3, 4, 18, 2, 60, '2026-09-11 07:45:00', '2026-09-11 08:10:00', null, '2026-09-11 09:00:00', 315.00, null, 'Упаковать собранные товары и подготовить этикетки.', 'Использовать усиленную упаковку.', ['goods' => [1101, 1835], 'goods_count' => 2, 'pieces_count' => 18, 'total_pieces' => 30, 'progress' => 60, 'package_type' => 'Коробка M']],
            [1005, 'Перемещение между складами', 'transfer_between_warehouses', 7, 5, 2, 7, 75, null, null, '2026-09-13 12:00:00', null, null, null, 4500.00, null, 'Переместить остатки в резервную зону.', 'Согласовать транспорт и маршрут.', ['goods' => [1, 2, 1101], 'goods_count' => 3, 'pieces_count' => 75, 'total_pieces' => 75, 'from_warehouse_id' => 1, 'to_warehouse_id' => 1]],
            [1006, 'Инвентаризация ячеек', 'warehouse_inventory', 6, 6, 4, 3, 240, 3, 100, '2026-09-10 09:00:00', '2026-09-10 09:30:00', '2026-09-10 14:00:00', '2026-09-10 14:05:00', 0.00, '2026-09-10 14:10:00', 'Провести пересчет остатков по ячейкам.', 'Расхождения зафиксированы в отчете инвентаризации.', ['goods' => [1, 2, 3, 1101], 'goods_count' => 4, 'pieces_count' => 240, 'total_pieces' => 240, 'progress' => 100, 'variance' => 0]],
            [1007, 'Отгрузка FBS заказа', 'ship_fbs_order', 5, 7, 5, 9, 12, null, null, '2026-09-14 15:00:00', null, null, null, 210.00, null, 'Подготовить FBS заказ к передаче службе доставки.', 'Проверить ШК и вложить сопроводительные документы.', ['goods' => [1834, 1836], 'goods_count' => 2, 'pieces_count' => 12, 'total_pieces' => 12, 'marketplace' => 'Wildberries FBS', 'shipment_number' => 'WB-2026-0914-07']],
        ];

        $payload = array_map(static function (array $row) use ($client, $tenant, $user, $warehouse, $now): array {
            [$id, $name, $shortname, $type, $statusId, $priority, $status, $fact, $unused, $progress, $planned, $started, $completed, $chargedAt, $chargedSum, $confirmedAt, $comment, $internal, $src] = $row;
            return [
                'id' => $id,
                'name' => $name,
                'shortname' => $shortname,
                'client_id' => $client,
                'task_type_id' => $type,
                'status_id' => $statusId,
                'planned_at' => $planned,
                'started_at' => $started,
                'completed_at' => $completed,
                'charged_at' => $chargedAt,
                'charged_sum' => $chargedSum,
                'confirmed_at' => $confirmedAt,
                'comment' => $comment,
                'internal_comment' => $internal,
                'priority_id' => $priority,
                'src' => json_encode($src, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'order_id' => null,
                'warehouse_id' => $warehouse,
                'user_id' => $user,
                'created_by_user_id' => $user,
                'fact_count' => $fact,
                'status' => 1,
                'tenant_id' => $tenant,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $rows);

        DB::table('wms.tasks')->whereIn('id', array_column($payload, 'id'))->delete();
        DB::table('wms.tasks')->insert($payload);
        DB::statement("SELECT setval(pg_get_serial_sequence('wms.tasks', 'id'), (SELECT MAX(id) FROM wms.tasks), true)");
    }

    public function down(): void
    {
        DB::table('wms.tasks')->whereBetween('id', [1001, 1007])->delete();
    }
};

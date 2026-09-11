<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryService;
use App\Models\Marketplace;
use App\Models\TypeWarehouse;
use App\Models\TypeStorage;
use App\Models\Zone;
use App\Models\Cell;
use App\Models\CellGood;
use App\Models\Good;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class FulfillmentCatalogService
{
    public function save(User $actor, string $catalog, array $data, ?string $id = null, bool $delete = false): array
    {
        abort_unless(in_array($catalog, ['marketplaces', 'delivery_services', 'warehouses', 'type_warehouses', 'type_storage', 'zones', 'cells', 'cell_goods', 'task_types', 'task_statuses', 'priorities'], true), 404);

        return DB::transaction(function () use ($actor, $catalog, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $model = match ($catalog) {
                'marketplaces' => Marketplace::class,
                'delivery_services' => DeliveryService::class,
                'warehouses' => Warehouse::class,
                'type_warehouses' => TypeWarehouse::class,
                'type_storage' => TypeStorage::class,
                'zones' => Zone::class,
                'cells' => Cell::class,
                'cell_goods' => CellGood::class,
                'task_types' => \App\Models\TaskType::class,
                'task_statuses' => \App\Models\TaskStatus::class,
                'priorities' => \App\Models\Priority::class,
            };
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, $model, $id);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $row = $id ? $model::withTrashed()->visibleTo($tenant)->findOrFail($id) : new $model;
            if ($id) {
                $current = $sync->current($model, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($row->trashed(), 422, 'Запись уже удалена.');
            }
            if ($delete) {
                if ($catalog === 'marketplaces') {
                    abort_if(DeliveryService::where('marketplace_id', $id)->exists(), 422, 'Маркетплейс используется в службах доставки.');
                }
                if ($catalog === 'type_warehouses') {
                    abort_if(Warehouse::where('type_warehouse_id', $id)->exists(), 422, 'Тип склада используется в складах.');
                }
                $row->delete();
            } else {
                if ($catalog === 'delivery_services') {
                    $marketplace = array_key_exists('marketplace_id', $data) ? $data['marketplace_id'] : $row->marketplace_id;
                    if ($marketplace !== null && ! Marketplace::visibleTo($tenant)->whereKey($marketplace)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['marketplace_id' => 'Выберите доступный маркетплейс.']);
                    }
                }
                if ($catalog === 'warehouses') {
                    $type = array_key_exists('type_warehouse_id', $data) ? $data['type_warehouse_id'] : $row->type_warehouse_id;
                    if ($type !== null && ! TypeWarehouse::visibleTo($tenant)->whereKey($type)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['type_warehouse_id' => 'Выберите доступный тип склада.']);
                    }
                }
                if ($catalog === 'cells') {
                    $warehouse = array_key_exists('warehouse_id', $data) ? $data['warehouse_id'] : $row->warehouse_id;
                    $zone = array_key_exists('zone_id', $data) ? $data['zone_id'] : $row->zone_id;
                    if (! Warehouse::visibleTo($tenant)->whereKey($warehouse)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['warehouse_id' => 'Выберите доступный склад.']);
                    }
                    if ($zone !== null && ! Zone::visibleTo($tenant)->whereKey($zone)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['zone_id' => 'Выберите доступную зону.']);
                    }
                }
                if ($catalog === 'cell_goods') {
                    $warehouse = (int) ($data['warehouse_id'] ?? $row->warehouse_id);
                    $cell = Cell::visibleTo($tenant)->whereKey($data['cell_id'] ?? $row->cell_id)->first();
                    $goodId = $data['good_id'] ?? $row->good_id;
                    if (! Warehouse::visibleTo($tenant)->whereKey($warehouse)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['warehouse_id' => 'Выберите доступный склад.']);
                    }
                    if (! $cell || (int) $cell->warehouse_id !== $warehouse) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['cell_id' => 'Выберите ячейку выбранного склада.']);
                    }
                    if ($goodId === null || ! Good::visibleTo($tenant)->whereKey($goodId)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['good_id' => 'Выберите доступный товар.']);
                    }
                    if (array_key_exists('user_id', $data) && $data['user_id'] !== null && ! User::visibleTo($tenant)->whereKey($data['user_id'])->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'Выберите доступного пользователя.']);
                    }
                }
                $row->forceFill(array_intersect_key($data, array_flip(array_diff(config('sync.entities.'.$catalog.'.fields'), ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']))));
                if (! $id && ! in_array($catalog, ['task_types','task_statuses','priorities'], true)) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryService;
use App\Models\Marketplace;
use App\Models\TypeWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class FulfillmentCatalogService
{
    public function save(User $actor, string $catalog, array $data, ?string $id = null, bool $delete = false): array
    {
        abort_unless(in_array($catalog, ['marketplaces', 'delivery_services', 'warehouses', 'type_warehouses'], true), 404);

        return DB::transaction(function () use ($actor, $catalog, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $model = match ($catalog) {
                'marketplaces' => Marketplace::class,
                'delivery_services' => DeliveryService::class,
                'warehouses' => Warehouse::class,
                'type_warehouses' => TypeWarehouse::class,
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
                $row->forceFill(array_intersect_key($data, array_flip(array_diff(config('sync.entities.'.$catalog.'.fields'), ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']))));
                if (! $id) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

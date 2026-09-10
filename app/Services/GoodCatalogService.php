<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Good;
use App\Models\GoodCard;
use App\Models\GoodType;
use App\Models\GoodUnit;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class GoodCatalogService
{
    public function save(User $actor, string $catalog, array $data, ?string $id = null, bool $delete = false): array
    {
        abort_unless(in_array($catalog, ['type_goods', 'unit_goods'], true), 404);

        return DB::transaction(function () use ($actor, $catalog, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $model = $catalog === 'type_goods' ? GoodType::class : GoodUnit::class;
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
                abort_if(Good::where($catalog === 'type_goods' ? 'type_good' : 'type_unit', $id)->exists() || ($catalog === 'unit_goods' && GoodCard::where('unit_id', $id)->exists()), 422, 'Запись используется в товарах или карточках.');
                $row->delete();
            } else {
                $row->forceFill(array_intersect_key($data, array_flip(['name', 'shortname', 'status'])));
                if (! $id) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

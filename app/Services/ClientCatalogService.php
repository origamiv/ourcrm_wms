<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\ClientService;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClientCatalogService
{
    public function save(User $actor, string $catalog, array $data, ?string $id = null, bool $delete = false): array
    {
        abort_unless(in_array($catalog, ['services', 'accounts'], true), 404);

        return DB::transaction(function () use ($actor, $catalog, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $model = $catalog === 'services' ? ClientService::class : ClientAccount::class;
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
                $row->delete();
            } else {
                $clientId = $data['client_id'] ?? $row->client_id;
                if (! Client::visibleTo($tenant)->whereKey($clientId)->exists()) {
                    throw ValidationException::withMessages(['client_id' => 'Выберите доступного клиента.']);
                }
                $fields = array_diff(config('sync.entities.client_'.$catalog.'.fields'), ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']);
                $row->forceFill(array_intersect_key($data, array_flip($fields)));
                if (! $id) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

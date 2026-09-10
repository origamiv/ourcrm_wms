<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class ClientService
{
    public function save(User $actor, array $data, ?string $id = null, bool $delete = false): array
    {
        return DB::transaction(function () use ($actor, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $client = $id ? Client::withTrashed()->where('tenant_id', $tenant)->findOrFail($id) : new Client;
            if ($id) {
                $current = $sync->current(Client::class, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($client->trashed(), 422, 'Клиент уже удалён.');
            }
            if ($delete) {
                $client->delete();
            } else {
                $client->forceFill(array_intersect_key($data, array_flip(['name', 'shortname', 'status'])));
                if (! $id) {
                    $client->tenant_id = $tenant;
                }
                $client->save();
            }

            return $sync->current(Client::class, $tenant, $client->id);
        }, 3);
    }
}

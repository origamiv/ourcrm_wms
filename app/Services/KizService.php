<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Good;
use App\Models\KindKiz;
use App\Models\Kiz;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class KizService
{
    public function save(User $actor, array $data, ?string $id = null, bool $delete = false): array
    {
        return DB::transaction(function () use ($actor, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $model = Kiz::class;
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
                foreach (['client_id' => \App\Models\Client::class, 'good_id' => Good::class, 'kind_kiz_id' => KindKiz::class] as $field => $related) {
                    $value = array_key_exists($field, $data) ? $data[$field] : $row->{$field};
                    if ($value !== null && ! $related::visibleTo($tenant)->whereKey($value)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([$field => 'Выберите доступную запись.']);
                    }
                }
                $row->forceFill(array_intersect_key($data, array_flip(['code', 'client_id', 'kind_kiz_id', 'good_id', 'entranced_at', 'leaving_at', 'printed_at'])));
                if (! $id) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

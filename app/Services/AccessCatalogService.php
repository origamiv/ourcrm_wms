<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AccessCatalogService
{
    public function deleteRole(User $actor, string $id, string $version): array
    {
        return DB::transaction(function () use ($actor, $id, $version) {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, \App\Models\Role::class, $id);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $role = \App\Models\Role::withTrashed()->visibleTo($tenant)->findOrFail($id);
            $current = $sync->current(\App\Models\Role::class, $tenant, $id);
            if (! hash_equals($current['version'], $version)) {
                throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
            }
            abort_if($role->system || $role->slug === 'admin', 422, 'Системную роль и роль admin удалять нельзя.');
            abort_if($role->trashed(), 422, 'Роль уже удалена.');
            $role->delete();

            return $sync->current(\App\Models\Role::class, $tenant, $id);
        }, 3);
    }

    public function save(User $actor, string $catalog, array $data, ?string $id = null): array
    {
        abort_unless(in_array($catalog, ['roles', 'permissions'], true), 404);

        return DB::transaction(function () use ($actor, $catalog, $data, $id) {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, config('sync.entities.'.$catalog.'.entity'), $id);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $definition = app(SyncEntityRegistry::class)->resolve($catalog, $actor);
            $model = $definition['entity'];
            $record = $id ? $model::withTrashed()->visibleTo($tenant)->findOrFail($id) : new $model;
            if ($id) {
                $current = $sync->current($model, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($record->trashed(), 422, 'Удалённую запись нельзя редактировать.');
                if ($record->system || ($catalog === 'roles' && $record->slug === 'admin')) {
                    if ($data['slug'] !== $record->slug || ($catalog === 'permissions' && ($data['status'] === null ? null : (int) $data['status']) !== $record->status) || ($catalog === 'permissions' && $data['resource'] !== $record->resource)) {
                        throw ValidationException::withMessages(['slug' => 'Код системной записи или роли admin, а также ресурс и статус системного права изменять нельзя.']);
                    }
                }
            }
            if ($id && $catalog === 'roles' && $record->slug === 'admin' && (int) $data['status'] === 2) {
                $otherRole = app(AccessService::class)->adminAssignments($tenant)->where('ru.user_id', $actor->id)->where('ru.role_id', '<>', $record->id)->exists();
                abort_unless($otherRole, 422, 'Нельзя отключить роль, обеспечивающую ваш административный доступ.');
                $foreignAssignments = DB::table('main.role_user')->where('role_id', $record->id)->where('status', 1)->whereNull('deleted_at')->where('tenant_id', '<>', $tenant)->exists();
                abort_if($foreignAssignments, 422, 'Роль используется администраторами другой организации.');
            }
            if ($catalog === 'roles' && $data['slug'] === 'admin' && (! $id || $record->slug !== 'admin')) {
                throw ValidationException::withMessages(['slug' => 'Код admin зарезервирован.']);
            }
            if ($model::withTrashed()->where('tenant_id', $id ? $record->tenant_id : $tenant)->where('slug', $data['slug'])->when($id, fn ($query) => $query->where('id', '<>', $id))->exists()) {
                throw ValidationException::withMessages(['slug' => 'Этот код уже используется в организации.']);
            }
            $fields = ['name', 'slug', 'status', $catalog === 'roles' ? 'description' : 'resource'];
            $record->forceFill(array_intersect_key($data, array_flip($fields)));
            if (! $id) {
                $record->forceFill(['tenant_id' => $tenant, 'system' => false]);
            }
            $record->save();

            return $sync->current($model, $tenant, $record->id);
        }, 3);
    }
}

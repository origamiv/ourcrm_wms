<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Feature;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReferenceService
{
    public function save(User $actor, string $type, array $data, ?string $id = null, bool $delete = false): array
    {
        abort_unless(in_array($type, ['modules', 'features', 'icons', 'files'], true), 404);

        return DB::transaction(function () use ($actor, $type, $data, $id, $delete) {
            $definition = app(SyncEntityRegistry::class)->resolve($type, $actor);
            $tenant = $actor->tenant_id;
            $partition = $definition['global'] ? null : $tenant;
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, $definition['entity'], $id);
            $sync->checkpoint($partition);
            DB::table('public.sync_state')->where('tenant_id', $partition)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $model = $definition['entity'];
            $query = $model::withTrashed();
            $query->visibleTo($tenant);
            $row = $id ? $query->findOrFail($id) : new $model;
            if ($id) {
                $current = $sync->current($model, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($row->trashed(), 422, 'Запись уже удалена.');
            }
            if ($delete) {
                if ($type === 'modules') {
                    abort_if(Feature::where('module_id', $id)->exists() || DB::table('main.permissions')->where('module_id', $id)->whereNull('deleted_at')->exists(), 422, 'Модуль используется в возможностях или правах доступа.');
                }
                if ($type === 'features') {
                    abort_if(DB::table('main.permissions')->where('feature_id', $id)->whereNull('deleted_at')->exists(), 422, 'Возможность используется в правах доступа.');
                }
                $row->delete();
            } else {
                foreach (['module_id' => Module::class, 'company_id' => Company::class, 'user_id' => User::class] as $field => $related) {
                    $value = $data[$field] ?? null;
                    if ($value !== null) {
                        $relatedQuery = $related::query();
                        $relatedQuery->visibleTo($tenant);
                        if (! $relatedQuery->whereKey($value)->exists()) {
                            throw ValidationException::withMessages([$field => 'Связанная запись недоступна.']);
                        }
                    }
                }
                $fields = array_diff($definition['fields'], ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']);
                $row->forceFill(array_intersect_key($data, array_flip($fields)));
                if (! $id && ! $definition['global']) {
                    $row->tenant_id = $tenant;
                    $row->user_id = $data['user_id'] ?? $actor->id;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

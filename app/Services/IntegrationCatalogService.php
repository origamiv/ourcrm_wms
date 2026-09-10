<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntegrationData;
use App\Models\IntegrationRule;
use App\Models\IntegrationWebhook;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class IntegrationCatalogService
{
    public function show(User $actor, string $catalog, string $id): array
    {
        abort_unless(app(AccessService::class)->isAdmin($actor), 403);
        $definition = IntegrationDefinition::get($catalog);

        return DB::transaction(function () use ($actor, $definition, $id) {
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($actor->tenant_id, $definition['model'], $id);
            DB::table('public.sync_state')->where('tenant_id', $actor->tenant_id)->sharedLock()->firstOrFail();
            $row = $definition['model']::withTrashed()->visibleTo($actor->tenant_id)->findOrFail($id);

            return ['data' => $sync->current($definition['model'], $actor->tenant_id, $id), 'details' => $row->only($definition['details'])];
        });
    }

    public function save(User $actor, string $catalog, array $data, ?string $id = null, bool $delete = false): array
    {
        $definition = IntegrationDefinition::get($catalog);

        return DB::transaction(function () use ($actor, $catalog, $definition, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $model = $definition['model'];
            $sync = app(EntitySyncService::class);
            $sync->prepareWrite($tenant, $model, $id);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $row = $id ? $model::withTrashed()->visibleTo($tenant)->lockForUpdate()->findOrFail($id) : new $model;
            if ($id) {
                $current = $sync->current($model, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($row->trashed(), 422, 'Запись уже удалена.');
            }
            if ($delete) {
                $used = match ($catalog) {
                    'services' => IntegrationWebhook::where('service_id', $id)->exists() || IntegrationData::where('service_id', $id)->exists(),
                    'webhooks' => IntegrationData::where('webhook_id', $id)->exists(),
                    'type_hook' => IntegrationWebhook::where('type_hook_id', $id)->exists(),
                    'type_processing' => IntegrationRule::where('type_processing_id', $id)->exists(),
                    'rules' => IntegrationWebhook::whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements_text(CASE WHEN jsonb_typeof(rules_id) = 'array' THEN rules_id ELSE '[]'::jsonb END) AS rule_id(value) WHERE value = ?)", [$id])->exists(),
                    default => false,
                };
                abort_if($used, 422, 'Запись используется в интеграциях.');
                $row->delete();
            } else {
                foreach ($definition['links'] as $field => $related) {
                    $value = array_key_exists($field, $data) ? $data[$field] : $row->{$field};
                    if ($value !== null && ! $related::visibleTo($tenant)->whereKey($value)->exists()) {
                        throw ValidationException::withMessages([$field => 'Выберите доступную запись.']);
                    }
                }
                if ($catalog === 'webhooks') {
                    foreach ((array_key_exists('rules_id', $data) ? ($data['rules_id'] ?? []) : ($row->rules_id ?? [])) as $rule) {
                        if (! IntegrationRule::visibleTo($tenant)->whereKey($rule)->exists()) {
                            throw ValidationException::withMessages(['rules_id' => 'Выберите доступные правила.']);
                        }
                    }
                }
                $row->forceFill(array_intersect_key($data, array_flip($definition['fields'])));
                if (! $id) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $tenant, $row->id);
        }, 3);
    }
}

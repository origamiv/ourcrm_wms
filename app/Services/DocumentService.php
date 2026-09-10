<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\DocType;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DocumentService
{
    public function save(User $actor, string $catalog, array $data, ?string $id = null, bool $delete = false): array
    {
        abort_unless(in_array($catalog, ['documents', 'doc_types'], true), 404);

        return DB::transaction(function () use ($actor, $catalog, $data, $id, $delete) {
            $tenant = $actor->tenant_id;
            $types = $catalog === 'doc_types';
            $partition = $types ? null : $tenant;
            $model = $types ? DocType::class : Document::class;
            $sync = app(EntitySyncService::class);
            // Lock the shared types before tenant documents to serialize type deletion with use.
            $sync->checkpoint(null);
            $typeState = DB::table('public.sync_state')->whereNull('tenant_id');
            ($types ? $typeState->lockForUpdate() : $typeState->sharedLock())->firstOrFail();
            if (! $types) {
                $sync->checkpoint($tenant);
                DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            }
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            $row = $id ? $model::withTrashed()->when(! $types, fn ($q) => $q->where('tenant_id', $tenant))->findOrFail($id) : new $model;
            if ($id) {
                $current = $sync->current($model, $partition, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($row->trashed(), 422, 'Запись уже удалена.');
            }
            if ($delete) {
                abort_if($types && Document::where('doc_type_id', $id)->exists(), 422, 'Тип используется в документах.');
                $row->delete();
            } else {
                if ($types && isset($data['settings']['print']['fields'])) {
                    foreach ($data['settings']['print']['fields'] as $field) {
                        if (($field['key'] === 'items') !== ($field['type'] === 'items')) {
                            throw ValidationException::withMessages(['settings' => 'Для позиций используйте ключ items и тип items.']);
                        }
                    }
                }
                if (! $types) {
                    if (! Client::where('tenant_id', $tenant)->whereKey($data['client_id'])->exists()) {
                        throw ValidationException::withMessages(['client_id' => 'Выберите клиента своей организации.']);
                    }
                    if (! DocType::whereKey($data['doc_type_id'])->exists()) {
                        throw ValidationException::withMessages(['doc_type_id' => 'Выберите существующий тип документа.']);
                    }
                    $candidate = new Document;
                    $candidate->forceFill(['tenant_id' => $tenant, 'client_id' => $data['client_id']]);
                    $parties = app(DocumentPartiesService::class);
                    foreach (['executor_id' => $parties->executors($candidate), 'customer_id' => $parties->customers($candidate)] as $field => $query) {
                        $value = array_key_exists($field, $data) ? $data[$field] : $row->{$field};
                        if ($value !== null && ! $query->whereKey($value)->exists()) {
                            throw ValidationException::withMessages([$field => $field === 'executor_id' ? 'Выберите компанию с признаком «Наша».' : 'Выберите юридическое лицо выбранного клиента.']);
                        }
                    }
                    foreach (['accepted_at', 'payed_at', 'canceled_at'] as $field) {
                        if (isset($data[$field])) {
                            $data[$field] = str_replace('T', ' ', $data[$field]).':00';
                        }
                    }
                }
                $fields = array_diff(config('sync.entities.client_'.$catalog.'.fields'), ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at']);
                $row->forceFill(array_intersect_key($data, array_flip($fields)));
                if (! $types && ! $id) {
                    $row->tenant_id = $tenant;
                }
                $row->save();
            }

            return $sync->current($model, $partition, $row->id);
        }, 3);
    }
}

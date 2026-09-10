<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompanyDirectoryService
{
    public function save(User $actor, string $directory, array $data, ?string $id = null, bool $delete = false, ?string $companyId = null): array
    {
        abort_unless(in_array($directory, ['companies', 'company_contacts'], true), 404);

        return DB::transaction(function () use ($actor, $directory, $data, $id, $delete, $companyId) {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            if ($companyId !== null) {
                abort_unless($directory === 'company_contacts', 404);
                Company::where('tenant_id', $tenant)->findOrFail($companyId);
                if (isset($data['company_id']) && (string) $data['company_id'] !== $companyId) {
                    throw ValidationException::withMessages(['company_id' => 'В этом разделе можно работать только с контактами выбранной компании.']);
                }
                $data['company_id'] = $companyId;
            }
            $definition = app(SyncEntityRegistry::class)->resolve($directory, $actor);
            $model = $definition['entity'];
            $record = $id ? $model::withTrashed()->where('tenant_id', $tenant)->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))->findOrFail($id) : new $model;
            if ($id) {
                $current = $sync->current($model, $tenant, $id);
                if (! hash_equals($current['version'], $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Загрузите актуальные данные.', 'current' => $current], 409));
                }
                abort_if($record->trashed(), 422, 'Запись уже удалена.');
            }
            if ($delete) {
                // Сохраняем контакты: сначала нужно удалить или перенести их в другую компанию.
                abort_if($directory === 'companies' && CompanyContact::where('company_id', $id)->exists(), 422, 'У компании есть контактные лица. Сначала удалите или перенесите их.');
                $record->delete();
            } else {
                if ($directory === 'company_contacts' && ! Company::where('tenant_id', $tenant)->whereKey($data['company_id'])->exists()) {
                    throw ValidationException::withMessages(['company_id' => 'Выберите существующую компанию своей организации.']);
                }
                $fields = array_diff($definition['fields'], ['id', 'tenant_id', 'created_at', 'updated_at', 'deleted_at', 'src']);
                $record->forceFill(array_intersect_key($data, array_flip($fields)));
                if ($directory === 'companies' && array_key_exists('src', $data)) {
                    abort_if($record->src !== null && ! is_array($record->src), 422, 'Содержимое дополнительных данных компании требует проверки.');
                    $record->src = array_replace($record->src ?? [], $data['src']);
                }
                if (! $id) {
                    $record->tenant_id = $tenant;
                }
                $record->save();
            }

            return $sync->current($model, $tenant, $record->id);
        }, 3);
    }
}

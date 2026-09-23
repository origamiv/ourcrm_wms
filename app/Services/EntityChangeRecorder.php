<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SyncPayloadProjector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

final class EntityChangeRecorder
{
    /** @var array<class-string<Model>, array<string, mixed>>|null */
    private ?array $definitions = null;

    /** @var array<string, bool> */
    private array $triggerCache = [];

    public function recordCreated(Model $model): void
    {
        $this->recordModelChange($model);
    }

    public function recordUpdated(Model $model): void
    {
        $definition = $this->definition($model::class);
        $oldId = (string) $model->getRawOriginal($model->getKeyName());
        $oldTenant = $this->tenantFrom($definition, $model->getRawOriginal('tenant_id'));
        $newId = (string) $model->getKey();
        $newTenant = $this->tenantFrom($definition, $model->getAttribute('tenant_id'));

        $this->withinTransaction(function () use ($model, $oldId, $oldTenant, $newId, $newTenant): void {
            if ($oldId !== $newId || $oldTenant !== $newTenant) {
                $this->append($model::class, $oldTenant, $oldId, 'remove');
            }
            $this->appendCurrent($model::class, $newId);
        }, $model::class, $newId);
    }

    public function recordDeleted(Model $model): void
    {
        $definition = $this->definition($model::class);
        $id = (string) $model->getKey();
        $tenant = $this->tenantFrom($definition, $model->getAttribute('tenant_id'));
        $forceDeleting = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();

        $this->withinTransaction(function () use ($model, $id, $tenant, $forceDeleting): void {
            if ($forceDeleting) {
                $this->append($model::class, $tenant, $id, 'remove');
            } else {
                $this->appendCurrent($model::class, $id);
            }
        }, $model::class, $id);
    }

    public function publishCurrent(string $entity, string|int $id): void
    {
        $this->withinTransaction(fn () => $this->appendCurrent($entity, (string) $id), $entity, (string) $id);
    }

    /** @param iterable<string|int> $ids */
    public function publishMany(string $entity, iterable $ids): void
    {
        $this->withinTransaction(function () use ($entity, $ids): void {
            foreach ($ids as $id) {
                $this->appendCurrent($entity, (string) $id);
            }
        }, $entity, '*');
    }

    public function remove(string $entity, ?string $tenant, string|int $id): void
    {
        $this->withinTransaction(fn () => $this->append($entity, $tenant, (string) $id, 'remove'), $entity, (string) $id);
    }

    public function initializeTenantShares(string $tenant): void
    {
        $this->withinTransaction(function () use ($tenant): void {
            $this->ensureState(null);
            DB::table('public.sync_state')->whereNull('tenant_id')->lockForUpdate()->firstOrFail();
            $this->ensureState($tenant);
            $state = DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            if (isset($state->shared_initialized) && $state->shared_initialized) {
                return;
            }

            $rows = DB::select(<<<'SQL'
SELECT * FROM (
    SELECT DISTINCT ON (entity, entity_id) entity, entity_id, operation, data
    FROM public.entity_changes
    WHERE tenant_id IS NULL
    ORDER BY entity, entity_id, revision DESC
) latest
WHERE operation = 'upsert'
ORDER BY entity, entity_id
SQL);
            foreach ($rows as $row) {
                if ($this->sharedVisible($row->entity, (string) $row->entity_id, $tenant)) {
                    $this->write($row->entity, $tenant, (string) $row->entity_id, 'upsert', $this->decodeJson($row->data));
                }
            }
            if (Schema::hasColumn('public.sync_state', 'shared_initialized')) {
                DB::table('public.sync_state')->where('tenant_id', $tenant)->update(['shared_initialized' => true]);
            }
        }, 'shared', $tenant);
    }

    public function refreshVisibility(string $entity, string|int $id): void
    {
        $this->withinTransaction(function () use ($entity, $id): void {
            $itemId = (string) $id;
            $this->ensureState(null);
            DB::table('public.sync_state')->whereNull('tenant_id')->lockForUpdate()->firstOrFail();
            $latest = DB::table('public.entity_changes')
                ->whereNull('tenant_id')
                ->where('entity', $entity)
                ->where('entity_id', $itemId)
                ->orderByDesc('revision')
                ->first();
            if (! $latest || $latest->operation !== 'upsert') {
                return;
            }
            foreach ($this->tenantStates() as $tenant) {
                $allowed = $this->sharedVisible($entity, $itemId, $tenant);
                $this->write($entity, $tenant, $itemId, $allowed ? 'upsert' : 'remove', $allowed ? $this->decodeJson($latest->data) : null);
            }
        }, $entity, (string) $id);
    }

    public function hasDatabaseTrigger(string $table): bool
    {
        if (array_key_exists($table, $this->triggerCache)) {
            return $this->triggerCache[$table];
        }
        if (! Schema::hasTable($table)) {
            return $this->triggerCache[$table] = false;
        }

        return $this->triggerCache[$table] = (bool) DB::selectOne(<<<'SQL'
SELECT EXISTS (
    SELECT 1
    FROM pg_trigger t
    JOIN pg_proc p ON p.oid = t.tgfoid
    WHERE (t.tgrelid = ?::regclass
      OR (? IN ('clients.services', 'integration.services')
          AND t.tgrelid = to_regclass('shared.services')
          AND p.proname = 'service_changed'))
      AND NOT t.tgisinternal
      AND p.proname IN (
        'capture_entity_change', 'capture_document_change', 'capture_good_card_change',
        'capture_user_with_roles_change', 'capture_role_user_change',
        'capture_tenant_entity_change', 'service_changed'
      )
) AS active
SQL, [$table, $table])->active;
    }

    /** @return array<class-string<Model>, array<string, mixed>> */
    public function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }
        $definitions = [];
        foreach (config('sync.entities', []) as $definition) {
            $entity = $definition['entity'];
            if (isset($definitions[$entity])) {
                throw new RuntimeException("Модель {$entity} повторно зарегистрирована для синхронизации.");
            }
            $definitions[$entity] = $definition;
        }

        return $this->definitions = $definitions;
    }

    private function recordModelChange(Model $model): void
    {
        $id = (string) $model->getKey();
        $this->withinTransaction(fn () => $this->appendCurrent($model::class, $id), $model::class, $id);
    }

    private function appendCurrent(string $entity, string $id): void
    {
        $definition = $this->definition($entity);
        $row = $this->rawRow($definition, $id);
        if ($row === null) {
            throw new RuntimeException("Не удалось сформировать событие {$entity}:{$id}: запись отсутствует.");
        }
        $tenant = $this->tenantFrom($definition, $row['tenant_id'] ?? null);
        $this->append($entity, $tenant, $id, 'upsert', $this->payload($definition, $row, $tenant));
    }

    /** @param array<string, mixed>|null $payload */
    private function append(string $entity, ?string $tenant, string $id, string $operation, ?array $payload = null): void
    {
        $this->ensureState(null);
        $global = DB::table('public.sync_state')->whereNull('tenant_id');
        ($tenant === null ? $global->lockForUpdate() : $global->sharedLock())->firstOrFail();
        $this->write($entity, $tenant, $id, $operation, $payload);
        if ($tenant !== null) {
            return;
        }
        foreach ($this->tenantStates() as $target) {
            $allowed = $operation === 'upsert' && $this->sharedVisible($entity, $id, $target);
            $this->write($entity, $target, $id, $allowed ? 'upsert' : 'remove', $allowed ? $payload : null);
        }
    }

    /** @param array<string, mixed>|null $payload */
    private function write(string $entity, ?string $tenant, string $id, string $operation, ?array $payload): void
    {
        $state = DB::selectOne(<<<'SQL'
INSERT INTO public.sync_state (tenant_id, revision)
VALUES (?, 1)
ON CONFLICT (tenant_id) DO UPDATE
SET revision = public.sync_state.revision + 1
RETURNING revision
SQL, [$tenant]);
        DB::table('public.entity_changes')->insert([
            'revision' => $state->revision,
            'tenant_id' => $tenant,
            'entity' => $entity,
            'entity_id' => $id,
            'operation' => $operation,
            'data' => $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    private function ensureState(?string $tenant): void
    {
        $state = DB::table('public.sync_state');
        $state = $tenant === null ? $state->whereNull('tenant_id') : $state->where('tenant_id', $tenant);
        if ($state->exists()) {
            return;
        }
        DB::statement('INSERT INTO public.sync_state (tenant_id) VALUES (?) ON CONFLICT (tenant_id) DO NOTHING', [$tenant]);
    }

    /** @return list<string> */
    private function tenantStates(): array
    {
        return DB::table('public.sync_state')->whereNotNull('tenant_id')->orderByRaw('tenant_id COLLATE "C"')->pluck('tenant_id')->map('strval')->all();
    }

    private function sharedVisible(string $entity, string $id, string $tenant): bool
    {
        if (in_array($entity, [\App\Models\Module::class, \App\Models\Feature::class, \App\Models\DocType::class], true)) {
            return true;
        }
        $assignments = DB::table('main.tenant_entity')->where('entity_type', $entity)->where('entity_id', $id);

        return ! (clone $assignments)->exists() || (clone $assignments)->where('tenant_id', $tenant)->exists();
    }

    /** @param array<string, mixed> $definition
     * @return array<string, mixed>|null
     */
    private function rawRow(array $definition, string $id): ?array
    {
        $table = $definition['table'];
        if (! preg_match('/^[a-z_][a-z0-9_]*\.[a-z_][a-z0-9_]*$/', $table)) {
            throw new InvalidArgumentException("Некорректная таблица синхронизации: {$table}");
        }
        $row = DB::selectOne("SELECT to_jsonb(source) AS data FROM {$table} source WHERE id::text = ?", [$id]);

        return $row ? $this->decodeJson($row->data) : null;
    }

    /** @param array<string, mixed> $definition
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function payload(array $definition, array $row, ?string $tenant): array
    {
        $payload = array_intersect_key($row, array_flip($definition['fields']));
        $payload['id'] = (string) $row['id'];
        foreach ($definition['json_fields'] ?? [] as $field => $keys) {
            $source = is_array($row[$field] ?? null) ? $row[$field] : [];
            $payload[$field] = array_intersect_key($source, array_flip($keys));
        }
        if (isset($definition['projector'])) {
            $projector = app($definition['projector']);
            if (! $projector instanceof SyncPayloadProjector) {
                throw new RuntimeException("Проектор {$definition['projector']} должен реализовывать SyncPayloadProjector.");
            }
            $payload = $projector->project($payload, $tenant);
        }

        return $payload;
    }

    /** @param array<string, mixed> $definition */
    private function tenantFrom(array $definition, mixed $value): ?string
    {
        return ($definition['global'] ?? false) || ! in_array('tenant_id', $definition['fields'], true)
            ? null
            : ($value === null ? null : (string) $value);
    }

    /** @return array<string, mixed> */
    private function definition(string $entity): array
    {
        return $this->definitions()[$entity] ?? throw new InvalidArgumentException("Сущность {$entity} не зарегистрирована для синхронизации.");
    }

    /** @return array<string, mixed> */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }

    private function withinTransaction(callable $callback, string $entity, string $id): void
    {
        if (DB::transactionLevel() > 0) {
            $callback();

            return;
        }
        Log::warning('Синхронизируемая сущность изменена без внешней транзакции.', ['entity' => $entity, 'entity_id' => $id]);
        DB::transaction($callback, 3);
    }
}

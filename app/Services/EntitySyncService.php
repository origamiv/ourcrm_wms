<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use JsonException;

final class EntitySyncService
{
    public function revision(?string $tenant): string
    {
        return (string) $this->checkpoint($tenant)->revision;
    }

    /** @return array<string, mixed> */
    public function current(string $entity, ?string $tenant, string|int $id): array
    {
        $this->checkpoint($tenant);
        if ($tenant !== null && class_exists($entity) && is_subclass_of($entity, \Illuminate\Database\Eloquent\Model::class)) {
            abort_unless($entity::withTrashed()->visibleTo($tenant)->whereKey($id)->exists(), 404);
        }
        $row = DB::table('public.entity_changes')->where('entity', $entity)->where('tenant_id', $tenant)->where('entity_id', $id)->orderByDesc('revision')->first();
        abort_unless($row && $row->data, 404);

        return [...json_decode($row->data, true), 'version' => (string) $row->revision];
    }

    /** @return array<string, mixed> */
    public function page(string $entity, ?string $tenant, string $user, ?string $cursor, ?string $continuation): array
    {
        $size = config('wms.sync_page_size');
        if ($continuation) {
            $state = $this->decode($continuation, $tenant, $user, $entity);
            abort_unless(isset($state['mode'], $state['target'], $state['after'], $state['from']), 422);
        } else {
            $checkpoint = $cursor ? $this->decode($cursor, $tenant, $user, $entity) : ['revision' => '0'];
            abort_unless(isset($checkpoint['revision']), 422, 'Ожидается курсор завершённой загрузки.');
            $from = $checkpoint['revision'];
            $target = $this->checkpoint($tenant);
            $state = ['entity' => $entity, 'tenant' => $tenant, 'user' => $user, 'format' => config('wms.cache_version'),
                'generation' => $target->generation,
                'mode' => $cursor ? 'delta' : 'snapshot', 'from' => $from, 'target' => (string) $target->revision, 'after' => ''];
        }
        if ($state['mode'] === 'snapshot') {
            $tenantWhere = $tenant === null ? 'tenant_id IS NULL' : '(tenant_id = ? OR tenant_id IS NULL)';
            $rows = DB::select('SELECT * FROM (SELECT DISTINCT ON (entity_id) * FROM public.entity_changes WHERE entity = ? AND '.$tenantWhere.' AND revision <= ? ORDER BY entity_id, revision DESC) latest WHERE entity_id > ? AND operation = ? ORDER BY entity_id LIMIT ?', [$entity, ...($tenant === null ? [] : [$tenant]), $state['target'], $state['after'], 'upsert', $size + 1]);
        } else {
            $rows = DB::table('public.entity_changes')->where('entity', $entity)->when($tenant === null, fn ($query) => $query->whereNull('tenant_id'), fn ($query) => $query->where(fn ($q) => $q->where('tenant_id', $tenant)->orWhereNull('tenant_id')))->where('revision', '>', max((int) $state['from'], (int) $state['after']))
                ->where('revision', '<=', $state['target'])->orderBy('revision')->limit($size + 1)->get()->all();
        }
        $more = count($rows) > $size;
        $rows = array_slice($rows, 0, $size);
        $visible = null;
        if ($tenant !== null && $rows && class_exists($entity) && is_subclass_of($entity, \Illuminate\Database\Eloquent\Model::class)) {
            $visible = array_fill_keys($entity::withTrashed()->visibleTo($tenant)->whereKey(array_column($rows, 'entity_id'))->pluck('id')->map(fn ($id) => (string) $id)->all(), true);
        }
        // Never replay previously visible payloads after access has been revoked.
        foreach ($rows as $row) {
            if ($visible !== null && ! isset($visible[(string) $row->entity_id])) {
                $row->operation = 'remove';
                $row->data = null;
            }
        }
        $changes = array_map(fn ($row) => ['id' => (string) $row->entity_id, 'version' => (string) $row->revision,
            'operation' => $row->operation, 'data' => $row->data ? [...json_decode($row->data, true), 'version' => (string) $row->revision] : null], $rows);
        if ($rows) {
            $last = end($rows);
            $state['after'] = (string) ($state['mode'] === 'snapshot' ? $last->entity_id : $last->revision);
        }

        return ['mode' => $state['mode'], 'changes' => $changes, 'continuation' => $more ? $this->encode($state) : null,
            'cursor' => $more ? null : $this->encode(['entity' => $entity, 'tenant' => $tenant, 'user' => $user, 'format' => config('wms.cache_version'), 'generation' => $state['generation'], 'revision' => $state['target']])];
    }

    public function checkpoint(?string $tenant): object
    {
        $state = DB::table('public.sync_state')->where('tenant_id', $tenant)->first();
        if (! $state) {
            DB::table('public.sync_state')->insertOrIgnore(['tenant_id' => $tenant]);
            $state = DB::table('public.sync_state')->where('tenant_id', $tenant)->firstOrFail();
        }

        if ($tenant !== null && isset($state->shared_initialized) && ! $state->shared_initialized) {
            DB::statement('SELECT wms.initialize_tenant_shares(?)', [$tenant]);
            $state = DB::table('public.sync_state')->where('tenant_id', $tenant)->firstOrFail();
        }

        return $state;
    }

    /** Shared writes lock the global partition before tenant partitions; owned writes take a shared lock. */
    public function prepareWrite(string $tenant, string $model, ?string $id, bool $shared = false): void
    {
        $this->checkpoint($tenant);
        $this->checkpoint(null);
        $instance = new $model;
        $shared = $shared || ! Schema::hasColumn($instance->getTable(), 'tenant_id')
            || ($id !== null && $model::withTrashed()->whereKey($id)->whereNull('tenant_id')->exists());
        $lock = DB::table('public.sync_state')->whereNull('tenant_id');
        ($shared ? $lock->lockForUpdate() : $lock->sharedLock())->firstOrFail();
    }

    private function generation(?string $tenant): string
    {
        return (string) $this->checkpoint($tenant)->generation;
    }

    private function encode(array $value): string
    {
        return Crypt::encryptString(json_encode($value));
    }

    private function decode(string $token, ?string $tenant, string $user, string $entity): array
    {
        try {
            $value = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            throw ValidationException::withMessages(['cursor' => 'Курсор недействителен. Требуется повторная загрузка.']);
        }
        abort_unless(is_array($value) && ($value['tenant'] ?? null) === $tenant && ($value['user'] ?? null) === $user, 403);
        abort_unless(($value['format'] ?? null) === config('wms.cache_version'), 409, 'Формат кэша изменён.');
        abort_unless(($value['entity'] ?? null) === $entity, 403);
        abort_unless(($value['generation'] ?? null) === $this->generation($tenant), 409, 'Журнал обновлён. Требуется повторная загрузка.');

        return $value;
    }
}

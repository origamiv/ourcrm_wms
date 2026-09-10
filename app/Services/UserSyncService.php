<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

final class UserSyncService
{
    public function revision(): string
    {
        return (string) DB::table('wms.sync_state')->where('id', 1)->value('revision');
    }

    /** @return array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string} */
    public function current(string|int $id): array
    {
        $row = DB::table('wms.user_changes')->where('user_id', $id)->orderByDesc('revision')->first();
        abort_unless($row && $row->data, 404);

        return [...json_decode($row->data, true), 'version' => (string) $row->revision];
    }

    /** @return array{mode: 'snapshot'|'delta', changes: list<array{id: string, version: string, operation: 'upsert'|'remove', data: array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string}|null}>, cursor: ?string, continuation: ?string} */
    public function page(string $tenant, string $user, ?string $cursor, ?string $continuation): array
    {
        $size = config('wms.sync_page_size');
        if ($continuation) {
            $state = $this->decode($continuation, $tenant, $user);
            abort_unless(isset($state['mode'], $state['target'], $state['after'], $state['from']), 422);
        } else {
            $checkpoint = $cursor ? $this->decode($cursor, $tenant, $user) : ['revision' => '0'];
            abort_unless(isset($checkpoint['revision']), 422, 'Ожидается курсор завершённой загрузки.');
            $from = $checkpoint['revision'];
            $state = ['tenant' => $tenant, 'user' => $user, 'format' => config('wms.cache_version'),
                'generation' => $this->generation(),
                'mode' => $cursor ? 'delta' : 'snapshot', 'from' => $from, 'target' => $this->revision(), 'after' => '0'];
        }
        if ($state['mode'] === 'snapshot') {
            $rows = DB::select('SELECT * FROM (SELECT DISTINCT ON (user_id) * FROM wms.user_changes WHERE tenant_id = ? AND revision <= ? ORDER BY user_id, revision DESC) latest WHERE user_id > ? AND operation = ? ORDER BY user_id LIMIT ?', [$tenant, $state['target'], $state['after'], 'upsert', $size + 1]);
        } else {
            $rows = DB::table('wms.user_changes')->where('tenant_id', $tenant)->where('revision', '>', max((int) $state['from'], (int) $state['after']))
                ->where('revision', '<=', $state['target'])->orderBy('revision')->limit($size + 1)->get()->all();
        }
        $more = count($rows) > $size;
        $rows = array_slice($rows, 0, $size);
        $changes = array_map(fn ($row) => ['id' => (string) $row->user_id, 'version' => (string) $row->revision,
            'operation' => $row->operation, 'data' => $row->data ? [...json_decode($row->data, true), 'version' => (string) $row->revision] : null], $rows);
        if ($rows) {
            $last = end($rows);
            $state['after'] = (string) ($state['mode'] === 'snapshot' ? $last->user_id : $last->revision);
        }

        return ['mode' => $state['mode'], 'changes' => $changes, 'continuation' => $more ? $this->encode($state) : null,
            'cursor' => $more ? null : $this->encode(['tenant' => $tenant, 'user' => $user, 'format' => config('wms.cache_version'), 'generation' => $state['generation'], 'revision' => $state['target']])];
    }

    private function generation(): string
    {
        return (string) DB::table('wms.sync_state')->where('id', 1)->value('generation');
    }

    private function encode(array $value): string
    {
        return Crypt::encryptString(json_encode($value));
    }

    private function decode(string $token, string $tenant, string $user): array
    {
        try {
            $value = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            throw ValidationException::withMessages(['cursor' => 'Курсор недействителен. Требуется повторная загрузка.']);
        }
        abort_unless(is_array($value) && ($value['tenant'] ?? null) === $tenant && ($value['user'] ?? null) === $user, 403);
        abort_unless(($value['format'] ?? null) === config('wms.cache_version'), 409, 'Формат кэша изменён.');
        abort_unless(($value['generation'] ?? null) === $this->generation(), 409, 'Журнал обновлён. Требуется повторная загрузка.');

        return $value;
    }
}

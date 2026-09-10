<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class UserSyncService
{
    public function revision(): string
    {
        return app(EntitySyncService::class)->revision();
    }

    /** @return array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string} */
    public function current(string|int $id): array
    {
        $user = User::withTrashed()->findOrFail($id);

        return app(EntitySyncService::class)->current(User::class, (string) $user->tenant_id, $id);
    }

    /** @return array{mode: 'snapshot'|'delta', changes: list<array{id: string, version: string, operation: 'upsert'|'remove', data: array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string}|null}>, cursor: ?string, continuation: ?string} */
    public function page(string $tenant, string $user, ?string $cursor, ?string $continuation): array
    {
        return app(EntitySyncService::class)->page(User::class, $tenant, $user, $cursor, $continuation);
    }
}

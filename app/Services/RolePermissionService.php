<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

final class RolePermissionService
{
    /** @return array{data: list<array<string, mixed>>, enabled: bool, version: string} */
    public function save(User $actor, string $roleId, string $permissionId, bool $enabled, string $version): array
    {
        return DB::transaction(function () use ($actor, $roleId, $permissionId, $enabled, $version) {
            $tenant = $actor->tenant_id;
            $sync = app(EntitySyncService::class);
            $sync->checkpoint($tenant);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && app(AccessService::class)->isAdmin($actor), 403);
            Role::where('tenant_id', $tenant)->where('status', 1)->findOrFail($roleId);
            Permission::where('tenant_id', $tenant)->where('status', 1)->findOrFail($permissionId);
            $links = PermissionRole::withTrashed()->where('tenant_id', $tenant)->where('role_id', $roleId)->where('permission_id', $permissionId)->orderBy('id')->get();
            $rows = $links->map(fn ($row) => $sync->current(PermissionRole::class, $tenant, $row->id));
            $currentVersion = (string) ($rows->max(fn ($row) => (int) $row['version']) ?? 0);
            if (! hash_equals($currentVersion, $version)) {
                throw new HttpResponseException(response()->json(['message' => 'Назначение изменено. Данные обновлены; повторите действие при необходимости.', 'current' => ['data' => $rows->all(), 'version' => $currentVersion]], 409));
            }
            if ($links->isEmpty() && $enabled) {
                $link = new PermissionRole;
                $link->forceFill(['tenant_id' => $tenant, 'role_id' => $roleId, 'permission_id' => $permissionId]);
                $links->push($link);
            }
            foreach ($links as $index => $link) {
                $active = $enabled && $index === 0;
                $link->forceFill(['status' => $active ? 1 : 0, 'deleted_at' => $active ? null : now()])->save();
            }
            $rows = $links->map(fn ($row) => $sync->current(PermissionRole::class, $tenant, $row->id));

            return ['data' => $rows->all(), 'enabled' => $enabled, 'version' => (string) ($rows->max(fn ($row) => (int) $row['version']) ?? 0)];
        }, 3);
    }
}

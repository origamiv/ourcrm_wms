<?php

declare(strict_types=1);

namespace App\Services\Sync;

use App\Contracts\SyncPayloadProjector;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

final class UserSyncPayloadProjector implements SyncPayloadProjector
{
    public function project(array $row, ?string $tenant): array
    {
        $row['roles'] = DB::table('main.role_user as ru')
            ->join('main.roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $row['id'])
            ->where('ru.status', 1)
            ->whereNull('ru.deleted_at')
            ->where('r.status', 1)
            ->whereNull('r.deleted_at')
            ->whereRaw('wms.entity_visible(?, r.id::text, r.tenant_id, ?)', [Role::class, $tenant])
            ->orderBy('r.name')
            ->orderBy('r.id')
            ->get(['r.id', 'r.name', 'r.slug', 'r.status'])
            ->map(static fn (object $role): array => [
                'id' => (string) $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'status' => $role->status,
            ])
            ->all();

        return $row;
    }
}

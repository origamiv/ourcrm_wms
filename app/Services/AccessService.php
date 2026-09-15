<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AccessService
{
    public function active(User $user): bool
    {
        return ! $user->trashed() && $user->status === 1 && trim((string) $user->tenant_id) !== '';
    }

    public function loginable(User $user): bool
    {
        if ($user->trashed() || $user->status !== 1) {
            return false;
        }
        if (trim((string) $user->tenant_id) !== '') {
            return true;
        }

        return (Schema::hasTable('public.tenants') && DB::table('public.tenants')->where('owner_user_id', $user->id)->exists())
            || DB::table('main.role_user')->where('user_id', $user->id)->where('status', 1)->whereNull('deleted_at')->whereNotNull('tenant_id')->exists()
            || (Schema::hasTable('main.tenant_entity') && DB::table('main.tenant_entity')->where('entity_type', $user->getMorphClass())->where('entity_id', $user->id)->exists());
    }

    public function isAdmin(User $user): bool
    {
        if (! $this->active($user)) {
            return false;
        }

        // Владелец организации имеет административный доступ в ней даже если
        // legacy-таблица role_user не позволяет назначить вторую роль той же паре.
        if (DB::table('public.tenants')->where('id', $user->tenant_id)->where('owner_user_id', $user->id)->exists()) {
            return true;
        }

        return $this->adminAssignments($user->tenant_id)->where('ru.user_id', $user->id)->exists();
    }

    public function adminAssignments(string $tenant)
    {
        return DB::table('main.role_user as ru')->join('main.roles as r', 'r.id', '=', 'ru.role_id')
            ->whereRaw('wms.entity_visible(?, ru.id::text, ru.tenant_id, ?)', [\App\Models\RoleUser::class, $tenant])
            ->where('r.slug', 'admin')->where('ru.status', 1)->where('r.status', 1)
            ->whereNull('ru.deleted_at')->whereNull('r.deleted_at');
    }
}

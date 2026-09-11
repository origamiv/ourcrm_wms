<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UserService
{
    public function __construct(private UserSyncService $sync, private AccessService $access) {}

    public function find(User $actor, string $id): User
    {
        abort_unless($this->access->isAdmin($actor), 403);

        return User::withTrashed()->visibleTo($actor->tenant_id)->findOrFail($id);
    }

    /** @return array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string} */
    public function save(User $actor, array $data, ?string $id = null, string $action = 'update'): array
    {
        return DB::transaction(function () use ($actor, $data, $id, $action) {
            $tenant = $actor->tenant_id;
            app(EntitySyncService::class)->prepareWrite($tenant, User::class, $id);
            DB::table('public.sync_state')->where('tenant_id', $tenant)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($actor->id);
            abort_unless($actor->tenant_id === $tenant && $this->access->isAdmin($actor), 403);
            $user = $id ? $this->find($actor, $id) : new User;
            if ($id) {
                $current = $this->sync->current($id, $tenant);
                if (! hash_equals($current['version'], (string) $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Проверьте актуальные данные.', 'current' => $current], 409));
                }
                abort_if($user->trashed() && $action !== 'restore', 422, 'Сначала восстановите пользователя.');
                abort_if(! $user->trashed() && $action === 'restore', 422, 'Пользователь не удалён.');
            }
            if ($action === 'roles') {
                return $this->saveRoles($actor, $user, $data, $id);
            }
            $statusChanged = $id && $action === 'update' && array_key_exists('status', $data) && (int) $data['status'] !== $user->status;
            $disabling = $statusChanged && (int) $data['status'] !== 1;
            if (in_array($action, ['delete', 'block'], true) || $disabling) {
                abort_if($user->id === $actor->id, 422, 'Нельзя отключить, перевести в новые или удалить себя.');
                if ($this->access->isAdmin($user)) {
                    $others = $this->access->adminAssignments($actor->tenant_id)->join('public.users as u', 'u.id', '=', 'ru.user_id')
                        ->where('u.tenant_id', $actor->tenant_id)->where('u.status', 1)->whereNull('u.deleted_at')->where('u.id', '<>', $user->id)->exists();
                    abort_unless($others, 422, 'Нельзя отключить последнего администратора.');
                }
            }
            if (in_array($action, ['create', 'update'], true)) {
                $email = mb_strtolower(trim($data['email']));
                // Email общий для приложений и организаций: сериализуем проверки одного адреса.
                DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [$email]);
                if (User::withTrashed()->whereRaw('lower(email) = ?', [$email])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists()) {
                    throw ValidationException::withMessages(['email' => 'Этот email уже используется.']);
                }
                $user->fill([...array_intersect_key($data, array_flip($user->getFillable())), 'email' => $email]);
            }
            if (! $id) {
                $user->tenant_id = $actor->tenant_id;
                $user->status = (int) ($data['status'] ?? 0);
                $user->password = $data['password'];
            }
            if ($statusChanged) {
                $user->status = (int) $data['status'];
            }
            if ($action === 'password') {
                $user->password = $data['password'];
            }
            if ($action === 'activate') {
                $user->status = 1;
            }
            if ($action === 'block') {
                $user->status = 2;
            }
            if ($action === 'restore') {
                $user->deleted_at = null;
                $user->status = 0;
            }
            if ($action === 'delete') {
                $user->deleted_at = now();
            }
            $user->save();
            if (in_array($action, ['password', 'block', 'delete'], true) || $disabling) {
                $user->tokens()->where('name', 'like', 'wms:%')->delete();
            }

            return $this->sync->current($user->id, $tenant);
        }, 3);
    }

    /** @return array<string, mixed> */
    private function saveRoles(User $actor, User $user, array $data, ?string $id): array
    {
        abort_unless($id !== null, 422, 'Роли можно назначать только существующему пользователю.');

        $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        $roles = \App\Models\Role::visibleTo($actor->tenant_id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->whereIn('id', $roleIds)
            ->get(['id']);
        abort_unless($roles->count() === count($roleIds), 422, 'Выберите доступные активные роли.');

        $links = DB::table('main.role_user')->where('user_id', $user->id)->where('tenant_id', $actor->tenant_id)->lockForUpdate()->get();
        $activeAdmin = fn (array $ids): bool => DB::table('main.role_user as ru')
            ->join('main.roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ru.user_id', $user->id)
            ->where('ru.tenant_id', $actor->tenant_id)
            ->where('ru.status', 1)
            ->whereNull('ru.deleted_at')
            ->where('r.slug', 'admin')
            ->where('r.status', 1)
            ->whereNull('r.deleted_at')
            ->whereIn('ru.role_id', $ids)
            ->exists();
        if ($user->id === $actor->id && ! $activeAdmin($roleIds)) {
            abort(422, 'Нельзя снять роль admin у своей учётной записи.');
        }

        foreach ($links as $link) {
            if (in_array((int) $link->role_id, $roleIds, true)) {
                DB::table('main.role_user')->where('id', $link->id)->update(['status' => 1, 'deleted_at' => null]);
            } else {
                DB::table('main.role_user')->where('id', $link->id)->update(['status' => 2, 'deleted_at' => now()]);
            }
        }
        $existing = $links->pluck('role_id')->map(fn ($value) => (int) $value)->all();
        foreach (array_diff($roleIds, $existing) as $roleId) {
            DB::table('main.role_user')->insert([
                'role_id' => $roleId,
                'user_id' => $user->id,
                'tenant_id' => $actor->tenant_id,
                'status' => 1,
            ]);
        }

        return $this->sync->current($user->id, $actor->tenant_id);
    }
}

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

        return User::withTrashed()->where('tenant_id', $actor->tenant_id)->findOrFail($id);
    }

    /** @return array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string} */
    public function save(User $actor, array $data, ?string $id = null, string $action = 'update'): array
    {
        return DB::transaction(function () use ($actor, $data, $id, $action) {
            DB::table('wms.sync_state')->where('id', 1)->lockForUpdate()->first();
            $actor = User::findOrFail($actor->id);
            abort_unless($this->access->isAdmin($actor), 403);
            $user = $id ? $this->find($actor, $id) : new User;
            if ($id) {
                $current = $this->sync->current($id);
                if (! hash_equals($current['version'], (string) $data['version'])) {
                    throw new HttpResponseException(response()->json(['message' => 'Запись уже изменена. Проверьте актуальные данные.', 'current' => $current], 409));
                }
                abort_if($user->trashed() && $action !== 'restore', 422, 'Сначала восстановите пользователя.');
                abort_if(! $user->trashed() && $action === 'restore', 422, 'Пользователь не удалён.');
            }
            if (in_array($action, ['delete', 'block'], true)) {
                abort_if($user->id === $actor->id, 422, 'Нельзя блокировать или удалять себя.');
                if ($this->access->isAdmin($user)) {
                    $others = $this->access->adminAssignments($actor->tenant_id)->join('public.users as u', 'u.id', '=', 'ru.user_id')
                        ->where('u.tenant_id', $actor->tenant_id)->where('u.status', 1)->whereNull('u.deleted_at')->where('u.id', '<>', $user->id)->exists();
                    abort_unless($others, 422, 'Нельзя отключить последнего администратора.');
                }
            }
            if (in_array($action, ['create', 'update'], true)) {
                $email = mb_strtolower(trim($data['email']));
                if (User::withTrashed()->whereRaw('lower(email) = ?', [$email])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists()) {
                    throw ValidationException::withMessages(['email' => 'Этот email уже используется.']);
                }
                $user->fill([...array_intersect_key($data, array_flip($user->getFillable())), 'email' => $email]);
            }
            if (! $id) {
                $user->tenant_id = $actor->tenant_id;
                $user->status = 0;
                $user->password = $data['password'];
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
            if (in_array($action, ['password', 'block', 'delete'], true)) {
                $user->tokens()->delete();
            }

            return $this->sync->current($user->id);
        }, 3);
    }
}

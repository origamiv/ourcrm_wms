<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UserActionRequest;
use App\Services\UserService;
use App\Services\UserSyncService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Пользователи организации. Все операции требуют роли admin. */
final class WmsUserController extends BaseApiController
{
    /** Согласованный снимок либо изменения после курсора. */
    public function index(Request $request, UserSyncService $sync): JsonResponse
    {
        $data = $request->validate(['cursor' => ['nullable', 'string', 'max:4096'], 'continuation' => ['nullable', 'string', 'max:4096']]);

        return response()->json($sync->page($request->user()->tenant_id, (string) $request->user()->id, $data['cursor'] ?? null, $data['continuation'] ?? null));
    }

    /** Получить пользователя своей организации, включая мягко удалённого. */
    public function show(Request $request, string $id, UserService $users, UserSyncService $sync): JsonResponse
    {
        $users->find($request->user(), $id);

        return response()->json(['data' => $sync->current($id, $request->user()->tenant_id)]);
    }

    /** Создать пользователя в статусе «Новый». */
    public function store(CreateUserRequest $request, UserService $users): JsonResponse
    {
        return response()->json(['data' => $users->save($request->user(), $request->validated(), action: 'create')], 201);
    }

    /** Изменить профиль с проверкой версии. */
    #[Response(409, 'Запись изменена другим пользователем.', type: 'array{message: string, current: array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string}}')]
    public function update(UpdateUserRequest $request, string $id, UserService $users): JsonResponse
    {
        return response()->json(['data' => $users->save($request->user(), $request->validated(), $id)]);
    }

    /** Действие: activate, block, delete, restore, password или roles. Конфликт версии возвращает 409 и current. */
    #[PathParameter('action', description: 'activate, block, delete, restore, password или roles', type: 'string')]
    #[BodyParameter('password', description: 'Только для password: не менее 12 символов.', type: 'string')]
    #[BodyParameter('password_confirmation', description: 'Подтверждение нового пароля для действия password.', type: 'string')]
    #[Response(409, 'Запись изменена другим пользователем.', type: 'array{message: string, current: array{id: string, name: ?string, last_name: ?string, middle_name: ?string, nick: ?string, email: ?string, phone: ?string, status: ?int, tenant_id: ?string, created_at: ?string, updated_at: ?string, deleted_at: ?string, version: string}}')]
    public function action(UserActionRequest $request, string $id, string $action, UserService $users): JsonResponse
    {
        return response()->json(['data' => $users->save($request->user(), $request->validated(), $id, $action)]);
    }
}

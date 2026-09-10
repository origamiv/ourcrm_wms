<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\AccessCatalogs\CreateCatalogRequest;
use App\Http\Requests\AccessCatalogs\DeleteRoleRequest;
use App\Http\Requests\AccessCatalogs\UpdateCatalogRequest;
use App\Services\AccessCatalogService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class AccessCatalogController extends BaseApiController
{
    /** Удалить роль своей организации (SoftDeletes). Системные роли и admin защищены. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function destroyRole(DeleteRoleRequest $request, string $id, AccessCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->deleteRole($request->user(), $id, $request->validated('version'))]);
    }

    /** Создать роль или право своей организации. catalog: roles или permissions. */
    #[BodyParameter('resource', description: 'Обязательно для permissions; запрещено для roles.', type: 'string')]
    #[BodyParameter('description', description: 'Необязательное описание только для roles.', type: 'string')]
    public function store(CreateCatalogRequest $request, string $catalog, AccessCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $catalog, $request->validated())], 201);
    }

    /** Изменить роль или право своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    #[BodyParameter('resource', description: 'Обязательно для permissions; запрещено для roles.', type: 'string')]
    #[BodyParameter('description', description: 'Необязательное описание только для roles.', type: 'string')]
    public function update(UpdateCatalogRequest $request, string $catalog, string $id, AccessCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $catalog, $request->validated(), $id)]);
    }
}

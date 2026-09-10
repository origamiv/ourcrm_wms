<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\AccessCatalogs\UpdateRolePermissionRequest;
use App\Services\RolePermissionService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class RolePermissionController extends BaseApiController
{
    /** Назначить или снять право у роли своей организации. version = максимальная версия строк этой пары, либо 0, если строк нет. */
    #[Response(409, 'Назначение изменено: повторно синхронизируйте permission_roles.')]
    public function update(UpdateRolePermissionRequest $request, string $roleId, string $permissionId, RolePermissionService $service): JsonResponse
    {
        return response()->json($service->save($request->user(), $roleId, $permissionId, $request->boolean('enabled'), $request->validated('version')));
    }
}

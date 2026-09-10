<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Companies\CreateDirectoryRequest;
use App\Http\Requests\Companies\DeleteDirectoryRequest;
use App\Http\Requests\Companies\UpdateDirectoryRequest;
use App\Services\CompanyDirectoryService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class CompanyDirectoryController extends BaseApiController
{
    /** Создать компанию или контактное лицо. directory: companies или company_contacts. */
    public function store(CreateDirectoryRequest $request, string $directory, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $directory, $request->validated())], 201);
    }

    /** Изменить компанию или контактное лицо своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateDirectoryRequest $request, string $directory, string $id, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $directory, $request->validated(), $id)]);
    }

    /** Мягкое удаление. Компания с неудалёнными контактами защищена (422). */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function destroy(DeleteDirectoryRequest $request, string $directory, string $id, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $directory, $request->validated(), $id, true)]);
    }
}

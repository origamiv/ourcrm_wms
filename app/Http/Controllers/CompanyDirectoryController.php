<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Companies\CreateDirectoryRequest;
use App\Http\Requests\Companies\DeleteDirectoryRequest;
use App\Http\Requests\Companies\UpdateDirectoryRequest;
use App\Models\Company;
use App\Services\CompanyDirectoryService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class CompanyDirectoryController extends BaseApiController
{
    public function contactsPage(Request $request): \Inertia\Response
    {
        $data = $request->validate(['company_id' => ['sometimes', 'required', 'integer', 'min:1']]);
        $scope = null;
        if (isset($data['company_id'])) {
            $company = Company::visibleTo($request->user()->tenant_id)->findOrFail($data['company_id']);
            $scope = ['id' => (string) $company->id, 'name' => $company->name];
        }

        return Inertia::render('CompanyContacts', ['companyScope' => $scope]);
    }

    /** Создать контакт только указанной компании своей организации. company_id назначается сервером. */
    public function storeContact(CreateDirectoryRequest $request, string $companyId, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), 'company_contacts', $request->validated(), companyId: $companyId)], 201);
    }

    /** Изменить контакт только указанной компании, без переноса в другую компанию. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function updateContact(UpdateDirectoryRequest $request, string $companyId, string $id, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), 'company_contacts', $request->validated(), $id, companyId: $companyId)]);
    }

    /** Мягко удалить контакт только указанной компании своей организации. */
    public function destroyContact(DeleteDirectoryRequest $request, string $companyId, string $id, CompanyDirectoryService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), 'company_contacts', $request->validated(), $id, true, $companyId)]);
    }

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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Integration\CreateIntegrationRequest;
use App\Http\Requests\Integration\DeleteIntegrationRequest;
use App\Http\Requests\Integration\UpdateIntegrationRequest;
use App\Services\IntegrationCatalogService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IntegrationController extends BaseApiController
{
    /** Просмотреть доступную запись интеграции. Полные параметры возвращаются отдельно от данных синхронизации и не кэшируются. */
    public function show(Request $request, string $integration_catalog, string $id, IntegrationCatalogService $service): JsonResponse
    {
        return response()->json($service->show($request->user(), $integration_catalog, $id))->header('Cache-Control', 'private, no-store');
    }

    /** Создать запись интеграции своей организации: webhooks, data, rules, services, type_hook или type_processing. */
    public function store(CreateIntegrationRequest $request, string $integration_catalog, IntegrationCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $integration_catalog, $request->validated())], 201);
    }

    /** Изменить доступную запись интеграции с сохранением организации и проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateIntegrationRequest $request, string $integration_catalog, string $id, IntegrationCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $integration_catalog, $request->validated(), $id)]);
    }

    /** Мягко удалить неиспользуемую запись интеграции с проверкой версии. */
    public function destroy(DeleteIntegrationRequest $request, string $integration_catalog, string $id, IntegrationCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $integration_catalog, $request->validated(), $id, true)]);
    }
}

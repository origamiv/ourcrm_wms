<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\ClientCatalogs\CreateClientCatalogRequest;
use App\Http\Requests\ClientCatalogs\UpdateClientCatalogRequest;
use App\Http\Requests\Goods\DeleteGoodRequest;
use App\Services\ClientCatalogService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ClientCatalogController extends BaseApiController
{
    /** Создать сервис или доступ клиента своей организации. */
    public function store(CreateClientCatalogRequest $request, string $clientCatalog, ClientCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $clientCatalog, $request->validated())], 201);
    }

    /** Изменить сервис или доступ клиента с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateClientCatalogRequest $request, string $clientCatalog, string $id, ClientCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $clientCatalog, $request->validated(), $id)]);
    }

    /** Мягко удалить сервис или доступ клиента с проверкой версии. */
    public function destroy(DeleteGoodRequest $request, string $clientCatalog, string $id, ClientCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $clientCatalog, $request->validated(), $id, true)]);
    }
}

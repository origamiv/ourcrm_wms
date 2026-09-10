<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Clients\CreateClientRequest;
use App\Http\Requests\Clients\DeleteClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Services\ClientService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class ClientController extends BaseApiController
{
    /** Создать клиента своей организации. */
    public function store(CreateClientRequest $request, ClientService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated())], 201);
    }

    /** Изменить клиента своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateClientRequest $request, string $id, ClientService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated(), $id)]);
    }

    /** Мягко удалить клиента своей организации с проверкой версии. */
    public function destroy(DeleteClientRequest $request, string $id, ClientService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated(), $id, true)]);
    }
}

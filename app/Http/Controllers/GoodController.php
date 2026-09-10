<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Goods\CreateGoodRequest;
use App\Http\Requests\Goods\DeleteGoodRequest;
use App\Http\Requests\Goods\UpdateGoodRequest;
use App\Services\GoodService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class GoodController extends BaseApiController
{
    /** Создать товар своей организации. */
    public function store(CreateGoodRequest $request, GoodService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated())], 201);
    }

    /** Изменить товар своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateGoodRequest $request, string $id, GoodService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated(), $id)]);
    }

    /** Мягко удалить товар своей организации с проверкой версии. */
    public function destroy(DeleteGoodRequest $request, string $id, GoodService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated(), $id, true)]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Goods\CreateKizRequest;
use App\Http\Requests\Goods\DeleteGoodRequest;
use App\Http\Requests\Goods\UpdateKizRequest;
use App\Services\KizService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class KizController extends BaseApiController
{
    /** Создать код маркировки своей организации. */
    public function store(CreateKizRequest $request, KizService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated())], 201);
    }

    /** Изменить код маркировки своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateKizRequest $request, string $id, KizService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated(), $id)]);
    }

    /** Мягко удалить код маркировки своей организации с проверкой версии. */
    public function destroy(DeleteGoodRequest $request, string $id, KizService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $request->validated(), $id, true)]);
    }
}

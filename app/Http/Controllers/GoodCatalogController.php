<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Goods\CreateGoodCatalogRequest;
use App\Http\Requests\Goods\DeleteGoodRequest;
use App\Http\Requests\Goods\UpdateGoodCatalogRequest;
use App\Services\GoodCatalogService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class GoodCatalogController extends BaseApiController
{
    /** Создать запись справочника своей организации. */
    public function store(CreateGoodCatalogRequest $request, string $catalog, GoodCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $catalog, $request->validated())], 201);
    }

    /** Изменить запись справочника своей организации с проверкой версии. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateGoodCatalogRequest $request, string $catalog, string $id, GoodCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $catalog, $request->validated(), $id)]);
    }

    /** Мягко удалить запись справочника своей организации с проверкой версии. */
    public function destroy(DeleteGoodRequest $request, string $catalog, string $id, GoodCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->save($request->user(), $catalog, $request->validated(), $id, true)]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Fulfillment\UpdateMarketplaceCatalogMatchRequest;
use App\Services\MarketplaceCatalogService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class MarketplaceCatalogController extends BaseApiController
{
    /** Вручную сопоставить товар маркетплейса с товаром мастер-каталога. */
    #[Response(409, 'Запись изменена', type: 'array{message: string, current: array<string, mixed>}')]
    public function update(UpdateMarketplaceCatalogMatchRequest $request, string $id, MarketplaceCatalogService $service): JsonResponse
    {
        return response()->json(['data' => $service->match($request->user(), $id, $request->validated())]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Companies\SuggestCompanyRequest;
use App\Services\DadataService;
use Illuminate\Http\JsonResponse;

final class CompanySuggestionController extends BaseApiController
{
    /** Подсказки реквизитов DaData. type: party (компания) или bank (банк). Запись не сохраняется. */
    public function index(SuggestCompanyRequest $request, string $type, DadataService $service): JsonResponse
    {
        return response()->json(['suggestions' => $service->suggest($type, $request->validated('query'))])->header('Cache-Control', 'no-store');
    }
}

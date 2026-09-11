<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Services\AcceptanceService;
use Illuminate\Http\Request;

final class AcceptanceController extends BaseApiController
{
    public function pick(Request $request, string $id, AcceptanceService $service): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['barcode' => ['required', 'string', 'max:255'], 'cell_id' => ['nullable', 'integer', 'min:1']]);
        return response()->json($service->pick($request->user(), (int) $id, $data['barcode'], $data['cell_id'] ?? null));
    }
}

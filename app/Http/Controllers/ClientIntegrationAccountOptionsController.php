<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClientAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientIntegrationAccountOptionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $accounts = ClientAccount::visibleTo($request->user()->tenant_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'shortname', 'client_id']);

        return response()->json(['data' => $accounts])->header('Cache-Control', 'private, no-store');
    }
}

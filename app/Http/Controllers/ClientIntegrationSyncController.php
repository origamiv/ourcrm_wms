<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\Client;
use App\Services\EntitySyncService;
use App\Services\SyncEntityRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientIntegrationSyncController extends BaseApiController
{
    public function __invoke(Request $request, string $clientId, SyncEntityRegistry $registry, EntitySyncService $sync): JsonResponse
    {
        $client = Client::visibleTo($request->user()->tenant_id)->findOrFail($clientId);
        $definition = $registry->resolve('integration_webhooks', $request->user());
        $input = $request->validate(['cursor' => ['nullable', 'string', 'max:4096'], 'continuation' => ['nullable', 'string', 'max:4096']]);
        $page = $sync->page($definition['entity'], $request->user()->tenant_id, (string) $request->user()->id, $input['cursor'] ?? null, $input['continuation'] ?? null);

        foreach ($page['changes'] as &$change) {
            if ($change['data'] !== null && (string) ($change['data']['client_id'] ?? '') !== (string) $client->id) {
                $change['operation'] = 'remove';
                $change['data'] = null;
            }
        }
        unset($change);

        return response()->json(['entity_type' => 'integration_webhooks', ...$page]);
    }
}

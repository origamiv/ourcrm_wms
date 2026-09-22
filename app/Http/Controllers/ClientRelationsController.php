<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\ClientCompany;
use App\Models\ClientIndividual;
use App\Models\Document;
use App\Models\IntegrationWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientRelationsController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $input = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:25'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $tenant = $request->user()->tenant_id;
        $ids = Client::visibleTo($tenant)->whereKey($input['ids'])->pluck('id')->map(fn ($id) => (string) $id)->all();
        $relations = [
            'documents' => Document::class,
            'accounts' => ClientAccount::class,
            'integrations' => IntegrationWebhook::class,
            'companies' => ClientCompany::class,
            'individuals' => ClientIndividual::class,
        ];
        $data = array_fill_keys($ids, array_fill_keys(array_keys($relations), false));

        foreach ($relations as $name => $model) {
            $found = $model::visibleTo($tenant)->whereIn('client_id', $ids)->distinct()->pluck('client_id');
            foreach ($found as $clientId) {
                $data[(string) $clientId][$name] = true;
            }
        }

        return response()->json(['data' => $data]);
    }
}

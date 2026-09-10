<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Services\EntitySyncService;
use App\Services\SyncEntityRegistry;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EntitySyncController extends BaseApiController
{
    /** Снимок или дельта зарегистрированной сущности; доступны users, roles, permissions и permission_roles для администратора. */
    #[Response(200, 'Страница синхронизации', type: 'array{entity_type: string, mode: string, changes: list<array{id: string, version: string, operation: string, data: array<string, mixed>|null}>, cursor: string|null, continuation: string|null}')]
    #[Response(409, 'Формат кэша или поколение журнала изменились: требуется новый снимок.')]
    public function index(Request $request, string $entity_type, SyncEntityRegistry $registry, EntitySyncService $sync): JsonResponse
    {
        $definition = $registry->resolve($entity_type, $request->user());
        $input = $request->validate(['cursor' => ['nullable', 'string', 'max:4096'], 'continuation' => ['nullable', 'string', 'max:4096']]);
        $page = $sync->page($definition['entity'], ($definition['global'] ?? false) ? null : $request->user()->tenant_id, (string) $request->user()->id, $input['cursor'] ?? null, $input['continuation'] ?? null);

        return response()->json(['entity_type' => $entity_type, ...$page]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\FilterPresets\CreateFilterPresetRequest;
use App\Http\Requests\FilterPresets\UpdateFilterPresetRequest;
use App\Models\UserFilterPreset;
use App\Services\FilterPresetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FilterPresetController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $input = $request->validate(['screen_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_:]+$/']]);
        $rows = UserFilterPreset::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('screen_key', $input['screen_key'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $rows->map($this->item(...))->values()])->header('Cache-Control', 'private, no-store');
    }

    public function store(CreateFilterPresetRequest $request, FilterPresetService $service): JsonResponse
    {
        $row = $service->save($request->user(), $request->validated());

        return response()->json(['data' => $this->item($row)], 201);
    }

    public function update(UpdateFilterPresetRequest $request, FilterPresetService $service, string $id): JsonResponse
    {
        $row = $service->save($request->user(), $request->validated(), $service->owned($request->user(), $id));

        return response()->json(['data' => $this->item($row)]);
    }

    public function destroy(Request $request, FilterPresetService $service, string $id): JsonResponse
    {
        $service->delete($request->user(), $id);

        return response()->json(['data' => ['id' => $id]]);
    }

    private function item(UserFilterPreset $row): array
    {
        return [
            'id' => (string) $row->id,
            'screen_key' => $row->screen_key,
            'name' => $row->name,
            'rules' => $row->rules,
            'is_active' => $row->is_active,
            'updated_at' => $row->updated_at?->toIso8601String(),
        ];
    }
}

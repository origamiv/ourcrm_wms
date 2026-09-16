<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\BaseApiController;
use App\Http\Requests\Instructions\CreateInstructionRequest;
use App\Http\Requests\Instructions\UpdateInstructionRequest;
use App\Models\Instruction;
use App\Services\InstructionService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class InstructionController extends BaseApiController
{
    #[Response(200, 'Список инструкций')]
    public function index(Request $request, InstructionService $service): JsonResponse
    {
        $section = $request->validate(['section' => ['nullable', 'string', 'in:'.implode(',', Instruction::SECTIONS)]])['section'] ?? null;
        $rows = $service->list($request->user(), $section, app(\App\Services\AccessService::class)->isAdmin($request->user()));

        return response()->json(['data' => $rows->map(fn (Instruction $row): array => $this->item($row))->values()]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $row = Instruction::query()->visibleTo($request->user()->tenant_id)->where('status', 1)->findOrFail($id);

        return response()->json(['data' => $this->item($row)]);
    }

    public function store(CreateInstructionRequest $request, InstructionService $service): JsonResponse
    {
        abort_unless(app(\App\Services\AccessService::class)->isAdmin($request->user()), 403);

        return response()->json(['data' => $this->item($service->save($request->user(), $request->validated()))], 201);
    }

    public function update(UpdateInstructionRequest $request, string $id, InstructionService $service): JsonResponse
    {
        abort_unless(app(\App\Services\AccessService::class)->isAdmin($request->user()), 403);
        $row = Instruction::query()->visibleTo($request->user()->tenant_id)->findOrFail($id);

        return response()->json(['data' => $this->item($service->save($request->user(), $request->validated(), $row))]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(app(\App\Services\AccessService::class)->isAdmin($request->user()), 403);
        $row = Instruction::query()->visibleTo($request->user()->tenant_id)->findOrFail($id);
        Storage::disk($row->storage_disk)->delete($row->storage_path);
        $row->delete();

        return response()->json(['data' => ['id' => $id]]);
    }

    public function content(Request $request, string $id)
    {
        $row = Instruction::query()->visibleTo($request->user()->tenant_id)->where('status', 1)->findOrFail($id);
        return Storage::disk($row->storage_disk)->response($row->storage_path, $row->original_filename, ['Content-Type' => $row->mime_type, 'Content-Disposition' => 'inline']);
    }

    public function download(Request $request, string $id)
    {
        $row = Instruction::query()->visibleTo($request->user()->tenant_id)->where('status', 1)->findOrFail($id);
        return Storage::disk($row->storage_disk)->download($row->storage_path, $row->original_filename, ['Content-Type' => $row->mime_type]);
    }

    private function item(Instruction $row): array
    {
        return [
            'id' => (string) $row->id, 'name' => $row->name, 'shortname' => $row->shortname,
            'section_key' => $row->section_key, 'content_type' => $row->content_type,
            'original_filename' => $row->original_filename, 'mime_type' => $row->mime_type,
            'size' => $row->size, 'sort_order' => $row->sort_order, 'status' => $row->status,
            'updated_at' => $row->updated_at?->toISOString(),
            'content_url' => '/web/instructions/'.$row->id.'/content',
            'download_url' => '/web/instructions/'.$row->id.'/download',
        ];
    }
}

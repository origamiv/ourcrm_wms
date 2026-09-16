<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Instruction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class InstructionService
{
    public function list(User $user, ?string $section = null, bool $includeInactive = false)
    {
        $query = Instruction::query()->visibleTo($user->tenant_id)->orderBy('sort_order')->orderBy('name');
        if ($section !== null) {
            $query->where('section_key', $section);
        }
        if (! $includeInactive) {
            $query->where('status', 1);
        }

        return $query->get();
    }

    public function save(User $user, array $data, ?Instruction $instruction = null): Instruction
    {
        $file = $data['file'] ?? null;
        if (! $instruction) {
            $instruction = new Instruction(['tenant_id' => $user->tenant_id]);
        } else {
            $instruction = Instruction::query()->visibleTo($user->tenant_id)->findOrFail($instruction->id);
        }
        if ($file instanceof UploadedFile) {
            if ($instruction->storage_path) {
                Storage::disk($instruction->storage_disk)->delete($instruction->storage_path);
            }
            $path = $file->store('instructions/'.$user->tenant_id, 'local');
            $instruction->forceFill([
                'storage_disk' => 'local',
                'storage_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'content_type' => $this->contentType($file),
            ]);
        }
        $instruction->forceFill([
            'name' => $data['name'],
            'shortname' => $data['shortname'],
            'section_key' => $data['section_key'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (int) $data['status'],
        ])->save();

        return $instruction->refresh();
    }

    private function contentType(UploadedFile $file): string
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'pdf' => 'pdf',
            'md', 'markdown' => 'markdown',
            'html', 'htm' => 'html',
            'mp4' => 'video',
            default => throw new \InvalidArgumentException('Формат инструкции не поддерживается.'),
        };
    }
}

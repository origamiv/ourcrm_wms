<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructions;

use App\Models\Instruction;
use App\Http\BaseRequest;

final class UpdateInstructionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'shortname' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'section_key' => ['required', 'string', 'in:'.implode(',', Instruction::SECTIONS)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'status' => ['required', 'integer', 'in:0,1,2'],
            'file' => ['nullable', 'file', 'max:512000', 'mimes:pdf,md,markdown,html,htm,mp4'],
        ];
    }
}

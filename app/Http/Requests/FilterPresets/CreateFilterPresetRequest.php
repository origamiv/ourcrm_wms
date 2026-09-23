<?php

declare(strict_types=1);

namespace App\Http\Requests\FilterPresets;

use App\Http\BaseRequest;

final class CreateFilterPresetRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'screen_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_:]+$/'],
            'name' => ['required', 'string', 'max:80'],
            'rules' => ['required', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

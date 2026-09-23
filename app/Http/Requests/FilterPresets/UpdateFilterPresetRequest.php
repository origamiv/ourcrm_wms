<?php

declare(strict_types=1);

namespace App\Http\Requests\FilterPresets;

use App\Http\BaseRequest;

final class UpdateFilterPresetRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'rules' => ['required', 'array'],
            'is_active' => ['required', 'boolean'],
            'screen_key' => ['prohibited'],
        ];
    }
}

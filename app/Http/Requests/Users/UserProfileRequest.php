<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Http\BaseRequest;

abstract class UserProfileRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'last_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'], 'nick' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:255'],
            'tenant_id' => ['prohibited'], 'status' => ['sometimes', 'integer', 'in:0,1,2']];
    }
}

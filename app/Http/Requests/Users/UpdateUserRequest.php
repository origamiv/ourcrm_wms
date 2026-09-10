<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

final class UpdateUserRequest extends UserProfileRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'password' => ['prohibited'], 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

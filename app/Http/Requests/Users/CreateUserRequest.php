<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Validation\Rules\Password;

final class CreateUserRequest extends UserProfileRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'password' => ['required', 'confirmed', Password::min(12)]];
    }
}

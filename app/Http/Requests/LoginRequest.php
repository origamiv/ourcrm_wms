<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\BaseRequest;

final class LoginRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']];
    }
}

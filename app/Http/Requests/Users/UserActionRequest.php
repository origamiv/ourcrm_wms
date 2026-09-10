<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Http\BaseRequest;
use Illuminate\Validation\Rules\Password;

final class UserActionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'string', 'regex:/^[0-9]+$/'], 'tenant_id' => ['prohibited'],
            'password' => $this->route('action') === 'password' ? ['required', 'confirmed', Password::min(12)] : ['prohibited']];
    }
}

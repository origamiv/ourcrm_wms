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
        $action = $this->route('action');

        return [
            'version' => ['required', 'string', 'regex:/^[0-9]+$/'],
            'tenant_id' => ['prohibited'],
            'password' => $action === 'password' ? ['required', 'confirmed', Password::min(12)] : ['prohibited'],
            'role_ids' => $action === 'roles' ? ['present', 'array', 'list', 'max:100'] : ['prohibited'],
            'role_ids.*' => $action === 'roles' ? ['integer', 'distinct', 'min:1'] : ['prohibited'],
        ];
    }
}

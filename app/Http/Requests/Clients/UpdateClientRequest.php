<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

final class UpdateClientRequest extends ClientRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

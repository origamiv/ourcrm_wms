<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientParties;

final class UpdateClientPartyRequest extends ClientPartyRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Integration;

final class UpdateIntegrationRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientCatalogs;

final class UpdateClientCatalogRequest extends ClientCatalogRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

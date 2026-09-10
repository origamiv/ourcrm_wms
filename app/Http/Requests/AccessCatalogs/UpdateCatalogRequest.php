<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessCatalogs;

final class UpdateCatalogRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

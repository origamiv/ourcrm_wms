<?php

declare(strict_types=1);

namespace App\Http\Requests\Fulfillment;

final class UpdateFulfillmentCatalogRequest extends FulfillmentCatalogRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

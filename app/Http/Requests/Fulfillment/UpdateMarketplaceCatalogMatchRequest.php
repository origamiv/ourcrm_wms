<?php

declare(strict_types=1);

namespace App\Http\Requests\Fulfillment;

use App\Http\BaseRequest;

final class UpdateMarketplaceCatalogMatchRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'good_id' => ['required', 'integer', 'min:1'],
            'version' => ['required', 'string', 'regex:/^[0-9]+$/'],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'good_id' => 'Товар мастер-каталога'];
    }
}

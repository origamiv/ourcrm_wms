<?php

declare(strict_types=1);

namespace App\Http\Requests\Goods;

final class UpdateGoodRequest extends GoodRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

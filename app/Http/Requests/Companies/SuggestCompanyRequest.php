<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use App\Http\BaseRequest;

final class SuggestCompanyRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['query' => ['required', 'string', 'min:2', 'max:255']];
    }
}

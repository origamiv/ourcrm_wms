<?php

declare(strict_types=1);

namespace App\Http\Requests\Goods;

use App\Http\BaseRequest;

abstract class KizRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'kind_kiz_id' => ['nullable', 'integer', 'min:1'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'good_id' => ['nullable', 'integer', 'min:1'],
            'entranced_at' => ['nullable', 'date'],
            'leaving_at' => ['nullable', 'date'],
            'printed_at' => ['nullable', 'date'],
            'tenant_id' => ['prohibited'],
        ];
    }
}

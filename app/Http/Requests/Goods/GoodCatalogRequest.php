<?php

declare(strict_types=1);

namespace App\Http\Requests\Goods;

use App\Http\BaseRequest;

abstract class GoodCatalogRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'shortname' => ['nullable', 'string', 'max:255'], 'status' => ['present', $this->route('catalog') === 'kind_kiz' ? 'required' : 'nullable', 'integer', 'in:0,1,2'], 'tenant_id' => ['prohibited']];
    }
}

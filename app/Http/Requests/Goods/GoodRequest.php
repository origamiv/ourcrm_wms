<?php

declare(strict_types=1);

namespace App\Http\Requests\Goods;

abstract class GoodRequest extends \App\Http\BaseRequest
{
    public function rules(): array
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'status' => ['present', 'nullable', 'integer', 'in:0,1,2'], 'tenant_id' => ['prohibited'], 'level' => ['required', 'integer', 'min:0', 'max:2147483647'], 'barcodes' => ['nullable', 'array']];
        foreach (['shortname', 'code', 'parent_code'] as $key) {
            $rules[$key] = ['nullable', 'string', 'max:255'];
        }
        foreach (['parent_id', 'goodcard_id', 'type_good', 'type_unit'] as $key) {
            $rules[$key] = ['nullable', 'integer', 'min:1', 'max:2147483647'];
        }
        foreach (['is_category', 'is_from_external'] as $key) {
            $rules[$key] = ['nullable', 'integer', 'in:1,2'];
        }
        $rules['articul'] = ['nullable', 'array', 'list'];
        $rules['articul.*'] = ['required', 'string', 'max:255'];

        return $rules;
    }
}

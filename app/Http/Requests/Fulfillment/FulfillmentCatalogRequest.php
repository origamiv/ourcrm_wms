<?php

declare(strict_types=1);

namespace App\Http\Requests\Fulfillment;

use App\Http\BaseRequest;
use Illuminate\Validation\Rule;

abstract class FulfillmentCatalogRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'shortname' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique($this->route('catalog') === 'marketplaces' ? 'wms.marketplaces' : 'wms.delivery_services', 'shortname')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at'),
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
            'icon' => ['nullable', 'string', 'max:255'],
            'tenant_id' => ['prohibited'],
        ];
        if ($this->route('catalog') === 'delivery_services') {
            $rules += [
                'marketplace_id' => ['nullable', 'integer', 'min:1'],
                'color' => ['nullable', 'string', 'max:32'],
                'from_integration_only' => ['nullable', 'integer', 'in:0,1'],
                'is_order_edit' => ['nullable', 'integer', 'in:0,1'],
                'prefix' => ['nullable', 'string', 'max:255'],
                'folder' => ['nullable', 'string', 'max:255'],
            ];
        }

        return $rules;
    }
}

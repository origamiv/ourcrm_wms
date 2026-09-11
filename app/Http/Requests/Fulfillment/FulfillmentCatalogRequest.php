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
                Rule::unique(match ($this->route('catalog')) {
                    'marketplaces' => 'pgsql.wms.marketplaces',
                    'delivery_services' => 'pgsql.wms.delivery_services',
                    'warehouses' => 'pgsql.wms.warehouses',
                    'cells' => 'pgsql.wms.cells',
                    'cell_goods' => 'pgsql.wms.cell_goods',
                    'zones' => 'pgsql.wms.zones',
                    'type_storage' => 'pgsql.wms.type_storage',
                    default => 'pgsql.wms.type_warehouses',
                }, 'shortname')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at'),
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
            'icon' => ['nullable', 'string', 'max:255'],
            'tenant_id' => ['prohibited'],
        ];
        if ($this->route('catalog') === 'warehouses') {
            $rules['type_warehouse_id'] = ['nullable', 'integer', 'min:1'];
        }
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
        if ($this->route('catalog') === 'cells') {
            $rules += [
                'warehouse_id' => ['required', 'integer', 'min:1'],
                'zone_id' => ['required', 'integer', 'min:1'],
                'row' => ['nullable', 'integer', 'min:0'],
                'level' => ['nullable', 'integer', 'min:0'],
                'number' => ['nullable', 'integer', 'min:0'],
                'priority' => ['nullable', 'integer'],
                'type_storage_id' => ['nullable', 'integer', 'min:1'],
            ];
        }
        if ($this->route('catalog') === 'cell_goods') {
            $rules = [
                'warehouse_id' => ['required', 'integer', 'min:1'],
                'cell_id' => ['required', 'integer', 'min:1'],
                'good_id' => ['required', 'integer', 'min:1'],
                'user_id' => ['nullable', 'integer', 'min:1'],
                'cnt' => ['required', 'integer', 'min:0'],
                'put_at' => ['nullable', 'date'],
                'leave_at' => ['nullable', 'date', 'after_or_equal:put_at'],
                'src' => ['nullable', 'array'],
                'tenant_id' => ['prohibited'],
            ];
        }

        return $rules;
    }
}

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
                    'kind_warehouses' => 'pgsql.wms.kind_warehouses',
                    'type_services' => 'pgsql.wms.type_services',
                    'services_ff' => 'pgsql.wms.services_ff',
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
            $rules += [
                'type_warehouse_id' => ['nullable', 'integer', 'min:1'],
                'code' => ['nullable', 'string', 'max:255'],
                'kind_warehouse_id' => ['nullable', 'integer', 'min:1'],
                'address' => ['nullable', 'string', 'max:255'],
                'timezone' => ['nullable', 'string', 'max:255'],
                'contact_name' => ['nullable', 'string', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:255'],
                'working_hours' => ['nullable', 'string', 'max:255'],
                'width' => ['required', 'integer', 'min:1'],
                'height' => ['required', 'integer', 'min:1'],
                'scheme_json' => ['nullable', 'array'],
            ];
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
        if ($this->route('catalog') === 'acceptances') {
            $rules = [
                'client_id' => ['nullable', 'integer', 'min:1'],
                'warehouse_id' => ['nullable', 'integer', 'min:1'],
                'task_id' => ['nullable', 'integer', 'min:1'],
                'plan_count' => ['nullable', 'integer', 'min:0'],
                'fact_count' => ['nullable', 'integer', 'min:0'],
                'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
                'type_acceptance_id' => ['nullable', 'integer', 'min:1'],
                'started_at' => ['nullable', 'date'],
                'finished_at' => ['nullable', 'date', 'after_or_equal:started_at'],
                'status' => ['required', 'integer', 'in:0,1,2,3'],
                'tenant_id' => ['prohibited'],
            ];
        }
        if ($this->route('catalog') === 'services_ff') {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'shortname' => ['nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
                'status' => ['required', 'integer', 'in:0,1,2'],
                'unit_id' => ['nullable', 'integer', 'min:1'],
                'price' => ['nullable', 'numeric', 'min:0'],
                'type_service_ff' => ['nullable', 'integer', 'min:1'],
                'is_visible' => ['nullable', 'integer', 'in:0,1'],
                'tenant_id' => ['prohibited'],
            ];
        }

        return $rules;
    }
}

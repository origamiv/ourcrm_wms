<?php

declare(strict_types=1);

namespace App\Http\Requests\Integration;

use App\Http\BaseRequest;
use App\Services\IntegrationDefinition;

abstract class IntegrationRequest extends BaseRequest
{
    public function rules(): array
    {
        $catalog = $this->route('integration_catalog');
        $definition = ['fields' => [], 'links' => []];
        // Scramble читает правила вне HTTP-запроса: описываем объединение полей.
        foreach ($catalog ? [$catalog] : ['webhooks', 'data', 'rules'] as $type) {
            $item = IntegrationDefinition::get($type);
            $definition['fields'] = array_unique([...$definition['fields'], ...$item['fields']]);
            $definition['links'] = [...$definition['links'], ...$item['links']];
        }
        $rules = ['name' => ['required', 'string', 'max:255'], 'status' => ['present', 'nullable', 'integer', 'in:0,1,2,3'], 'tenant_id' => ['prohibited']];
        foreach ($definition['fields'] as $field) {
            if (isset($rules[$field])) {
                continue;
            }
            $rules[$field] = match ($field) {
                'rules_id' => ['nullable', 'array', 'list', 'max:1000'],
                'params', 'src', 'data', 'progress_processing' => ['nullable', 'array'],
                'dat_last_run' => ['nullable', 'date'],
                'cnt' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
                'status_processing' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
                'raw' => ['nullable', 'string', 'max:1000000'],
                default => isset($definition['links'][$field])
                    ? ['nullable', 'integer', 'min:1', 'max:2147483647']
                    : ['nullable', 'string', 'max:255'],
            };
        }
        if (isset($rules['rules_id'])) {
            $rules['rules_id.*'] = ['required', 'integer', 'min:1', 'distinct'];
        }
        if ($catalog === 'webhooks') {
            // Сохраняем существующие ключи params, не перечисленные в схеме конструктора.
            $rules['params.*'] = ['nullable'];
            $rules['params.builder'] = ['sometimes', 'array:version,nodes'];
            $rules['params.account_id'] = ['sometimes', 'integer', 'min:1'];
            $rules['params.builder.version'] = ['required_with:params.builder', 'integer', 'in:1'];
            $rules['params.builder.nodes'] = ['present_with:params.builder', 'array', 'list', 'max:5'];
            $rules['params.builder.nodes.*'] = ['required', 'array:id,type,settings'];
            $rules['params.builder.nodes.*.id'] = ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/', 'distinct'];
            $rules['params.builder.nodes.*.type'] = ['required', 'string', 'in:catalog_sync,stock_export,orders_import,shipment,other', 'distinct'];
            $rules['params.builder.nodes.*.settings'] = ['present', 'array:marketplace_id,schedule_hours,sync_prices,discount,category_mappings,rule_id,json,export_stocks,stock_formula,stock_fixed,trigger_mode,trigger_hours'];
            $rules['params.builder.nodes.*.settings.marketplace_id'] = ['sometimes', 'nullable', 'integer', 'min:1'];
            $rules['params.builder.nodes.*.settings.rule_id'] = ['sometimes', 'nullable', 'integer', 'min:1'];
            $rules['params.builder.nodes.*.settings.json'] = ['sometimes', 'string', 'max:1000000', 'json'];
            $rules['params.builder.nodes.*.settings.export_stocks'] = ['sometimes', 'boolean'];
            $rules['params.builder.nodes.*.settings.stock_formula'] = ['sometimes', 'string', 'in:real-fbs-fbo,real-fbs,fixed'];
            $rules['params.builder.nodes.*.settings.stock_fixed'] = ['sometimes', 'nullable', 'integer', 'min:0', 'max:2000000000'];
            $rules['params.builder.nodes.*.settings.trigger_mode'] = ['sometimes', 'string', 'in:events,schedule'];
            $rules['params.builder.nodes.*.settings.trigger_hours'] = ['sometimes', 'integer', 'in:1,2,4,6,12,24'];
            $rules['params.builder.nodes.*.settings.schedule_hours'] = ['sometimes', 'integer', 'in:1,2,4,6,12,24'];
            $rules['params.builder.nodes.*.settings.sync_prices'] = ['sometimes', 'boolean'];
            $rules['params.builder.nodes.*.settings.discount'] = ['sometimes', 'boolean'];
            $rules['params.builder.nodes.*.settings.category_mappings'] = ['sometimes', 'array', 'list', 'max:100'];
            $rules['params.builder.nodes.*.settings.category_mappings.*'] = ['required', 'array:crm,marketplace'];
            $rules['params.builder.nodes.*.settings.category_mappings.*.crm'] = ['required', 'string', 'max:255'];
            $rules['params.builder.nodes.*.settings.category_mappings.*.marketplace'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}

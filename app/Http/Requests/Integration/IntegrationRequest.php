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

        return $rules;
    }
}

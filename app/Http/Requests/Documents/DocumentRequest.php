<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

abstract class DocumentRequest extends \App\Http\BaseRequest
{
    public function rules(): array
    {
        $types = $this->route('document_catalog') === 'doc_types';
        $rules = ['name' => ['required', 'string', 'max:255'], 'shortname' => ['nullable', 'string', 'max:255'], 'status' => ['required', 'integer', $types ? 'in:1,2' : 'in:0,1,2,3'], 'tenant_id' => ['prohibited']];
        if (! $types) {
            foreach (['client_id', 'doc_type_id'] as $field) {
                $rules[$field] = ['required', 'integer', 'min:1'];
            }
            $rules['comment'] = ['nullable', 'string', 'max:10000'];
            $rules['internal_comment'] = ['nullable', 'string', 'max:10000'];
            $rules['src'] = ['nullable', 'array'];
            $rules['doc_date'] = ['nullable', 'date_format:Y-m-d'];
            foreach (['accepted_at', 'payed_at', 'canceled_at'] as $field) {
                $rules[$field] = ['nullable', 'date_format:Y-m-d\TH:i'];
            }
        }

        return $rules;
    }
}

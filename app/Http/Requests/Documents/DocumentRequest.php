<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

abstract class DocumentRequest extends \App\Http\BaseRequest
{
    public function rules(): array
    {
        $types = $this->route('document_catalog') === 'doc_types';
        $rules = ['name' => ['required', 'string', 'max:255'], 'shortname' => ['nullable', 'string', 'max:255'], 'status' => ['required', 'integer', $types ? 'in:1,2' : 'in:0,1,2,3'], 'tenant_id' => ['prohibited']];
        if ($types) {
            $rules['settings'] = ['nullable', 'array'];
            $rules['settings.print'] = ['sometimes', 'array'];
            $rules['settings.print.template'] = ['required_with:settings.print', 'in:invoice,act,upd,contract,agreement,vat_invoice'];
            $rules['settings.print.fields'] = ['required_with:settings.print', 'array', 'min:1', 'max:40'];
            $rules['settings.print.fields.*.key'] = ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,49}$/', 'distinct'];
            $rules['settings.print.fields.*.label'] = ['required', 'string', 'max:100'];
            $rules['settings.print.fields.*.type'] = ['required', 'in:text,textarea,date,items'];
            $rules['settings.print.fields.*.required'] = ['required', 'boolean'];
        }
        if (! $types) {
            foreach (['client_id', 'doc_type_id'] as $field) {
                $rules[$field] = ['required', 'integer', 'min:1'];
            }
            foreach (['executor_id', 'customer_id'] as $field) {
                $rules[$field] = ['nullable', 'integer', 'min:1'];
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

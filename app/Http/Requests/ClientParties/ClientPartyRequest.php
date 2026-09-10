<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientParties;

abstract class ClientPartyRequest extends \App\Http\Requests\Companies\CompanyDirectoryRequest
{
    public function rules(): array
    {
        if ($this->route('party') === 'companies') {
            $rules = parent::rules();
            $rules['okpo'] = ['nullable', 'string', 'max:255'];
        } else {
            $rules = ['name' => ['required', 'string', 'max:255'], 'status' => ['present', 'nullable', 'integer', 'in:0,1,2'], 'tenant_id' => ['prohibited']];
            foreach (['shortname', 'firstname', 'middlename', 'lastname', 'phone', 'email', 'passport_seria', 'passport_number', 'passport_kem', 'passport_code', 'address_reg', 'vodud_nomer'] as $field) {
                $rules[$field] = ['nullable', 'string', 'max:255'];
            }
            $rules['email'][] = 'email';
            foreach (['birthday', 'passport_date', 'vodud_date'] as $field) {
                $rules[$field] = ['nullable', 'date_format:Y-m-d'];
            }
            foreach (['user_id', 'manager_id'] as $field) {
                $rules[$field] = ['nullable', 'integer', 'min:1', 'max:2147483647'];
            }
        }
        $rules['client_id'] = ['nullable', 'integer', 'min:1', 'max:2147483647'];

        return $rules;
    }
}

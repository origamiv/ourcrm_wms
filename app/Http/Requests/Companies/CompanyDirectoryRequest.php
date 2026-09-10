<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use App\Http\BaseRequest;

abstract class CompanyDirectoryRequest extends BaseRequest
{
    public function rules(): array
    {
        $scoped = $this->route('companyId') !== null;
        $contact = $scoped || $this->route('directory') === 'company_contacts';
        $rules = ['name' => ['required', 'string', 'max:255'], 'shortname' => ['required', 'string', 'max:255'], 'status' => ['present', 'nullable', 'integer', 'in:0,1,2'], 'tenant_id' => ['prohibited']];
        foreach (['fullname', 'inn', 'kpp', 'ogrn', 'phone', 'email', 'site', 'director_fio', 'director_position', 'bank', 'bik', 'korr_schet', 'rasch_schet'] as $field) {
            $rules[$field] = $contact ? ['prohibited'] : ['nullable', 'string', 'max:255'];
        }
        if (! $contact) {
            $rules['email'][] = 'email';
        }
        $rules['company_id'] = $contact ? [$scoped ? 'sometimes' : 'required', 'integer', 'min:1', 'max:2147483647'] : ['prohibited'];
        $rules['val'] = $contact ? ['nullable', 'string', 'max:255'] : ['prohibited'];

        $rules['src'] = $contact ? ['prohibited'] : ['sometimes', 'array:telegram,opf,accountant_position,accountant_fio,legal_address,is_own,is_client,is_partner'];
        if (! $contact) {
            foreach (['telegram', 'opf', 'accountant_position', 'accountant_fio', 'legal_address'] as $field) {
                $rules['src.'.$field] = ['nullable', 'string', 'max:255'];
            }
            foreach (['is_own', 'is_client', 'is_partner'] as $field) {
                $rules['src.'.$field] = ['sometimes', 'boolean'];
            }
        }

        return $rules;
    }
}

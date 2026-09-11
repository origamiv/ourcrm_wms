<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientCatalogs;

use App\Http\BaseRequest;

abstract class ClientCatalogRequest extends BaseRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'shortname' => ['nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'status' => ['present', 'nullable', 'integer', 'in:0,1,2,3'],
            'tenant_id' => ['prohibited'],
        ];
        if ($this->route('client_catalog') === 'accounts') {
            $rules += [
                'client_id' => ['required', 'integer', 'min:1'],
                'host' => ['nullable', 'string', 'max:255'],
                'login' => ['nullable', 'string', 'max:255'],
                'pass' => ['nullable', 'string', 'max:255'],
                'token' => ['nullable', 'string', 'max:10000'],
                'descr' => ['nullable', 'string', 'max:10000'],
                'group_id' => ['nullable', 'integer', 'min:1'],
                'server_id' => ['nullable', 'integer', 'min:1'],
                'src' => ['nullable', 'array'],
            ];
        }

        return $rules;
    }
}

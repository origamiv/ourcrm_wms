<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessCatalogs;

use App\Http\BaseRequest;

abstract class CatalogRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'status' => $this->route('catalog') === 'roles' ? ['required', 'integer', 'in:1,2'] : ['present', 'nullable', 'integer', 'in:0,1,2,3'],
            'description' => $this->route('catalog') === 'roles' ? ['nullable', 'string', 'max:10000'] : ['prohibited'],
            'resource' => $this->route('catalog') === 'permissions' ? ['required', 'string', 'max:255'] : ['prohibited'],
            'tenant_id' => ['prohibited'], 'system' => ['prohibited'], 'deleted_at' => ['prohibited'],
            'company_id' => ['prohibited'], 'module_id' => ['prohibited'], 'feature_id' => ['prohibited'], 'tags' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'name' => 'Название', 'slug' => 'Код', 'resource' => 'Ресурс', 'description' => 'Описание', 'status' => 'Статус'];
    }
}

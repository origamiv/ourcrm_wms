<?php

declare(strict_types=1);

namespace App\Http\Requests\References;

use App\Http\BaseRequest;

abstract class ReferenceRequest extends BaseRequest
{
    public function rules(): array
    {
        $type = $this->route('reference');
        $rules = ['name' => ['required', 'string', 'max:255'], 'status' => ['present', $type === 'files' ? 'required' : 'nullable', 'integer', 'in:0,1,2'], 'tenant_id' => ['prohibited']];
        $fields = match ($type) {
            'modules' => ['shortname', 'fn', 'domain'],
            'features' => ['shortname'],
            default => ['path', 'category', 'ext'],
        };
        foreach ($fields as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }
        if ($type === 'modules') {
            $rules['descr'] = ['nullable', 'string', 'max:10000'];
        }
        foreach (match ($type) {
            'features' => ['module_id'],
            'icons', 'files' => ['company_id', 'user_id', 'size'],
            default => [],
        } as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0', 'max:2147483647'];
        }
        if (in_array($type, ['features', 'files'], true)) {
            $rules[$type === 'features' ? 'is_resource' : 'is_s3'] = ['nullable', 'integer', 'in:0,1'];
        }

        return $rules;
    }
}

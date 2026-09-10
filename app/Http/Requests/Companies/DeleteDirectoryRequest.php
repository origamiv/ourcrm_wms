<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use App\Http\BaseRequest;

final class DeleteDirectoryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'string', 'regex:/^[0-9]+$/'], 'tenant_id' => ['prohibited']];
    }
}

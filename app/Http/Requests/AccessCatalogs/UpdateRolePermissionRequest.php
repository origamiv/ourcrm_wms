<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessCatalogs;

use App\Http\BaseRequest;

final class UpdateRolePermissionRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean'], 'version' => ['required', 'string', 'regex:/^[0-9]+$/'], 'tenant_id' => ['prohibited']];
    }
}

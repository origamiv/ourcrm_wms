<?php

declare(strict_types=1);

namespace App\Http\Requests\Integration;

use App\Http\BaseRequest;

final class DeleteIntegrationRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['version' => ['required', 'string', 'regex:/^[0-9]+$/'], 'tenant_id' => ['prohibited']];
    }
}

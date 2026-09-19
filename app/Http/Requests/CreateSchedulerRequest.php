<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\BaseRequest;

final class CreateSchedulerRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'task_key' => ['required', 'string', 'max:255'], 'params' => ['nullable', 'array'], 'schedule' => ['required', 'array'], 'status' => ['sometimes', 'integer', 'in:0,1']];
    }
}

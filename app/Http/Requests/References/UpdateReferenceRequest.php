<?php

declare(strict_types=1);

namespace App\Http\Requests\References;

final class UpdateReferenceRequest extends ReferenceRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

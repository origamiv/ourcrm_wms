<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

final class UpdateDirectoryRequest extends CompanyDirectoryRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

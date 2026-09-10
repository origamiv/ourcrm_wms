<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

final class UpdateDocumentRequest extends DocumentRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['required', 'string', 'regex:/^[0-9]+$/']];
    }
}

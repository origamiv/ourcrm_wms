<?php

declare(strict_types=1);

namespace OurCRM;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BaseRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, response([
            'success' => false,
            'message' => 'Ошибки валидации',
            'errors' => $validator->errors(),
        ], 422));
    }
}

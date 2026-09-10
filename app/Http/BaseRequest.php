<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function attributes(): array
    {
        return ['name' => 'Имя', 'email' => 'Email', 'phone' => 'Телефон', 'password' => 'Пароль', 'version' => 'Версия записи'];
    }

    public function messages(): array
    {
        return ['required' => 'Поле «:attribute» обязательно.', 'email' => 'Введите корректный email.',
            'string' => 'Поле «:attribute» должно быть строкой.', 'max' => 'Поле «:attribute» слишком длинное.',
            'confirmed' => 'Пароль и подтверждение не совпадают.', 'prohibited' => 'Поле «:attribute» нельзя передавать в этой операции.',
            'regex' => 'Недопустимое значение поля «:attribute».'];
    }
}

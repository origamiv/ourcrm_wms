<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class ServiceAccountRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое название';
        $attributes['service_id'] = 'Сервис';
        $attributes['login'] = 'Логин';
        $attributes['password'] = 'Пароль';
        $attributes['token'] = 'Токен';
        $attributes['options'] = 'Параметры';
        $attributes['status'] = 'Статус';
        $attributes['balance'] = 'Баланс';
        $attributes['cnt'] = 'Количество';

        return $attributes;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ($this->isMethod('post')) ? $this->createRules() : $this->updateRules();
    }

    public function createRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'service_id' => 'nullable|integer',
            'login' => 'nullable|string',
            'password' => 'nullable|string',
            'token' => 'nullable|string',
            'options' => 'nullable|json',
            'status' => 'nullable|integer',
            'balance' => 'nullable|double',
            'cnt' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'service_id' => 'nullable|integer',
            'login' => 'nullable|string',
            'password' => 'nullable|string',
            'token' => 'nullable|string',
            'options' => 'nullable|json',
            'status' => 'nullable|integer',
            'balance' => 'nullable|double',
            'cnt' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AccountRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['user_id'] = 'Пользователь';
        $attributes['messenger_id'] = 'Мессенджер';
        $attributes['login'] = 'Логин';
        $attributes['code'] = 'Код';
        $attributes['password'] = 'Пароль';
        $attributes['is_2fa'] = 'Включена 2ФА';
        $attributes['pass2fa'] = 'Пароль 2FA';
        $attributes['telegram_id'] = 'ID в Телеграмм';
        $attributes['tas_session_status'] = 'Статус сессии';
        $attributes['tas_session_expires'] = 'Дата истечения сессии';
        $attributes['first_name'] = 'Имя';
        $attributes['last_name'] = 'Фамилия';
        $attributes['username'] = 'Псевдоним';
        $attributes['src'] = 'Инфо об аккаунте';
        $attributes['options'] = 'Опции';
        $attributes['slot'] = 'Слот ';
        $attributes['last_used_at'] = 'Время последнего использования ';
        $attributes['new_messages_last_check'] = 'Время последней проверки сообщений';
        $attributes['phone_code_hash'] = 'phone_code_hash';
        $attributes['cnt'] = 'Количество сообщений';
        $attributes['status'] = 'Статус';
        $attributes['status_messenger'] = 'Статус в мессенджере';
        $attributes['cnt_people'] = 'cnt_people';
        $attributes['tas_port'] = 'Port для сессии';
        $attributes['port'] = 'port';
        $attributes['fn_avatar'] = 'fn_avatar';
        $attributes['icon'] = 'icon';
        $attributes['tagged_at'] = 'tagged_at';
        $attributes['mode'] = 'mode';

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
            'user_id' => 'nullable|integer',
            'messenger_id' => 'nullable|integer',
            'login' => 'nullable|string',
            'code' => 'nullable|string',
            'password' => 'nullable|string',
            'is_2fa' => 'nullable|integer',
            'pass2fa' => 'nullable|string',
            'telegram_id' => 'nullable|string',
            'tas_session_status' => 'nullable|text',
            'tas_session_expires' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string',
            'src' => 'nullable|jsonb',
            'options' => 'nullable|jsonb',
            'slot' => 'nullable|integer',
            'last_used_at' => 'nullable|date',
            'new_messages_last_check' => 'nullable|date',
            'phone_code_hash' => 'nullable|string',
            'cnt' => 'nullable|integer',
            'status' => 'nullable|integer',
            'status_messenger' => 'nullable|integer',
            'cnt_people' => 'nullable|integer',
            'tas_port' => 'nullable|string',
            'port' => 'nullable|integer',
            'fn_avatar' => 'nullable|string',
            'icon' => 'nullable|string',
            'tagged_at' => 'nullable|date',
            'mode' => 'nullable|string',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'messenger_id' => 'nullable|integer',
            'login' => 'nullable|string',
            'code' => 'nullable|string',
            'password' => 'nullable|string',
            'is_2fa' => 'nullable|integer',
            'pass2fa' => 'nullable|string',
            'telegram_id' => 'nullable|string',
            'tas_session_status' => 'nullable|text',
            'tas_session_expires' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string',
            'src' => 'nullable|jsonb',
            'options' => 'nullable|jsonb',
            'slot' => 'nullable|integer',
            'last_used_at' => 'nullable|date',
            'new_messages_last_check' => 'nullable|date',
            'phone_code_hash' => 'nullable|string',
            'cnt' => 'nullable|integer',
            'status' => 'nullable|integer',
            'status_messenger' => 'nullable|integer',
            'cnt_people' => 'nullable|integer',
            'tas_port' => 'nullable|string',
            'port' => 'nullable|integer',
            'fn_avatar' => 'nullable|string',
            'icon' => 'nullable|string',
            'tagged_at' => 'nullable|date',
            'mode' => 'nullable|string',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

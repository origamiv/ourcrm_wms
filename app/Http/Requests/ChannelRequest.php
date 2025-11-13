<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class ChannelRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['account_id'] = 'Аккаунт';
        $attributes['bot_id'] = 'Бот';
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['channel'] = 'Название в мессенджере';
        $attributes['username'] = 'Псевдоним';
        $attributes['phone'] = 'Телефон';
        $attributes['fn_avatar'] = 'Аватар';
        $attributes['type_channel'] = 'Тип канала';
        $attributes['cnt'] = 'Количество сообщений';
        $attributes['cnt_parsed'] = 'Количество полученных сообщений';
        $attributes['cnt_people'] = 'Количество участников';
        $attributes['cnt_people_parsed'] = 'Количество полученных участников';
        $attributes['can_view_participants'] = 'Можно просматривать участников';
        $attributes['date_last_message'] = 'Время последнего сообщения';
        $attributes['date_last_check'] = 'Время последней проверки сообщений';
        $attributes['frequency'] = 'Частота сообщений';
        $attributes['status'] = 'Статус';
        $attributes['srcDialog'] = 'Исходник';
        $attributes['src'] = 'Исходник';
        $attributes['last_message_id'] = 'Последнее сообщение';
        $attributes['cnt_unread'] = 'Число непрочитанных сообщений';
        $attributes['date_last_read'] = 'Время прочтения сообщений';
        $attributes['last_message_src'] = 'last_message_src';
        $attributes['tagged_at'] = 'tagged_at';

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
            'account_id' => 'nullable|integer',
            'bot_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'channel' => 'nullable|string',
            'username' => 'nullable|string',
            'phone' => 'nullable|string',
            'fn_avatar' => 'nullable|string',
            'type_channel' => 'nullable|integer',
            'cnt' => 'nullable|integer',
            'cnt_parsed' => 'nullable|integer',
            'cnt_people' => 'nullable|integer',
            'cnt_people_parsed' => 'nullable|integer',
            'can_view_participants' => 'nullable|integer',
            'date_last_message' => 'nullable|date',
            'date_last_check' => 'nullable|date',
            'frequency' => 'nullable|integer',
            'status' => 'nullable|integer',
            'srcDialog' => 'nullable|jsonb',
            'src' => 'nullable|jsonb',
            'last_message_id' => 'nullable|integer',
            'cnt_unread' => 'nullable|integer',
            'date_last_read' => 'nullable|date',
            'last_message_src' => 'nullable|json',
            'tagged_at' => 'nullable|date',
        ];
    }

    public function updateRules()
    {
        return [
            'account_id' => 'nullable|integer',
            'bot_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'channel' => 'nullable|string',
            'username' => 'nullable|string',
            'phone' => 'nullable|string',
            'fn_avatar' => 'nullable|string',
            'type_channel' => 'nullable|integer',
            'cnt' => 'nullable|integer',
            'cnt_parsed' => 'nullable|integer',
            'cnt_people' => 'nullable|integer',
            'cnt_people_parsed' => 'nullable|integer',
            'can_view_participants' => 'nullable|integer',
            'date_last_message' => 'nullable|date',
            'date_last_check' => 'nullable|date',
            'frequency' => 'nullable|integer',
            'status' => 'nullable|integer',
            'srcDialog' => 'nullable|jsonb',
            'src' => 'nullable|jsonb',
            'last_message_id' => 'nullable|integer',
            'cnt_unread' => 'nullable|integer',
            'date_last_read' => 'nullable|date',
            'last_message_src' => 'nullable|json',
            'tagged_at' => 'nullable|date',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

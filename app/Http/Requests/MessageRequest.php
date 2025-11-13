<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class MessageRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['account_id'] = 'Аккаунт';
        $attributes['bot_id'] = 'Бот';
        $attributes['messenger_id'] = 'Мессенджер';
        $attributes['message_id'] = 'ИД сообщения';
        $attributes['channel_id'] = 'Канал';
        $attributes['channel'] = 'ИД канала в мессенджере';
        $attributes['channel_name'] = 'Название канала';
        $attributes['thread_id'] = 'ID темы';
        $attributes['thread'] = 'Тема в мессенджере';
        $attributes['message'] = 'Сообщение';
        $attributes['files'] = 'Сообщение';
        $attributes['msg_date'] = 'Время сообщения';
        $attributes['napr'] = 'Направление';
        $attributes['from_id'] = 'Отправитель';
        $attributes['from_name'] = 'Имя отправителя';
        $attributes['src'] = 'Исходник';
        $attributes['type_msg'] = 'Тип сообщения';
        $attributes['user'] = 'Псевдоним';
        $attributes['comment'] = 'Примечание';
        $attributes['resend_status'] = 'Статус пересылки';
        $attributes['phone'] = 'Телефон';
        $attributes['channel_id_our'] = 'ID канала';
        $attributes['date_view'] = 'Время чтения сообщений';
        $attributes['parent_message_id'] = 'Связанное сообщение';
        $attributes['messenger_user_id'] = 'Пользователь мессенджера';
        $attributes['is_hidden_for_user'] = 'Скрытое';
        $attributes['status'] = 'Статус';
        $attributes['is_media'] = 'Есть вложения';
        $attributes['comments_cnt'] = 'кол-во комментариев';
        $attributes['replies_cnt'] = 'кол-во ответов';
        $attributes['type_msg_id'] = 'тип сообщения';
        $attributes['is_read'] = 'Сообщение прочитано или нет';
        $attributes['parent_message_src'] = 'parent_message_src';
        $attributes['is_tagged'] = 'is_tagged';
        $attributes['reaction'] = 'reaction';
        $attributes['edited_status'] = 'edited_status';
        $attributes['edited_cnt'] = 'edited_cnt';

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
            'messenger_id' => 'nullable|integer',
            'message_id' => 'nullable|string',
            'channel_id' => 'nullable|integer',
            'channel' => 'nullable|string',
            'channel_name' => 'nullable|string',
            'thread_id' => 'nullable|integer',
            'thread' => 'nullable|string',
            'message' => 'nullable',
            'files' => 'nullable|json',
            'msg_date' => 'nullable|date',
            'napr' => 'nullable|integer',
            'from_id' => 'nullable|string',
            'from_name' => 'nullable|string',
            'src' => 'nullable|jsonb',
            'type_msg' => 'nullable|string',
            'user' => 'nullable|string',
            'comment' => 'nullable|string',
            'resend_status' => 'nullable|integer',
            'phone' => 'nullable|string',
            'channel_id_our' => 'nullable|integer',
            'date_view' => 'nullable|date',
            'parent_message_id' => 'nullable|string',
            'messenger_user_id' => 'nullable|string',
            'is_hidden_for_user' => 'nullable|integer',
            'status' => 'nullable|integer',
            'is_media' => 'nullable|integer',
            'comments_cnt' => 'nullable|integer',
            'replies_cnt' => 'nullable|integer',
            'type_msg_id' => 'nullable|integer',
            'is_read' => 'nullable|integer',
            'parent_message_src' => 'nullable|json',
            'is_tagged' => 'nullable|integer',
            'reaction' => 'nullable|json',
            'edited_status' => 'nullable|integer',
            'edited_cnt' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'account_id' => 'nullable|integer',
            'bot_id' => 'nullable|integer',
            'messenger_id' => 'nullable|integer',
            'message_id' => 'nullable|string',
            'channel_id' => 'nullable|integer',
            'channel' => 'nullable|string',
            'channel_name' => 'nullable|string',
            'thread_id' => 'nullable|integer',
            'thread' => 'nullable|string',
            'message' => 'nullable',
            'files' => 'nullable|json',
            'msg_date' => 'nullable|date',
            'napr' => 'nullable|integer',
            'from_id' => 'nullable|string',
            'from_name' => 'nullable|string',
            'src' => 'nullable|jsonb',
            'type_msg' => 'nullable|string',
            'user' => 'nullable|string',
            'comment' => 'nullable|string',
            'resend_status' => 'nullable|integer',
            'phone' => 'nullable|string',
            'channel_id_our' => 'nullable|integer',
            'date_view' => 'nullable|date',
            'parent_message_id' => 'nullable|string',
            'messenger_user_id' => 'nullable|string',
            'is_hidden_for_user' => 'nullable|integer',
            'status' => 'nullable|integer',
            'is_media' => 'nullable|integer',
            'comments_cnt' => 'nullable|integer',
            'replies_cnt' => 'nullable|integer',
            'type_msg_id' => 'nullable|integer',
            'is_read' => 'nullable|integer',
            'parent_message_src' => 'nullable|json',
            'is_tagged' => 'nullable|integer',
            'reaction' => 'nullable|json',
            'edited_status' => 'nullable|integer',
            'edited_cnt' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

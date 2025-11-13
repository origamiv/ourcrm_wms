<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class FileRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое название';
        $attributes['account_id'] = 'Аккаунт';
        $attributes['channel_id'] = 'Канал';
        $attributes['message_id'] = 'Сообщение';
        $attributes['cnt'] = 'Кол-во скачиваний';
        $attributes['path'] = 'Путь  ';
        $attributes['status'] = 'Статус';
        $attributes['src'] = 'исходник сообщения';

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
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'message_id' => 'nullable|integer',
            'cnt' => 'nullable|integer',
            'path' => 'nullable|string',
            'status' => 'nullable|integer',
            'src' => 'nullable|json',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'message_id' => 'nullable|integer',
            'cnt' => 'nullable|integer',
            'path' => 'nullable|string',
            'status' => 'nullable|integer',
            'src' => 'nullable|json',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

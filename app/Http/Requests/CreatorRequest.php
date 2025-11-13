<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class CreatorRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['date_create'] = 'Дата и время';
        $attributes['cron'] = 'Расписание';
        $attributes['account_id'] = 'Аккаунт';
        $attributes['channel_id'] = 'Канал';
        $attributes['prompt'] = 'Промт';
        $attributes['options'] = 'Настройки';
        $attributes['status'] = 'Статус';

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
            'date_create' => 'nullable|date',
            'cron' => 'nullable|date',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'prompt' => 'nullable|mediumtext',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'date_create' => 'nullable|date',
            'cron' => 'nullable|date',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'prompt' => 'nullable|mediumtext',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

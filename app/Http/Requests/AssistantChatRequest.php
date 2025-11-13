<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AssistantChatRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['assistant_id'] = 'Ассистент';
        $attributes['account_id'] = 'Аккаунт';
        $attributes['channel_id'] = 'Канал';
        $attributes['ext_thread_id'] = 'Тред ассистента';
        $attributes['direction'] = 'Направление';
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
            'assistant_id' => 'nullable|integer',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'ext_thread_id' => 'nullable|string',
            'direction' => 'nullable|string',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
            'cnt' =>'integer'
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'assistant_id' => 'nullable|integer',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'ext_thread_id' => 'nullable|string',
            'direction' => 'nullable|string',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
            'cnt' =>'integer'
        ];
    }

    public function authorize()
    {
        return true;
    }
}

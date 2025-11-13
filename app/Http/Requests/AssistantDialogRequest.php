<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AssistantDialogRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['assistant_id'] = 'Ассистент';
        $attributes['account_id'] = 'Аккаунт';
        $attributes['channel_id'] = 'Канал';
        $attributes['query'] = 'Запрос';
        $attributes['answer'] = 'Ответ';
        $attributes['direction'] = 'направление';
        $attributes['ext_run_id'] = 'ID запуска';
        $attributes['ext_run_data'] = 'Инфо запуска';
        $attributes['total_tokens'] = 'Потрачено токенов';
        $attributes['price'] = 'Цена';
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
            'query' => 'nullable|text',
            'answer' => 'nullable|text',
            'direction' => 'nullable|string',
            'ext_run_id' => 'nullable|string',
            'ext_run_data' => 'nullable',
            'total_tokens' => 'nullable|integer',
            'price' => 'nullable',
            'status' => 'nullable|integer',
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
            'query' => 'nullable|text',
            'answer' => 'nullable|text',
            'direction' => 'nullable|string',
            'ext_run_id' => 'nullable|string',
            'ext_run_data' => 'nullable',
            'total_tokens' => 'nullable|integer',
            'price' => 'nullable',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

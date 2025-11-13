<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AutomateRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['account_id'] = 'Аккаунт';
        $attributes['channel_id'] = 'Канал';
        $attributes['assistant_id'] = 'Ассистент';
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
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'assistant_id' => 'nullable|integer',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'assistant_id' => 'nullable|integer',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

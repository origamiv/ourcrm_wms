<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AssistantChatsRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['assistant_id'] = 'Асистент айди';
        $attributes['account_id'] = 'Акаунт айди';
        $attributes['channel_id'] = 'Канал айди';
        $attributes['direction'] = 'Направление';
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
            'assistant_id' => 'nullable|integer',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'direction' => 'nullable|text',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'assistant_id' => 'nullable|integer',
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'direction' => 'nullable|text',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class SendMessageRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['account_id'] = 'account_id';
        $attributes['channel_id'] = 'channel_id';
        $attributes['message'] = 'Сообщение';
        $attributes['files'] = 'Файл';
        $attributes['response'] = 'Ответ после отправки';
        $attributes['status'] = '0 - new, 1 - sent, 2 - blocked, 3 - in progress';

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
            'channel_id' => 'nullable|integer',
            'message' => 'nullable|text',
            'files' => 'nullable|json',
            'response' => 'nullable|json',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'message' => 'nullable|text',
            'files' => 'nullable|json',
            'response' => 'nullable|json',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

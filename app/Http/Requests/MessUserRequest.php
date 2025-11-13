<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class MessUserRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['bot_id'] = 'bot_id';
        $attributes['account_id'] = 'account_id';
        $attributes['name'] = 'name';
        $attributes['messenger_id'] = 'messenger_id';
        $attributes['peer_id'] = 'peer_id';
        $attributes['first_name'] = 'first_name';
        $attributes['last_name'] = 'last_name';
        $attributes['photo_id'] = 'photo_id';
        $attributes['username'] = 'username';
        $attributes['src'] = 'src';
        $attributes['status'] = 'Статус';
        $attributes['activity'] = 'Активность';

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
            'bot_id' => 'nullable|integer',
            'account_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'messenger_id' => 'nullable|integer',
            'peer_id' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'photo_id' => 'nullable|string',
            'username' => 'nullable|string',
            'src' => 'nullable|json',
            'status' => 'nullable|string',
            'activity' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'bot_id' => 'nullable|integer',
            'account_id' => 'nullable|integer',
            'name' => 'nullable|string',
            'messenger_id' => 'nullable|integer',
            'peer_id' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'photo_id' => 'nullable|string',
            'username' => 'nullable|string',
            'src' => 'nullable|json',
            'status' => 'nullable|string',
            'activity' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

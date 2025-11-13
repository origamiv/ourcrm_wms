<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class ChannelUserRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['user_id'] = 'ID польз у нас';
        $attributes['channel_id'] = 'ID канала у нас';
        $attributes['mess_user_id'] = 'ID польз в мессенджере';
        $attributes['mess_channel'] = 'ID канала в мессенджере';
        $attributes['src'] = 'src';
        $attributes['account_id'] = 'account_id';

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
            'user_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'mess_user_id' => 'nullable|string',
            'mess_channel' => 'nullable|string',
            'src' => 'nullable|json',
            'account_id' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'user_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'mess_user_id' => 'nullable|string',
            'mess_channel' => 'nullable|string',
            'src' => 'nullable|json',
            'account_id' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class BlackLstRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['account_id'] = 'account_id';
        $attributes['channel_id'] = 'channel_id';
        $attributes['target_type'] = 'target_type';

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
            'target_type' => 'nullable|string',
        ];
    }

    public function updateRules()
    {
        return [
            'account_id' => 'nullable|integer',
            'channel_id' => 'nullable|integer',
            'target_type' => 'nullable|string',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

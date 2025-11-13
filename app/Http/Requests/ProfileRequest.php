<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class ProfileRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['profile_id'] = 'profile_id';
        $attributes['account_id'] = 'account_id';
        $attributes['status'] = 'status';

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
            'profile_id' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'profile_id' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

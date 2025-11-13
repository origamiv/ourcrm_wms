<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class ImportNumberRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['numbers'] = 'numbers';
        $attributes['status'] = 'status';
        $attributes['code'] = 'code';
        $attributes['pass2fa'] = 'pass2fa';

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
            'numbers' => 'nullable|string',
            'status' => 'nullable|integer',
            'code' => 'nullable|string',
            'pass2fa' => 'nullable|string',
        ];
    }

    public function updateRules()
    {
        return [
            'numbers' => 'nullable|string',
            'status' => 'nullable|integer',
            'code' => 'nullable|string',
            'pass2fa' => 'nullable|string',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

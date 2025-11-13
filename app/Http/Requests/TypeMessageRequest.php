<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class TypeMessageRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое название';
        $attributes['cnt'] = 'Кол-во использований';
        $attributes['other'] = 'Название в других сервисах ';
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
            'cnt' => 'nullable|integer',
            'other' => 'nullable|json',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'cnt' => 'nullable|integer',
            'other' => 'nullable|json',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

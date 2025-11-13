<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AssistantRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['prompt'] = 'Промпт';
        $attributes['data'] = 'Данные';
        $attributes['func'] = 'Функция';
        $attributes['ext_id'] = 'ID ассистента';
        $attributes['ext_model'] = 'Модель';
        $attributes['options'] = 'Опции';
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
            'prompt' => 'nullable',
            'data' => 'nullable',
            'func' => 'nullable|array',
            'ext_id' => 'nullable|string',
            'ext_model' => 'nullable|string',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'prompt' => 'nullable',
            'data' => 'nullable',
            'func' => 'nullable',
            'ext_id' => 'nullable|string',
            'ext_model' => 'nullable|string',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class PromptRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['name'] = 'Название';
        $attributes['shortname'] = 'Короткое';
        $attributes['tag'] = 'Тег';
        $attributes['category'] = 'Категория';
        $attributes['message'] = 'Промпт';
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
            'tag' => 'nullable|string',
            'category' => 'nullable|string',
            'message' => 'nullable|mediumtext',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function updateRules()
    {
        return [
            'name' => 'nullable|string',
            'shortname' => 'nullable|string',
            'tag' => 'nullable|string',
            'category' => 'nullable|string',
            'message' => 'nullable|mediumtext',
            'options' => 'nullable|jsonb',
            'status' => 'nullable|integer',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

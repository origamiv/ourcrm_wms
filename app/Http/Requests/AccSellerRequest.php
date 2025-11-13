<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class AccSellerRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['telegram_id'] = 'telegram_id';
        $attributes['name'] = 'name';
        $attributes['cnt_buy'] = 'cnt_buy';

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
            'telegram_id' => 'nullable|string',
            'name' => 'nullable|string',
            'cnt_buy' => 'nullable|string',
        ];
    }

    public function updateRules()
    {
        return [
            'telegram_id' => 'nullable|string',
            'name' => 'nullable|string',
            'cnt_buy' => 'nullable|string',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

<?php
namespace App\Http\Requests;

use OurCRM\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class DummyClass extends BaseRequest
{
     public function attributes()
     {
         $attributes=parent::attributes();
         //{$attributes}
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
            //{$rules}
        ];
    }

    public function updateRules()
    {
        return [
            //{$rules}
        ];
    }

    public function authorize()
    {
        return true;
    }
}

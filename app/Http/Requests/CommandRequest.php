<?php

declare(strict_types=1);

namespace App\Http\Requests;

use OurCRM\BaseRequest;

final class CommandRequest extends BaseRequest
{
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes['command'] = 'command';
        $attributes['shortname'] = 'shortname';
        $attributes['account_id'] = 'account_id';
        $attributes['command_arguments'] = 'command_arguments';
        $attributes['command_date'] = 'command_date';
        $attributes['status'] = '0 - новая, 1 - выполнена, 2 - возникла проблема, 3 - в процессе выполнения';
        $attributes['result'] = 'result';

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
            'command' => 'nullable|string',
            'shortname' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'command_arguments' => 'nullable|text',
            'command_date' => 'nullable|date',
            'status' => 'nullable|integer',
            'result' => 'nullable|json',
        ];
    }

    public function updateRules()
    {
        return [
            'command' => 'nullable|string',
            'shortname' => 'nullable|string',
            'account_id' => 'nullable|integer',
            'command_arguments' => 'nullable|text',
            'command_date' => 'nullable|date',
            'status' => 'nullable|integer',
            'result' => 'nullable|json',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

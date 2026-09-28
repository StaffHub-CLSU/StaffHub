<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('organization.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'position_name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
        ];
    }
}

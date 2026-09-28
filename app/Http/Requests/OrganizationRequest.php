<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('organization.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('department')?->department_id;

        return [
            'department_name' => ['required', 'string', 'max:255', Rule::unique('departments')->ignore($id, 'department_id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

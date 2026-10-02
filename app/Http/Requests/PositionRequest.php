<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('organization.manage') ?? false;
    }

    public function rules(): array
    {
        $positionId = $this->route('position')?->position_id;

        return [
            'position_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions')
                    ->where(fn ($query) => $query->where('department_id', $this->input('department_id')))
                    ->ignore($positionId, 'position_id'),
            ],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
        ];
    }
}

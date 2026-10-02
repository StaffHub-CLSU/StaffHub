<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees')->ignore($employee, 'employee_id'), Rule::unique('users')->ignore($employee->user_id, 'user_id')],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,position_id'],
            'gender' => ['required', 'string', 'max:20'],
            'birthdate' => ['required', 'date', 'before:today'],
            'contact_number' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'employment_status' => ['required', Rule::in(['Full-Time', 'Part-Time', 'Contractual'])],
            'basic_hourly_rate' => ['required', 'numeric', 'min:0'],
            'date_hired' => ['required', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['department_id', 'position_id'])) {
                return;
            }

            $position = Position::find($this->integer('position_id'));

            if ($position !== null && (int) $position->department_id !== $this->integer('department_id')) {
                $validator->errors()->add('position_id', 'The selected position must belong to the selected department.');
            }
        }];
    }
}

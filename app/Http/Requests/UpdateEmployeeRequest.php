<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}

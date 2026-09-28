<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,employee_id'],
            'attendance_date' => ['required', 'date'],
            'time_in' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date', 'after:time_in'],
            'status' => ['required', Rule::in(['Incomplete', 'Completed', 'Verified'])],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,employee_id'],
            'payroll_period_start' => ['required', 'date'],
            'payroll_period_end' => ['required', 'date', 'after_or_equal:payroll_period_start'],
            'bonuses' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

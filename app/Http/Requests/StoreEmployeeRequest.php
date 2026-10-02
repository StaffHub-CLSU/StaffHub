<?php

namespace App\Http\Requests;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees')],
            'username' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            $userByUsername = User::query()->where('username', $this->string('username')->toString())->first();
            $userByEmail = User::query()->where('email', $this->string('email')->toString())->first();

            if ($userByUsername !== null && ($userByEmail === null || ! $userByUsername->is($userByEmail))) {
                $validator->errors()->add('username', 'The username and email must belong to the same pending account.');
            }

            if ($userByEmail !== null && ($userByUsername === null || ! $userByEmail->is($userByUsername))) {
                $validator->errors()->add('email', 'The username and email must belong to the same pending account.');
            }

            if ($userByUsername !== null && ($userByUsername->employee !== null || $userByUsername->getRoleNames()->isNotEmpty())) {
                $validator->errors()->add('username', 'This username is already assigned to an account.');
            }

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

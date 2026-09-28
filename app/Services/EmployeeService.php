<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
            $user->assignRole('Employee');

            return Employee::create(Arr::except($data, ['username', 'password', 'password_confirmation']) + ['user_id' => $user->user_id]);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            $employee->update($data);
            $employee->user->update(['name' => trim($data['first_name'].' '.$data['last_name']), 'email' => $data['email']]);

            return $employee->fresh(['department', 'position', 'user']);
        });
    }

    public function archive(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $employee->update(['is_active' => false]);
            $employee->user->update(['is_active' => false]);
        });
    }
}

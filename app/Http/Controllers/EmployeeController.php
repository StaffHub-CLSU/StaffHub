<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        return Inertia::render('Admin/Employees', [
            'employees' => Employee::with(['department', 'position', 'user'])->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($employeeQuery) => $employeeQuery->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%")))->latest('employee_id')->paginate(10)->withQueryString(),
            'departments' => Department::orderBy('department_name')->get(),
            'positions' => Position::with('department')->orderBy('position_name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request, EmployeeService $employees): RedirectResponse
    {
        $employee = $employees->create($request->validated());

        return to_route('admin.employees.index')->with('success', "{$employee->full_name} was created.");
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $employees->update($employee, $request->validated());

        return to_route('admin.employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeService $employees): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $employees->archive($employee);

        return to_route('admin.employees.index')->with('success', 'Employee archived.');
    }
}

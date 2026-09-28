<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payroll::class);

        return Inertia::render('Admin/Payroll', ['employees' => Employee::active()->with('department')->orderBy('last_name')->get(), 'payrolls' => Payroll::with('employee.department')->latest('processed_date')->paginate(15)]);
    }

    public function store(PayrollRequest $request, PayrollService $service): RedirectResponse
    {
        $employee = Employee::findOrFail($request->integer('employee_id'));
        $service->calculate($employee, $request->validated(), $request->user());

        return to_route('admin.payroll.index')->with('success', 'Payroll calculated.');
    }

    public function destroy(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->authorize('delete', $payroll);
        $payroll->delete();

        return to_route('admin.payroll.index')->with('success', 'Payroll deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeePortalController extends Controller
{
    private function employee(Request $request)
    {
        return $request->user()->employee()->with(['department', 'position'])->firstOrFail();
    }

    public function dashboard(Request $request): Response
    {
        $employee = $this->employee($request);

        return Inertia::render('Employee/Dashboard', ['employee' => $employee, 'today' => Attendance::whereBelongsTo($employee)->whereDate('attendance_date', today())->first(), 'history' => Attendance::whereBelongsTo($employee)->latest('attendance_date')->limit(10)->get(), 'lastPayroll' => Payroll::whereBelongsTo($employee)->latest('processed_date')->first()]);
    }

    public function attendance(Request $request): Response
    {
        $employee = $this->employee($request);

        return Inertia::render('Employee/Attendance', ['attendance' => Attendance::whereBelongsTo($employee)->latest('attendance_date')->paginate(12)]);
    }

    public function clock(Request $request, string $action, AttendanceService $service): RedirectResponse
    {
        $this->authorize('create', Attendance::class);
        $service->clock($this->employee($request), $action);

        return back()->with('success', $action === 'in' ? 'Clock-in recorded.' : 'Clock-out recorded.');
    }

    public function payroll(Request $request): Response
    {
        $employee = $this->employee($request);

        return Inertia::render('Employee/Payroll', ['payrolls' => Payroll::whereBelongsTo($employee)->latest('payroll_period_start')->paginate(24)]);
    }

    public function profile(Request $request): Response
    {
        return Inertia::render('Employee/Profile', ['employee' => $this->employee($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);
        $this->authorize('update', $employee);
        $data = $request->validate(['contact_number' => ['required', 'string', 'max:50'], 'address' => ['nullable', 'string', 'max:1000']]);
        $employee->update($data);

        return back()->with('success', 'Profile updated.');
    }
}

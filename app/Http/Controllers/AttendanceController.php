<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Attendance::class);

        return Inertia::render('Admin/Attendance', ['attendance' => Attendance::with('employee.department')->latest('attendance_date')->paginate(15)->withQueryString(), 'employees' => Employee::active()->orderBy('last_name')->get()]);
    }

    public function store(AttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $hours = isset($data['time_in'], $data['time_out']) ? round(now()->parse($data['time_in'])->diffInSeconds(now()->parse($data['time_out'])) / 3600, 2) : 0;
        Attendance::updateOrCreate(['employee_id' => $data['employee_id'], 'attendance_date' => $data['attendance_date']], $data + ['total_hours' => $hours]);

        return to_route('admin.attendance.index')->with('success', 'Attendance saved.');
    }

    public function update(AttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);
        $data = $request->validated();
        $timeIn = isset($data['time_in']) ? now()->parse($data['time_in']) : $attendance->time_in;
        $timeOut = isset($data['time_out']) ? now()->parse($data['time_out']) : $attendance->time_out;

        $data['total_hours'] = $timeIn !== null && $timeOut !== null
            ? round($timeIn->diffInSeconds($timeOut) / 3600, 2)
            : 0;

        $attendance->update($data);

        return to_route('admin.attendance.index')->with('success', 'Attendance updated.');
    }

    public function destroy(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);
        $attendance->delete();

        return to_route('admin.attendance.index')->with('success', 'Attendance deleted.');
    }

    public function verify(Request $request, Attendance $attendance, AttendanceService $service): RedirectResponse
    {
        $this->authorize('verify', $attendance);
        $service->verify($attendance);

        return back()->with('success', 'Attendance verified.');
    }
}

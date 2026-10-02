<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    private function shared(): array
    {
        return [
            'departments' => Department::orderBy('department_name')->get(),
            'positions' => Position::orderBy('position_name')->get(),
        ];
    }

    public function dashboard(): Response
    {
        $totalEmployees = Employee::query()->where('is_active', true)->count();
        $presentToday = Attendance::query()
            ->whereDate('attendance_date', today())
            ->whereNotNull('time_in')
            ->distinct('employee_id')
            ->count('employee_id');
        $attendanceByDay = Attendance::query()
            ->selectRaw('attendance_date, count(distinct employee_id) as total')
            ->whereBetween('attendance_date', [today()->subDays(6), today()])
            ->groupBy('attendance_date')
            ->pluck('total', 'attendance_date');
        $payrollByMonth = Payroll::query()
            ->selectRaw("DATE_FORMAT(processed_date, '%b %Y') as label, sum(net_salary) as total")
            ->whereNotNull('processed_date')
            ->where('processed_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(processed_date, '%b %Y'), DATE_FORMAT(processed_date, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(processed_date, '%Y-%m')")
            ->get();
        $departmentCounts = Employee::query()
            ->where('is_active', true)
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');
        $departments = Department::query()->orderBy('department_name')->get(['department_id', 'department_name']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'absent_today' => max(0, $totalEmployees - $presentToday),
                'total_attendance_records' => Attendance::query()->count(),
                'payrolls_processed' => Payroll::query()->count(),
                'total_departments' => $departments->count(),
            ],
            'attendanceSeries' => collect(range(6, 0))->map(fn (int $offset): array => [
                'label' => today()->subDays($offset)->format('D'),
                'value' => (int) ($attendanceByDay[today()->subDays($offset)->toDateString()] ?? 0),
            ]),
            'payrollSeries' => $payrollByMonth->map(fn (Payroll $payroll): array => [
                'label' => $payroll->label,
                'value' => (float) $payroll->total,
            ]),
            'departmentSeries' => $departments->map(fn (Department $department): array => [
                'label' => $department->department_name,
                'value' => (int) ($departmentCounts[$department->department_id] ?? 0),
            ])->filter(fn (array $department): bool => $department['value'] > 0)->values(),
            'recentClockIns' => Attendance::query()
                ->with('employee')
                ->whereDate('attendance_date', today())
                ->whereNotNull('time_in')
                ->latest('time_in')
                ->limit(5)
                ->get(),
            'recentClockOuts' => Attendance::query()
                ->with('employee')
                ->whereDate('attendance_date', today())
                ->whereNotNull('time_out')
                ->latest('time_out')
                ->limit(5)
                ->get(),
            'activities' => ActivityLog::query()->latest('timestamp')->limit(8)->get(),
        ]);
    }

    public function employees(Request $request): Response
    {
        return Inertia::render('Admin/Employees', $this->shared() + [
            'employees' => Employee::with(['department', 'position', 'user'])
                ->when($request->search, fn ($query, $value) => $query->where(fn ($query) => $query
                    ->where('first_name', 'like', "%{$value}%")
                    ->orWhere('last_name', 'like', "%{$value}%")
                    ->orWhere('employee_code', 'like', "%{$value}%")))
                ->latest('employee_id')
                ->paginate(8)
                ->withQueryString(),
        ]);
    }

    public function organization(): Response
    {
        return Inertia::render('Admin/Organization', $this->shared());
    }

    public function attendance(Request $request): Response
    {
        return Inertia::render('Admin/Attendance', $this->shared() + [
            'attendance' => Attendance::with('employee.department')->latest('attendance_date')->paginate(10)->withQueryString(),
        ]);
    }

    public function payroll(Request $request): Response
    {
        return Inertia::render('Admin/Payroll', $this->shared() + [
            'employees' => Employee::active()->orderBy('last_name')->get(),
            'payrolls' => Payroll::with('employee.department')->latest('processed_date')->paginate(10),
        ]);
    }

    public function report(string $type): Response
    {
        $rows = match ($type) {
            'attendance' => Attendance::with('employee.department')->latest('attendance_date')->limit(1000)->get(),
            'payroll' => Payroll::with('employee.department')->latest('processed_date')->limit(1000)->get(),
            default => Employee::with(['department', 'position'])->latest('employee_id')->limit(1000)->get(),
        };

        return Inertia::render('Reports/Index', ['type' => $type, 'rows' => $rows] + $this->shared());
    }
}

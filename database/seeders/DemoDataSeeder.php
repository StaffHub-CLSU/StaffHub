<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds a small, realistic demonstration dataset:
 *
 *  - 5 fixed departments (Human Resources, Finance and Accounting,
 *    Information Technology, Operations, Administration)
 *  - 2 positions per department (one managerial, one rank-and-file)
 *  - 1 admin employee  (linked to the admin@staffhub.test User)
 *  - 1 manager per department (5 total)
 *  - 14 regular employees spread across departments with mixed statuses:
 *      ~9 Full-Time active, ~3 On Leave, ~2 Resigned / inactive
 *  - 10 working days of attendance history for every active employee
 *  - 1 semi-monthly payroll record (with deductions) for every active employee
 *
 * Demo login:
 *   URL      → /login
 *   Email    → admin@staffhub.test
 *   Password → Password123!
 */
class DemoDataSeeder extends Seeder
{
    // ─── Fixed department catalogue ────────────────────────────────────────────

    /** @var array<string, string> name → description */
    private const DEPARTMENTS = [
        'Human Resources'        => 'Handles employee relations, recruitment, and welfare programs.',
        'Finance and Accounting' => 'Manages financial records, budgeting, and payroll processing.',
        'Information Technology' => 'Maintains IT infrastructure, systems, and technical support.',
        'Operations'             => 'Oversees day-to-day operational activities of the organization.',
        'Administration'         => 'Provides administrative support and office management services.',
    ];

    /** @var array<string, array{managerial: string, rank: string}> */
    private const POSITIONS = [
        'Human Resources'        => ['managerial' => 'HR Manager',          'rank' => 'HR Officer'],
        'Finance and Accounting' => ['managerial' => 'Finance Manager',      'rank' => 'Accounting Clerk'],
        'Information Technology' => ['managerial' => 'IT Manager',           'rank' => 'IT Support Technician'],
        'Operations'             => ['managerial' => 'Operations Manager',   'rank' => 'Staff Associate'],
        'Administration'         => ['managerial' => 'Department Manager',   'rank' => 'Administrative Assistant'],
    ];

    public function run(): void
    {
        // ── 1. Build departments & positions ──────────────────────────────────
        $departments = $this->seedDepartments();

        // $positions['HR']['managerial'] / $positions['HR']['rank']
        $positions = $this->seedPositions($departments);

        // ── 2. Admin employee ──────────────────────────────────────────────────
        $this->seedAdminEmployee($departments, $positions);

        // ── 3. One manager per department ─────────────────────────────────────
        $managers = $this->seedManagers($departments, $positions);

        // ── 4. Regular employees (14) across departments ───────────────────────
        $regularEmployees = $this->seedRegularEmployees($departments, $positions);

        // ── 5. Attendance records (10 working days) for active employees ───────
        $activeEmployees = $managers->merge($regularEmployees)->filter(
            fn (Employee $e) => $e->is_active && $e->employment_status !== 'On Leave'
        );
        $this->seedAttendance($activeEmployees);

        // ── 6. Payroll records (last completed semi-monthly period) ────────────
        $this->seedPayroll($activeEmployees);
    }

    // ─── Step helpers ──────────────────────────────────────────────────────────

    /**
     * @return Collection<string, Department>   keyed by department_name
     */
    private function seedDepartments(): Collection
    {
        return collect(self::DEPARTMENTS)->map(
            fn (string $description, string $name) => Department::firstOrCreate(
                ['department_name' => $name],
                ['description'     => $description],
            )
        );
    }

    /**
     * @param  Collection<string, Department>  $departments
     * @return Collection<string, array<string, Position>>
     */
    private function seedPositions(Collection $departments): Collection
    {
        return $departments->mapWithKeys(function (Department $dept, string $name): array {
            $titles    = self::POSITIONS[$name];
            $managerial = Position::firstOrCreate(
                ['position_name' => $titles['managerial'], 'department_id' => $dept->department_id],
            );
            $rank = Position::firstOrCreate(
                ['position_name' => $titles['rank'], 'department_id' => $dept->department_id],
            );

            return [$name => ['managerial' => $managerial, 'rank' => $rank]];
        });
    }

    /**
     * Create an Employee record for the admin@staffhub.test User that
     * RolesAndPermissionsSeeder already seeded.  Does nothing if the user
     * already has an employee record.
     */
    private function seedAdminEmployee(Collection $departments, Collection $positions): void
    {
        $adminUser = User::where('username', 'admin')->first();

        if (! $adminUser || $adminUser->employee) {
            return;
        }

        $dept     = $departments->get('Administration');
        $position = $positions->get('Administration')['managerial'];

        Employee::factory()
            ->forDepartment($dept, $position)
            ->state([
                'user_id'           => $adminUser->user_id,
                'first_name'        => 'StaffHub',
                'last_name'         => 'Admin',
                'middle_name'       => null,
                'email'             => $adminUser->email,
                'employment_status' => 'Full-Time',
                'basic_hourly_rate' => 500.00,
                'date_hired'        => '2020-01-01',
                'is_active'         => true,
            ])
            ->create();
    }

    /**
     * @param  Collection<string, Department>                      $departments
     * @param  Collection<string, array<string, Position>>         $positions
     * @return Collection<int, Employee>
     */
    private function seedManagers(Collection $departments, Collection $positions): Collection
    {
        return $departments->values()->map(function (Department $dept) use ($positions): Employee {
            $deptName = $dept->department_name;
            $position = $positions->get($deptName)['managerial'];

            return Employee::factory()
                ->manager()
                ->forDepartment($dept, $position)
                ->create();
        });
    }

    /**
     * 14 regular employees spread across departments with realistic variety.
     *
     * Status breakdown (approximately):
     *   9 × Full-Time (active)
     *   3 × On Leave  (active = true, status = "On Leave")
     *   2 × Resigned  (active = false)
     *
     * @param  Collection<string, Department>               $departments
     * @param  Collection<string, array<string, Position>>  $positions
     * @return Collection<int, Employee>
     */
    private function seedRegularEmployees(Collection $departments, Collection $positions): Collection
    {
        $deptList = $departments->values();
        $employees = collect();

        // 9 active full-time employees spread across departments
        for ($i = 0; $i < 9; $i++) {
            $dept     = $deptList[$i % $deptList->count()];
            $position = $positions->get($dept->department_name)['rank'];

            $employees->push(
                Employee::factory()
                    ->active()
                    ->forDepartment($dept, $position)
                    ->create()
            );
        }

        // 3 employees on approved leave (still active in the system)
        for ($i = 0; $i < 3; $i++) {
            $dept     = $deptList[$i % $deptList->count()];
            $position = $positions->get($dept->department_name)['rank'];

            $employees->push(
                Employee::factory()
                    ->onLeave()
                    ->forDepartment($dept, $position)
                    ->create()
            );
        }

        // 2 resigned / inactive employees
        for ($i = 0; $i < 2; $i++) {
            $dept     = $deptList[$i % $deptList->count()];
            $position = $positions->get($dept->department_name)['rank'];

            $employees->push(
                Employee::factory()
                    ->inactive()
                    ->forDepartment($dept, $position)
                    ->create()
            );
        }

        return $employees;
    }

    /**
     * Create 10 working-day attendance records for each active employee.
     * The unique constraint is (employee_id, attendance_date), so we track
     * dates already inserted per employee.
     *
     * Attendance mix per employee (10 days):
     *   7 × Present, 1 × Late, 1 × Half-Day, 1 × Absent
     *
     * @param  Collection<int, Employee>  $employees
     */
    private function seedAttendance(Collection $employees): void
    {
        // Collect the last 14 calendar days and keep only weekdays (Mon–Fri)
        $workingDays = collect();
        $cursor      = Carbon::today();

        while ($workingDays->count() < 10) {
            if ($cursor->isWeekday()) {
                $workingDays->push($cursor->toDateString());
            }
            $cursor = $cursor->copy()->subDay();
        }

        // Statuses to cycle through across the 10 days
        $statusCycle = [
            'present', 'present', 'present', 'present', 'present',
            'present', 'present', 'late', 'halfDay', 'absent',
        ];

        foreach ($employees as $employee) {
            foreach ($workingDays as $index => $date) {
                $state = $statusCycle[$index];

                Attendance::factory()
                    ->forDate($date)
                    ->$state()
                    ->create(['employee_id' => $employee->employee_id]);
            }
        }
    }

    /**
     * Create one payroll record per active employee for the last completed
     * semi-monthly period, with statutory deductions applied.
     *
     * @param  Collection<int, Employee>  $employees
     */
    private function seedPayroll(Collection $employees): void
    {
        $now = Carbon::now();

        // Determine the last fully-completed semi-monthly period
        if ($now->day > 15) {
            // We are in the second half → last completed period = 1st–15th this month
            $start = $now->copy()->startOfMonth();
            $end   = $now->copy()->startOfMonth()->setDay(15);
        } else {
            // We are in the first half → last completed period = 16th–EOM last month
            $start = $now->copy()->subMonth()->setDay(16);
            $end   = $now->copy()->subMonth()->endOfMonth();
        }

        foreach ($employees as $employee) {
            $hourlyRate = (float) $employee->basic_hourly_rate;
            $hours      = fake()->randomFloat(2, 72.00, 96.00);
            $gross      = round($hours * $hourlyRate, 2);

            // Philippine statutory deductions
            $sss        = round($gross * 0.045, 2);
            $philHealth = round($gross * 0.025, 2);
            $pagIbig    = 100.00;
            $deductions = round($sss + $philHealth + $pagIbig, 2);
            $net        = round($gross - $deductions, 2);

            Payroll::create([
                'employee_id'          => $employee->employee_id,
                'payroll_period_start' => $start->toDateString(),
                'payroll_period_end'   => $end->toDateString(),
                'verified_hours'       => $hours,
                'hourly_rate'          => $hourlyRate,
                'gross_salary'         => $gross,
                'deductions'           => $deductions,
                'bonuses'              => 0.00,
                'net_salary'           => $net,
                'processed_by'         => null,
            ]);
        }
    }
}

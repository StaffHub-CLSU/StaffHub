<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 *
 * Filipino-style demo data factory for StaffHub.
 *
 * States available:
 *  - active()      → is_active = true,  employment_status = Full-Time (default)
 *  - inactive()    → is_active = false, employment_status = Resigned
 *  - onLeave()     → is_active = true,  employment_status = On Leave
 *  - partTime()    → employment_status = Part-Time, lower hourly rate
 *  - contractual() → employment_status = Contractual
 *  - admin()       → assigns 'Admin' Spatie role to the linked User after creation
 *  - manager()     → higher pay band, assigns 'Employee' role to linked User
 *
 * Relationship helpers:
 *  - forDepartment(Department, ?Position) → set department_id / position_id directly
 *  - withDepartment()                     → auto-creates a Department + Position
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /** Common Filipino first names (male & female). */
    private static array $filipinoFirstNames = [
        'Maria', 'Jose', 'Juan', 'Ana', 'Rosa', 'Carlos', 'Elena', 'Miguel',
        'Liza', 'Ramon', 'Cristina', 'Eduardo', 'Maricel', 'Ronald', 'Arlene',
        'Ferdinand', 'Corazon', 'Benedicto', 'Florencia', 'Renato', 'Teresita',
        'Danilo', 'Marilou', 'Nestor', 'Cynthia', 'Rodrigo', 'Melanie',
        'Alberto', 'Rosario', 'Roel', 'Gina', 'Emmanuel', 'Sheryl', 'Rogelio',
        'Cristy', 'Patrick', 'Aileen', 'Jayson', 'Lovely', 'Marlon', 'Charito',
    ];

    /** Common Filipino last names. */
    private static array $filipinoLastNames = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza',
        'Torres', 'Tinio', 'Castillo', 'Villanueva', 'Dela Cruz', 'Aquino',
        'Ramos', 'Flores', 'Gonzales', 'Salazar', 'Pascual', 'Dizon', 'Aguilar',
        'Manalo', 'Tolentino', 'Abad', 'Macapagal', 'Pangilinan', 'Silverio',
        'Delos Santos', 'Ilagan', 'Mercado', 'Soriano', 'Baluyot', 'Caballero',
        'Puno', 'Sabio', 'Magno', 'Lim', 'Sy', 'Tan', 'Ong', 'Co',
    ];

    /** Common Filipino middle names (maternal surnames used as middle name). */
    private static array $filipinoMiddleNames = [
        'Dela Peña', 'Magtibay', 'Buenaventura', 'Catindig', 'Manalang',
        'Paglinawan', 'Sarmiento', 'Batungbakal', 'Mabuting', 'Corpuz',
        'Dela Torre', 'Gatchalian', 'Ilustrisimo', 'Pimentel', 'Bayani',
    ];

    /** Philippine provincial / Metro Manila cities for address generation. */
    private static array $phCities = [
        'Cabanatuan City, Nueva Ecija',
        'San Jose City, Nueva Ecija',
        'Gapan City, Nueva Ecija',
        'Palayan City, Nueva Ecija',
        'Quezon City, Metro Manila',
        'Manila City, Metro Manila',
        'Makati City, Metro Manila',
        'Tarlac City, Tarlac',
        'San Fernando City, Pampanga',
        'Angeles City, Pampanga',
        'Olongapo City, Zambales',
        'Baguio City, Benguet',
    ];

    public function definition(): array
    {
        $firstName  = fake()->randomElement(self::$filipinoFirstNames);
        $lastName   = fake()->randomElement(self::$filipinoLastNames);
        $middleName = fake()->optional(0.80)->randomElement(self::$filipinoMiddleNames);

        // PH-style mobile number: 09XX-XXX-XXXX
        $contactNumber = '09' . fake()->numerify('########');

        // Hourly rate typical for Philippine provincial companies (PHP/hour)
        $hourlyRate = fake()->randomFloat(2, 75.00, 350.00);

        return [
            'employee_code'     => 'EMP-' . fake()->unique()->numerify('#####'),
            'user_id'           => User::factory(),
            'department_id'     => null,
            'position_id'       => null,
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'middle_name'       => $middleName,
            'gender'            => fake()->randomElement(['Male', 'Female']),
            'birthdate'         => fake()->dateTimeBetween('-55 years', '-21 years')->format('Y-m-d'),
            'email'             => fake()->unique()->safeEmail(),
            'contact_number'    => $contactNumber,
            'address'           => fake()->randomElement(self::$phCities),
            'employment_status' => 'Full-Time',
            'basic_hourly_rate' => $hourlyRate,
            'date_hired'        => fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'profile_picture'   => null,
            'is_active'         => true,
        ];
    }

    // ─── Employment-status states ──────────────────────────────────────────────

    /**
     * Active full-time employee (default state, exposed for clarity).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'         => true,
            'employment_status' => 'Full-Time',
        ]);
    }

    /**
     * Inactive / resigned employee no longer in the system.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'         => false,
            'employment_status' => 'Resigned',
        ]);
    }

    /**
     * Employee currently on approved leave (still active in the system).
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'         => true,
            'employment_status' => 'On Leave',
        ]);
    }

    /**
     * Part-time employee with a lower hourly rate.
     */
    public function partTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'         => true,
            'employment_status' => 'Part-Time',
            'basic_hourly_rate' => fake()->randomFloat(2, 60.00, 150.00),
        ]);
    }

    /**
     * Contractual / project-based employee.
     */
    public function contractual(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active'         => true,
            'employment_status' => 'Contractual',
        ]);
    }

    // ─── Role / seniority states ───────────────────────────────────────────────

    /**
     * Grants the 'Admin' Spatie role to the linked User after both records are
     * persisted.  Roles must already exist (run RolesAndPermissionsSeeder first).
     *
     * Usage: Employee::factory()->admin()->create()
     */
    public function admin(): static
    {
        return $this->afterCreating(function (Employee $employee): void {
            $employee->user?->assignRole('Admin');
        });
    }

    /**
     * Manager-level employee: higher pay band and 'Employee' role assigned.
     * Callers are responsible for setting an appropriate position_id.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_hourly_rate' => fake()->randomFloat(2, 250.00, 500.00),
        ])->afterCreating(function (Employee $employee): void {
            $employee->user?->assignRole('Employee');
        });
    }

    // ─── Relationship helpers ──────────────────────────────────────────────────

    /**
     * Wire the employee to an existing Department (and optionally a Position).
     *
     * Usage:
     *   Employee::factory()->forDepartment($dept)->create()
     *   Employee::factory()->forDepartment($dept, $pos)->create()
     */
    public function forDepartment(Department $department, ?Position $position = null): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->department_id,
            'position_id'   => $position?->position_id,
        ]);
    }

    /**
     * Auto-create a Department and a Position, then wire the employee to them.
     *
     * Usage: Employee::factory()->withDepartment()->create()
     */
    public function withDepartment(): static
    {
        return $this->state(function (array $attributes) {
            $department = Department::factory()->create();
            $position   = Position::factory()->for($department, 'department')->create();

            return [
                'department_id' => $department->department_id,
                'position_id'   => $position->position_id,
            ];
        });
    }
}

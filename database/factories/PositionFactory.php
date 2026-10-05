<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 *
 * Generates realistic position / job-title data for Philippine companies.
 * The unique constraint is (position_name, department_id), so positions with
 * the same name may exist across different departments.
 *
 * States available:
 *  - managerial()  → picks a managerial-level title
 *  - supervisory() → picks a supervisor / team-lead title
 *  - rank()        → picks a rank-and-file title
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    /** Managerial-level position titles. */
    private static array $managerialTitles = [
        'Department Manager',
        'Operations Manager',
        'IT Manager',
        'Finance Manager',
        'HR Manager',
        'Marketing Manager',
        'General Manager',
        'Project Manager',
        'Product Manager',
    ];

    /** Supervisor / team-lead titles. */
    private static array $supervisoryTitles = [
        'Team Leader',
        'Senior Supervisor',
        'Shift Supervisor',
        'Section Head',
        'Lead Engineer',
        'Senior Analyst',
        'Senior Developer',
    ];

    /** Rank-and-file position titles. */
    private static array $rankFileTitles = [
        'Staff Associate',
        'Administrative Assistant',
        'Data Entry Specialist',
        'Accounting Clerk',
        'IT Support Technician',
        'Customer Service Representative',
        'Payroll Officer',
        'Procurement Officer',
        'Junior Developer',
        'Quality Control Inspector',
        'Logistics Coordinator',
        'Marketing Associate',
        'HR Officer',
        'Cashier',
        'Liaison Officer',
    ];

    /** Combined pool for the default definition. */
    private static array $allTitles = [];

    public function definition(): array
    {
        // Merge all pools on first use
        if (empty(self::$allTitles)) {
            self::$allTitles = array_merge(
                self::$managerialTitles,
                self::$supervisoryTitles,
                self::$rankFileTitles,
            );
        }

        return [
            'position_name' => fake()->randomElement(self::$allTitles),
            'department_id' => Department::factory(),
        ];
    }

    /**
     * Managerial-level position.
     */
    public function managerial(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_name' => fake()->randomElement(self::$managerialTitles),
        ]);
    }

    /**
     * Supervisory / team-lead position.
     */
    public function supervisory(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_name' => fake()->randomElement(self::$supervisoryTitles),
        ]);
    }

    /**
     * Rank-and-file position.
     */
    public function rank(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_name' => fake()->randomElement(self::$rankFileTitles),
        ]);
    }
}

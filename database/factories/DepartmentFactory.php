<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 *
 * Generates realistic CLSU / Philippine-company style departments.
 *
 * States available:
 *  - withDescription() → always generates a non-null description
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /** Realistic department names for a Philippine company / university context. */
    private static array $departmentNames = [
        'Human Resources',
        'Finance and Accounting',
        'Information Technology',
        'Operations',
        'Marketing and Communications',
        'Administration',
        'Engineering',
        'Research and Development',
        'Procurement and Logistics',
        'Quality Assurance',
        'Customer Service',
        'Legal and Compliance',
        'Health and Safety',
        'Training and Development',
        'Sales',
    ];

    /** Brief department descriptions. */
    private static array $descriptions = [
        'Handles employee relations, recruitment, and welfare programs.',
        'Manages financial records, budgeting, and payroll processing.',
        'Maintains IT infrastructure, systems, and technical support.',
        'Oversees day-to-day operational activities of the organization.',
        'Leads marketing campaigns, public relations, and communications.',
        'Provides administrative support and office management services.',
        'Designs and implements engineering solutions for company projects.',
        'Conducts research initiatives and develops new products or processes.',
        'Manages procurement of materials, supplies, and logistics operations.',
        'Ensures products and services meet established quality standards.',
        'Handles customer inquiries, support tickets, and satisfaction surveys.',
        'Provides legal counsel and ensures regulatory compliance.',
        'Promotes workplace safety and manages health programs.',
        'Organizes employee training programs and professional development.',
        'Drives revenue through client acquisition and account management.',
    ];

    public function definition(): array
    {
        $index = fake()->numberBetween(0, count(self::$departmentNames) - 1);

        return [
            'department_name' => self::$departmentNames[$index],
            'description'     => fake()->optional(0.75)->randomElement(self::$descriptions),
        ];
    }

    /**
     * Always generate a non-null description.
     */
    public function withDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement(self::$descriptions),
        ]);
    }
}

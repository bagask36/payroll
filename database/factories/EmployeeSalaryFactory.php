<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'basic_salary' => fake()->randomFloat(2, 4500000, 15000000),
            'effective_from' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'effective_to' => null,
            'revision_reason' => 'Penyesuaian Gaji Pokok Tahunan',
            'created_by' => User::factory(),
        ];
    }
}

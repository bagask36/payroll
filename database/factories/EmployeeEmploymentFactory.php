<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeEmployment>
 */
class EmployeeEmploymentFactory extends Factory
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
            'contract_type' => fake()->randomElement(['permanent', 'contract', 'probation', 'internship']),
            'contract_number' => 'SPK/RSJ/'.fake()->numerify('###/####'),
            'start_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'end_date' => null,
            'is_active' => true,
        ];
    }
}

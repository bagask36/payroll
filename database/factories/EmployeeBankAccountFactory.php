<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeBankAccount>
 */
class EmployeeBankAccountFactory extends Factory
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
            'bank_name' => fake()->randomElement(['BCA', 'Bank Mandiri', 'BNI', 'BRI', 'BSI']),
            'account_number' => fake()->numerify('##########'),
            'account_holder' => fake()->name(),
            'is_primary' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('LV-###')),
            'name' => 'Cuti '.fake()->word(),
            'default_days_per_year' => 12,
            'deducts_balance' => true,
            'requires_attachment' => false,
            'is_active' => true,
        ];
    }
}

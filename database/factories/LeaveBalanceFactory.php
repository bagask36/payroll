<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
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
            'doctor_id' => null,
            'leave_type_id' => LeaveType::factory(),
            'year' => (int) date('Y'),
            'assigned_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ];
    }
}

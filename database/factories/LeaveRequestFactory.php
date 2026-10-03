<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
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
            'start_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d'),
            'total_days' => 2.0,
            'reason' => 'Keperluan keluarga penting',
            'status' => 'pending_tier1',
            'rejection_reason' => null,
            'created_by' => User::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleAssignment>
 */
class ScheduleAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'employee_id' => Employee::factory(),
            'doctor_id' => null,
            'schedule_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'status' => 'scheduled',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}

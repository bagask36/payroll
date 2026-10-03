<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_assignment_id' => null,
            'employee_id' => Employee::factory(),
            'doctor_id' => null,
            'date' => fake()->date(),
            'clock_in' => fake()->dateTimeThisMonth(),
            'clock_out' => fake()->dateTimeThisMonth(),
            'status' => 'present',
            'work_duration_minutes' => 480,
            'late_duration_minutes' => 0,
        ];
    }
}

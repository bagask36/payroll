<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceAdjustment>
 */
class AttendanceAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'original_clock_in' => now()->setTime(8, 20),
            'adjusted_clock_in' => now()->setTime(7, 30),
            'original_clock_out' => null,
            'adjusted_clock_out' => null,
            'reason' => 'Fingerprint sensor error at front entrance',
            'status' => 'pending',
            'requested_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}

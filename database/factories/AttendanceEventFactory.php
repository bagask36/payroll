<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceEvent>
 */
class AttendanceEventFactory extends Factory
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
            'event_type' => 'clock_in',
            'recorded_at' => now(),
            'latitude' => -6.91746400,
            'longitude' => 107.61912200,
            'distance_meters' => 15,
            'is_within_geofence' => true,
            'device_info' => 'Android 14 / SM-A546E',
            'face_matched' => true,
            'face_similarity' => 0.94,
            'liveness_score' => 0.98,
        ];
    }
}

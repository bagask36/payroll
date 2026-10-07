<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\GeofenceLocation;
use App\Models\ScheduleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Hospital Geofence Location
        $geofence = GeofenceLocation::updateOrCreate(
            ['name' => 'RS Permata Bunda'],
            [
                'latitude' => -6.91746400,
                'longitude' => 107.61912200,
                'radius_meters' => 100,
                'is_active' => true,
            ]
        );

        // 2. Nurse Attendance for Today
        $nurse = Employee::where('nip', 'PEG-2025042')->first();
        $today = Carbon::today()->toDateString();

        if ($nurse) {
            $assignment = ScheduleAssignment::where('employee_id', $nurse->id)
                ->whereDate('schedule_date', $today)
                ->first();

            $attendance = Attendance::updateOrCreate(
                ['employee_id' => $nurse->id, 'date' => $today],
                [
                    'schedule_assignment_id' => $assignment?->id,
                    'doctor_id' => null,
                    'status' => 'present',
                    'late_duration_minutes' => 0,
                ]
            );

            // Raw Clock In
            $attendance->recordClockIn(Carbon::today()->setTime(6, 55), [
                'latitude' => -6.91745000,
                'longitude' => 107.61911000,
                'distance_meters' => 12,
                'is_within_geofence' => true,
                'device_info' => 'Samsung Galaxy A54 (Android 14)',
                'face_matched' => true,
                'face_similarity' => 0.96,
                'liveness_score' => 0.99,
            ]);

            // Raw Clock Out
            $attendance->recordClockOut(Carbon::today()->setTime(14, 10), [
                'latitude' => -6.91745500,
                'longitude' => 107.61911500,
                'distance_meters' => 15,
                'is_within_geofence' => true,
                'device_info' => 'Samsung Galaxy A54 (Android 14)',
                'face_matched' => true,
                'face_similarity' => 0.95,
                'liveness_score' => 0.98,
            ]);
        }
    }
}

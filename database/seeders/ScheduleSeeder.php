<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('email', 'admin@juwitahospital.com')->first();
        $adminId = $superAdmin ? $superAdmin->id : User::factory()->create()->id;

        // 1. Hospital Shift Templates
        $pagi = Shift::updateOrCreate(
            ['code' => 'PAGI'],
            [
                'name' => 'Shift Pagi',
                'type' => 'shift',
                'start_time' => '07:00:00',
                'end_time' => '14:00:00',
                'is_overnight' => false,
                'late_tolerance_minutes' => 15,
                'is_active' => true,
            ]
        );

        $siang = Shift::updateOrCreate(
            ['code' => 'SIANG'],
            [
                'name' => 'Shift Siang',
                'type' => 'shift',
                'start_time' => '14:00:00',
                'end_time' => '21:00:00',
                'is_overnight' => false,
                'late_tolerance_minutes' => 15,
                'is_active' => true,
            ]
        );

        $malam = Shift::updateOrCreate(
            ['code' => 'MALAM'],
            [
                'name' => 'Shift Malam',
                'type' => 'shift',
                'start_time' => '21:00:00',
                'end_time' => '07:00:00',
                'is_overnight' => true,
                'late_tolerance_minutes' => 15,
                'is_active' => true,
            ]
        );

        $reguler = Shift::updateOrCreate(
            ['code' => 'NON-SHIFT'],
            [
                'name' => 'Jam Kerja Reguler (Non-Shift)',
                'type' => 'non_shift',
                'start_time' => '08:00:00',
                'end_time' => '16:30:00',
                'is_overnight' => false,
                'late_tolerance_minutes' => 15,
                'is_active' => true,
            ]
        );

        // 2. Schedule Assignments for Today & Upcoming Days
        $nurse = Employee::where('nip', 'PEG-2025042')->first(); // Budi Santoso (IGD)
        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->first();

        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        if ($nurse) {
            ScheduleAssignment::updateOrCreate(
                ['employee_id' => $nurse->id, 'schedule_date' => $today],
                [
                    'shift_id' => $pagi->id,
                    'doctor_id' => null,
                    'status' => 'scheduled',
                    'notes' => 'Dinas Pagi IGD',
                    'created_by' => $adminId,
                ]
            );

            ScheduleAssignment::updateOrCreate(
                ['employee_id' => $nurse->id, 'schedule_date' => $tomorrow],
                [
                    'shift_id' => $siang->id,
                    'doctor_id' => null,
                    'status' => 'scheduled',
                    'notes' => 'Dinas Siang IGD',
                    'created_by' => $adminId,
                ]
            );
        }

        if ($doctor) {
            ScheduleAssignment::updateOrCreate(
                ['doctor_id' => $doctor->id, 'schedule_date' => $today],
                [
                    'shift_id' => $reguler->id,
                    'employee_id' => null,
                    'status' => 'scheduled',
                    'notes' => 'Praktek Poli Penyakit Dalam',
                    'created_by' => $adminId,
                ]
            );
        }
    }
}

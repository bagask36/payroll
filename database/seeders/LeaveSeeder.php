<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Hospital Standard Leave Types
        $annual = LeaveType::updateOrCreate(
            ['code' => 'ANNUAL'],
            [
                'name' => 'Cuti Tahunan',
                'default_days_per_year' => 12,
                'deducts_balance' => true,
                'requires_attachment' => false,
                'is_active' => true,
            ]
        );

        $sick = LeaveType::updateOrCreate(
            ['code' => 'SICK'],
            [
                'name' => 'Cuti Sakit',
                'default_days_per_year' => 14,
                'deducts_balance' => false,
                'requires_attachment' => true,
                'is_active' => true,
            ]
        );

        $maternity = LeaveType::updateOrCreate(
            ['code' => 'MATERNITY'],
            [
                'name' => 'Cuti Melahirkan',
                'default_days_per_year' => 90,
                'deducts_balance' => false,
                'requires_attachment' => true,
                'is_active' => true,
            ]
        );

        $special = LeaveType::updateOrCreate(
            ['code' => 'SPECIAL'],
            [
                'name' => 'Cuti Alasan Penting (Pernikahan/Duka)',
                'default_days_per_year' => 3,
                'deducts_balance' => false,
                'requires_attachment' => true,
                'is_active' => true,
            ]
        );

        // 2. Annual Leave Allocation for 2026 for Nurse Budi Santoso
        $nurse = Employee::where('nip', 'PEG-2025042')->first();
        if ($nurse) {
            LeaveBalance::updateOrCreate(
                [
                    'employee_id' => $nurse->id,
                    'leave_type_id' => $annual->id,
                    'year' => 2026,
                ],
                [
                    'assigned_days' => 12.0,
                    'used_days' => 0.0,
                    'remaining_days' => 12.0,
                ]
            );

            // Sample Leave Request pending Tier 1
            LeaveRequest::updateOrCreate(
                [
                    'employee_id' => $nurse->id,
                    'start_date' => '2026-11-10',
                ],
                [
                    'doctor_id' => null,
                    'leave_type_id' => $annual->id,
                    'end_date' => '2026-11-12',
                    'total_days' => 3.0,
                    'reason' => 'Acara keluarga di luar kota',
                    'status' => 'pending_tier1',
                    'created_by' => $nurse->user->id,
                ]
            );
        }
    }
}

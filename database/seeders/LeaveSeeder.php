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

        // 2. Annual Leave Allocation for 2026 for Nurse Budi Santoso (ICU)
        $nurseBudi = Employee::where('nip', 'PEG-2025042')->first();
        if ($nurseBudi) {
            LeaveBalance::updateOrCreate(
                [
                    'employee_id' => $nurseBudi->id,
                    'leave_type_id' => $annual->id,
                    'year' => 2026,
                ],
                [
                    'assigned_days' => 12.0,
                    'used_days' => 0.0,
                    'remaining_days' => 12.0,
                ]
            );

            // Sample Leave Request pending Tier 1 (Awaiting Ns. Ratna Wulandari / ICU Head)
            LeaveRequest::updateOrCreate(
                [
                    'employee_id' => $nurseBudi->id,
                    'start_date' => '2026-11-10',
                ],
                [
                    'doctor_id' => null,
                    'leave_type_id' => $annual->id,
                    'end_date' => '2026-11-12',
                    'total_days' => 3.0,
                    'reason' => 'Acara keluarga di luar kota (Staf ICU)',
                    'status' => 'pending_tier1',
                    'created_by' => $nurseBudi->user?->id ?? 1,
                ]
            );
        }

        // 3. Annual Leave Allocation for 2026 for Nurse Siti Rahmawati (IGD)
        $nurseSiti = Employee::where('nip', 'PEG-2025099')->first();
        if ($nurseSiti) {
            LeaveBalance::updateOrCreate(
                [
                    'employee_id' => $nurseSiti->id,
                    'leave_type_id' => $annual->id,
                    'year' => 2026,
                ],
                [
                    'assigned_days' => 12.0,
                    'used_days' => 0.0,
                    'remaining_days' => 12.0,
                ]
            );

            // Sample Leave Request pending Tier 1 (Awaiting dr. Bambang Irawan / Kepala IGD)
            LeaveRequest::updateOrCreate(
                [
                    'employee_id' => $nurseSiti->id,
                    'start_date' => '2026-11-15',
                ],
                [
                    'doctor_id' => null,
                    'leave_type_id' => $annual->id,
                    'end_date' => '2026-11-16',
                    'total_days' => 2.0,
                    'reason' => 'Keperluan mendesak keluarga (Staf IGD)',
                    'status' => 'pending_tier1',
                    'created_by' => $nurseSiti->user?->id ?? 1,
                ]
            );
        }
    }
}

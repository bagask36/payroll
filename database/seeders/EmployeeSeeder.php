<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('email', 'admin@juwitahospital.com')->first();
        $adminId = $superAdmin ? $superAdmin->id : User::factory()->create()->id;

        // 1. HR Officer (Unit: SDM)
        $sdmUnit = Unit::where('code', 'SDM')->first();
        $hrPos = Position::where('title', 'Staff Penggajian & HRIS')->first();

        $hrUser = User::firstOrCreate(
            ['email' => 'hr.officer@juwitahospital.com'],
            [
                'name' => 'Dewi Sartika, S.Psi',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $hrUser->assignRole('hr_admin');

        $emp1 = Employee::updateOrCreate(
            ['nip' => 'PEG-2024001'],
            [
                'user_id' => $hrUser->id,
                'unit_id' => $sdmUnit->id,
                'position_id' => $hrPos->id,
                'ktp_number' => '3201015504950001',
                'full_name' => 'Dewi Sartika, S.Psi',
                'gender' => 'female',
                'birth_place' => 'Bandung',
                'birth_date' => '1995-04-15',
                'marital_status' => 'married',
                'tax_dependents' => 1,
                'npwp_number' => '85.123.456.7-412.000',
                'phone_number' => '081234567890',
                'residential_address' => 'Jl. Juwita Indah No. 12, Bandung',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $emp1->id, 'contract_number' => 'SPK/RSJ/2024/001'],
            [
                'unit_id' => $sdmUnit->id,
                'position_id' => $hrPos->id,
                'contract_type' => 'permanent',
                'start_date' => '2024-01-01',
                'end_date' => null,
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $emp1->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 6500000.00,
                'effective_to' => null,
                'revision_reason' => 'Kenaikan Berkala 2026',
                'created_by' => $adminId,
            ]
        );

        EmployeeBankAccount::updateOrCreate(
            ['employee_id' => $emp1->id, 'is_primary' => true],
            [
                'bank_name' => 'BCA',
                'account_number' => '8410293847',
                'account_holder' => 'Dewi Sartika',
            ]
        );

        // 2. ICU Head Nurse (Supervisor)
        $icuUnit = Unit::where('code', 'ICU')->first();
        $icuPos = Position::where('title', 'Kepala Ruang ICU')->first();

        $nurseSupervisor = User::firstOrCreate(
            ['email' => 'icu.head@juwitahospital.com'],
            [
                'name' => 'Ns. Ratna Wulandari, S.Kep',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $nurseSupervisor->assignRole('supervisor');

        $emp2 = Employee::updateOrCreate(
            ['nip' => 'PEG-2023015'],
            [
                'user_id' => $nurseSupervisor->id,
                'unit_id' => $icuUnit->id,
                'position_id' => $icuPos->id,
                'ktp_number' => '3201026008920002',
                'full_name' => 'Ns. Ratna Wulandari, S.Kep',
                'gender' => 'female',
                'birth_place' => 'Jakarta',
                'birth_date' => '1992-08-20',
                'marital_status' => 'married',
                'tax_dependents' => 2,
                'npwp_number' => '73.456.789.0-412.000',
                'phone_number' => '081345678901',
                'residential_address' => 'Jl. Mekar Wangi Blok C-4, Bandung',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $emp2->id, 'contract_number' => 'SPK/RSJ/2023/015'],
            [
                'unit_id' => $icuUnit->id,
                'position_id' => $icuPos->id,
                'contract_type' => 'permanent',
                'start_date' => '2023-03-01',
                'end_date' => null,
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $emp2->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 8200000.00,
                'effective_to' => null,
                'revision_reason' => 'Penyesuaian Jenjang Karir Supervisor 2026',
                'created_by' => $adminId,
            ]
        );

        EmployeeBankAccount::updateOrCreate(
            ['employee_id' => $emp2->id, 'is_primary' => true],
            [
                'bank_name' => 'Bank Mandiri',
                'account_number' => '1320019283746',
                'account_holder' => 'Ratna Wulandari',
            ]
        );

        // 3. IGD Nurse (Employee)
        $igdUnit = Unit::where('code', 'IGD')->first();
        $igdNursePos = Position::where('title', 'Perawat Pelaksana IGD')->first();

        $igdNurseUser = User::firstOrCreate(
            ['email' => 'nurse.budi@juwitahospital.com'],
            [
                'name' => 'Budi Santoso, A.Md.Kep',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $igdNurseUser->assignRole('employee');

        $emp3 = Employee::updateOrCreate(
            ['nip' => 'PEG-2025042'],
            [
                'user_id' => $igdNurseUser->id,
                'unit_id' => $igdUnit->id,
                'position_id' => $igdNursePos->id,
                'ktp_number' => '3201031205980003',
                'full_name' => 'Budi Santoso, A.Md.Kep',
                'gender' => 'male',
                'birth_place' => 'Cimahi',
                'birth_date' => '1998-05-12',
                'marital_status' => 'single',
                'tax_dependents' => 0,
                'npwp_number' => '91.234.567.8-412.000',
                'phone_number' => '081456789012',
                'residential_address' => 'Jl. Cibabat Raya No. 45, Cimahi',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $emp3->id, 'contract_number' => 'SPK/RSJ/2025/042'],
            [
                'unit_id' => $igdUnit->id,
                'position_id' => $igdNursePos->id,
                'contract_type' => 'contract',
                'start_date' => '2025-01-01',
                'end_date' => '2026-12-31',
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $emp3->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 5200000.00,
                'effective_to' => null,
                'revision_reason' => 'Gaji Pokok PKWT 2026',
                'created_by' => $adminId,
            ]
        );

        EmployeeBankAccount::updateOrCreate(
            ['employee_id' => $emp3->id, 'is_primary' => true],
            [
                'bank_name' => 'BNI',
                'account_number' => '0839201928',
                'account_holder' => 'Budi Santoso',
            ]
        );
    }
}

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
                'religion' => 'islam',
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

        $teguranType = \App\Models\WarningLetterType::where('code', 'TEGURAN')->first();
        if ($teguranType) {
            \App\Models\EmployeeWarningLetter::updateOrCreate(
                ['employee_id' => $emp1->id, 'letter_number' => '012/TEGURAN/SDM-RSJ/01/2026'],
                [
                    'warning_letter_type_id' => $teguranType->id,
                    'issued_date' => '2026-01-10',
                    'valid_from' => '2026-01-10',
                    'valid_until' => '2026-04-10',
                    'violation_reason' => 'Kelalaian pencatatan logbook administrasi harian penggajian pegawai.',
                    'authorized_by' => 'H. Bambang Soedirman, SE, MM (Kepala Bagian SDM)',
                    'status' => 'expired',
                    'notes' => 'Telah dilakukan pembinaan internal dan evaluasi berkala.',
                    'created_by' => $adminId,
                ]
            );
        }

        $sp1Type = \App\Models\WarningLetterType::where('code', 'SP-1')->first();
        if ($sp1Type) {
            \App\Models\EmployeeWarningLetter::updateOrCreate(
                ['employee_id' => $emp1->id, 'letter_number' => '005/SP-I/SDM-RSJ/08/2026'],
                [
                    'warning_letter_type_id' => $sp1Type->id,
                    'issued_date' => '2026-08-15',
                    'valid_from' => '2026-08-15',
                    'valid_until' => '2027-02-15',
                    'violation_reason' => 'Keterlambatan masuk tugas shift melebihi batas toleransi sebanyak 4 kali dalam satu periode absensi.',
                    'authorized_by' => 'H. Bambang Soedirman, SE, MM (Kepala Bagian SDM)',
                    'status' => 'active',
                    'notes' => 'Pegawai bersedia memperbaiki kedisiplinan waktu kehadiran kerja.',
                    'created_by' => $adminId,
                ]
            );
        }

        // 2. HR Manager (Unit: SDM)
        $hrManagerPos = Position::where('title', 'Kepala Bagian SDM')->first();
        $hrManagerUser = User::firstOrCreate(
            ['email' => 'hr.manager@juwitahospital.com'],
            [
                'name' => 'H. Bambang Soedirman (HR Manager)',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $hrManagerUser->assignRole('hr_manager');

        $empHrManager = Employee::updateOrCreate(
            ['nip' => 'PEG-2020001'],
            [
                'user_id' => $hrManagerUser->id,
                'unit_id' => $sdmUnit->id,
                'position_id' => $hrManagerPos->id,
                'ktp_number' => '3201015501800001',
                'full_name' => 'H. Bambang Soedirman, SE, MM',
                'gender' => 'male',
                'birth_place' => 'Semarang',
                'birth_date' => '1980-01-15',
                'marital_status' => 'married',
                'religion' => 'islam',
                'tax_dependents' => 3,
                'npwp_number' => '81.111.222.3-412.000',
                'phone_number' => '081211112222',
                'residential_address' => 'Jl. Juwita Residence A1, Bandung',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $empHrManager->id, 'contract_number' => 'SK/DIR/2020/001'],
            [
                'unit_id' => $sdmUnit->id,
                'position_id' => $hrManagerPos->id,
                'contract_type' => 'permanent',
                'start_date' => '2020-01-01',
                'end_date' => null,
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $empHrManager->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 9500000.00,
                'effective_to' => null,
                'revision_reason' => 'Gaji Pokok Manajer SDM 2026',
                'created_by' => $adminId,
            ]
        );

        // 3. ICU Head Nurse (Supervisor ICU)
        $icuUnit = Unit::where('code', 'ICU')->first();
        $icuPos = Position::where('title', 'Kepala Ruang ICU')->first();

        $nurseSupervisor = User::firstOrCreate(
            ['email' => 'icu.head@juwitahospital.com'],
            [
                'name' => 'Ns. Ratna Wulandari, S.Kep (Kepala ICU)',
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
                'religion' => 'islam',
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

        // 4. ICU Nurse (Subordinate of ICU Head)
        $icuNursePos = Position::where('title', 'Perawat Mahir ICU')->first();
        $icuNurseUser = User::firstOrCreate(
            ['email' => 'nurse.budi@juwitahospital.com'],
            [
                'name' => 'Budi Santoso, A.Md.Kep (Staf ICU)',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $icuNurseUser->assignRole('employee');

        $emp3 = Employee::updateOrCreate(
            ['nip' => 'PEG-2025042'],
            [
                'user_id' => $icuNurseUser->id,
                'unit_id' => $icuUnit->id,
                'position_id' => $icuNursePos->id,
                'ktp_number' => '3201031205980003',
                'full_name' => 'Budi Santoso, A.Md.Kep',
                'gender' => 'male',
                'birth_place' => 'Cimahi',
                'birth_date' => '1998-05-12',
                'marital_status' => 'single',
                'religion' => 'islam',
                'tax_dependents' => 0,
                'npwp_number' => '91.234.567.8-412.000',
                'phone_number' => '081456789012',
                'residential_address' => 'Jl. Cibabat Raya No. 45, Cimahi',
                'status' => 'active',
                'schedule_type' => 'shift',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $emp3->id, 'contract_number' => 'SPK/RSJ/2025/042'],
            [
                'unit_id' => $icuUnit->id,
                'position_id' => $icuNursePos->id,
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

        // 5. IGD Head (Supervisor IGD)
        $igdUnit = Unit::where('code', 'IGD')->first();
        $igdHeadPos = Position::where('title', 'Kepala Instalasi Gawat Darurat')->first();

        $igdHeadUser = User::firstOrCreate(
            ['email' => 'supervisor@juwitahospital.com'],
            [
                'name' => 'dr. Bambang Irawan (Kepala IGD)',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $igdHeadUser->assignRole('supervisor');

        $empIgdHead = Employee::updateOrCreate(
            ['nip' => 'PEG-2021008'],
            [
                'user_id' => $igdHeadUser->id,
                'unit_id' => $igdUnit->id,
                'position_id' => $igdHeadPos->id,
                'ktp_number' => '3201021104850005',
                'full_name' => 'dr. Bambang Irawan, Sp.B',
                'gender' => 'male',
                'birth_place' => 'Surabaya',
                'birth_date' => '1985-04-11',
                'marital_status' => 'married',
                'religion' => 'kristen',
                'tax_dependents' => 2,
                'npwp_number' => '71.222.333.4-412.000',
                'phone_number' => '081233334444',
                'residential_address' => 'Jl. Pasteur No. 88, Bandung',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $empIgdHead->id, 'contract_number' => 'SK/DIR/2021/008'],
            [
                'unit_id' => $igdUnit->id,
                'position_id' => $igdHeadPos->id,
                'contract_type' => 'permanent',
                'start_date' => '2021-02-01',
                'end_date' => null,
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $empIgdHead->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 9500000.00,
                'effective_to' => null,
                'revision_reason' => 'Gaji Pokok Kepala IGD 2026',
                'created_by' => $adminId,
            ]
        );

        // 6. IGD Nurse (Subordinate of IGD Head)
        $igdNursePos = Position::where('title', 'Perawat Pelaksana IGD')->first();
        $igdNurseUser = User::firstOrCreate(
            ['email' => 'employee@juwitahospital.com'],
            [
                'name' => 'Siti Rahmawati, A.Md.Kep (Staf IGD)',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $igdNurseUser->assignRole('employee');

        $empIgdNurse = Employee::updateOrCreate(
            ['nip' => 'PEG-2025099'],
            [
                'user_id' => $igdNurseUser->id,
                'unit_id' => $igdUnit->id,
                'position_id' => $igdNursePos->id,
                'ktp_number' => '3201046506990006',
                'full_name' => 'Siti Rahmawati, A.Md.Kep',
                'gender' => 'female',
                'birth_place' => 'Tasikmalaya',
                'birth_date' => '1999-06-25',
                'marital_status' => 'single',
                'religion' => 'islam',
                'tax_dependents' => 0,
                'npwp_number' => '92.333.444.5-412.000',
                'phone_number' => '081566667777',
                'residential_address' => 'Jl. Sukajadi No. 102, Bandung',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $empIgdNurse->id, 'contract_number' => 'SPK/RSJ/2025/099'],
            [
                'unit_id' => $igdUnit->id,
                'position_id' => $igdNursePos->id,
                'contract_type' => 'contract',
                'start_date' => '2025-06-01',
                'end_date' => '2026-12-31',
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $empIgdNurse->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 5200000.00,
                'effective_to' => null,
                'revision_reason' => 'Gaji Pokok PKWT 2026',
                'created_by' => $adminId,
            ]
        );

        EmployeeBankAccount::updateOrCreate(
            ['employee_id' => $empIgdNurse->id, 'is_primary' => true],
            [
                'bank_name' => 'BCA',
                'account_number' => '7720192837',
                'account_holder' => 'Siti Rahmawati',
            ]
        );
    }
}

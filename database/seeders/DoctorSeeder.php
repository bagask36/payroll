<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('email', 'admin@juwitahospital.com')->first();
        $adminId = $superAdmin ? $superAdmin->id : User::factory()->create()->id;

        $rajalUnit = Unit::where('code', 'RAJAL')->first() ?? Unit::factory()->create(['code' => 'RAJAL']);
        $docPos = Position::where('title', 'Dokter Jaga IGD')->first() ?? Position::factory()->create(['title' => 'Dokter Spesialis']);

        // 1. Organic Specialist Doctor (Linked to Employee & Payroll)
        $doc1User = User::firstOrCreate(
            ['email' => 'dr.hendra@juwitahospital.com'],
            [
                'name' => 'dr. Hendra Pratama, Sp.PD',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $doc1User->assignRole('doctor');

        $emp1 = Employee::updateOrCreate(
            ['nip' => 'DOK-2023001'],
            [
                'user_id' => $doc1User->id,
                'unit_id' => $rajalUnit->id,
                'position_id' => $docPos->id,
                'ktp_number' => '3201011005850001',
                'full_name' => 'dr. Hendra Pratama, Sp.PD',
                'gender' => 'male',
                'birth_place' => 'Surabaya',
                'birth_date' => '1985-05-10',
                'marital_status' => 'married',
                'tax_dependents' => 2,
                'npwp_number' => '54.321.987.6-412.000',
                'phone_number' => '081298765432',
                'residential_address' => 'Jl. Dago Asri No. 88, Bandung',
                'status' => 'active',
            ]
        );

        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $emp1->id, 'contract_number' => 'SPK/DOK/2023/001'],
            [
                'contract_type' => 'permanent',
                'start_date' => '2023-01-01',
                'end_date' => null,
                'is_active' => true,
            ]
        );

        EmployeeSalary::updateOrCreate(
            ['employee_id' => $emp1->id, 'effective_from' => '2026-01-01'],
            [
                'basic_salary' => 18000000.00,
                'effective_to' => null,
                'revision_reason' => 'Gaji Pokok Dokter Tetap 2026',
                'created_by' => $adminId,
            ]
        );

        EmployeeBankAccount::updateOrCreate(
            ['employee_id' => $emp1->id, 'is_primary' => true],
            [
                'bank_name' => 'BCA',
                'account_number' => '7310928374',
                'account_holder' => 'Hendra Pratama',
            ]
        );

        Doctor::updateOrCreate(
            ['str_number' => 'STR-33019284719284'],
            [
                'user_id' => $doc1User->id,
                'employee_id' => $emp1->id,
                'name' => 'dr. Hendra Pratama, Sp.PD',
                'specialization' => 'Spesialis Penyakit Dalam (Sp.PD)',
                'str_valid_until' => '2029-05-10',
                'sip_number' => 'SIP/446/012/RSJ/2024',
                'sip_valid_until' => '2029-05-10',
                'phone_number' => '081298765432',
                'email' => 'dr.hendra@juwitahospital.com',
                'status' => 'active',
            ]
        );

        // 2. Visiting / Partner Doctor (Pure Doctor, No Employee link, No basic salary)
        $doc2User = User::firstOrCreate(
            ['email' => 'dr.citra@juwitahospital.com'],
            [
                'name' => 'dr. Citra Anindya, Sp.A',
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ]
        );
        $doc2User->assignRole('doctor');

        Doctor::updateOrCreate(
            ['str_number' => 'STR-32098471928475'],
            [
                'user_id' => $doc2User->id,
                'employee_id' => null, // Non-organic partner
                'name' => 'dr. Citra Anindya, Sp.A',
                'specialization' => 'Spesialis Anak (Sp.A)',
                'str_valid_until' => '2028-11-24',
                'sip_number' => 'SIP/446/089/RSJ/2023',
                'sip_valid_until' => '2028-11-24',
                'phone_number' => '081387654321',
                'email' => 'dr.citra@juwitahospital.com',
                'status' => 'active',
            ]
        );
    }
}

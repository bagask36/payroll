<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Roles & Permissions exist
        if (Role::count() === 0) {
            $this->call(RoleAndPermissionSeeder::class);
        }

        $passwordHash = Hash::make('123456');

        // 2. Default Test User (Super Admin with full break-glass authority)
        $testUser = User::updateOrCreate(
            ['email' => 'testuser@juwitahospital.com'],
            [
                'name' => 'Test User (Super Admin)',
                'password' => $passwordHash,
                'is_active' => true,
            ]
        );
        $testUser->syncRoles(['super_admin']);

        // 3. Test User alias: testuser@example.com
        $testUserExample = User::updateOrCreate(
            ['email' => 'testuser@example.com'],
            [
                'name' => 'Test User',
                'password' => $passwordHash,
                'is_active' => true,
            ]
        );
        $testUserExample->syncRoles(['super_admin']);

        // 4. Link an Employee profile to testuser if units & positions exist
        $sdmUnit = Unit::first();
        $staffPos = Position::first();

        if ($sdmUnit && $staffPos) {
            $emp = Employee::updateOrCreate(
                ['nip' => 'TEST-001'],
                [
                    'user_id' => $testUser->id,
                    'unit_id' => $sdmUnit->id,
                    'position_id' => $staffPos->id,
                    'ktp_number' => '3201019901010001',
                    'full_name' => 'Test User (Super Admin)',
                    'gender' => 'male',
                    'birth_place' => 'Jakarta',
                    'birth_date' => '1990-01-01',
                    'marital_status' => 'married',
                    'tax_dependents' => 1,
                    'npwp_number' => '99.999.999.9-999.000',
                    'phone_number' => '081299998888',
                    'residential_address' => 'Jl. Juwita Hospital Test Complex No. 1',
                    'status' => 'active',
                ]
            );

            EmployeeEmployment::updateOrCreate(
                ['employee_id' => $emp->id],
                [
                    'contract_type' => 'permanent',
                    'contract_number' => 'SPK/TEST/2026/001',
                    'start_date' => '2026-01-01',
                    'end_date' => null,
                    'is_active' => true,
                ]
            );

            EmployeeSalary::updateOrCreate(
                ['employee_id' => $emp->id, 'effective_from' => '2026-01-01'],
                [
                    'basic_salary' => 10000000.00,
                    'effective_to' => null,
                    'revision_reason' => 'Test User Master Salary',
                    'created_by' => $testUser->id,
                ]
            );
        }

        // 5. Update / Ensure test accounts for every hospital role with password "123456"
        $roleTestAccounts = [
            'admin@juwitahospital.com' => ['name' => 'Super Administrator', 'role' => 'super_admin'],
            'hr@juwitahospital.com' => ['name' => 'HR Staff', 'role' => 'hr_admin'],
            'hr.officer@juwitahospital.com' => ['name' => 'Dewi Sartika, S.Psi', 'role' => 'hr_admin'],
            'hr.manager@juwitahospital.com' => ['name' => 'HR Manager', 'role' => 'hr_manager'],
            'supervisor@juwitahospital.com' => ['name' => 'ICU Supervisor', 'role' => 'supervisor'],
            'icu.head@juwitahospital.com' => ['name' => 'Ns. Ratna Wulandari, S.Kep', 'role' => 'supervisor'],
            'doctor@juwitahospital.com' => ['name' => 'dr. Dokter Test, Sp.B', 'role' => 'doctor'],
            'dr.hendra@juwitahospital.com' => ['name' => 'dr. Hendra Pratama, Sp.PD', 'role' => 'doctor'],
            'employee@juwitahospital.com' => ['name' => 'Perawat Test Pelaksana', 'role' => 'employee'],
            'nurse.budi@juwitahospital.com' => ['name' => 'Budi Santoso, A.Md.Kep', 'role' => 'employee'],
            'payroll@juwitahospital.com' => ['name' => 'Payroll Specialist', 'role' => 'payroll'],
            'finance@juwitahospital.com' => ['name' => 'Finance Manager', 'role' => 'finance'],
        ];

        foreach ($roleTestAccounts as $email => $data) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'password' => $passwordHash,
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$data['role']]);
        }
    }
}

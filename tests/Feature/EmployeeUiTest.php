<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Employee\EmployeeShow;
use App\Livewire\Tables\EmployeeTable;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthorized_user_cannot_access_employee_directory(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        Livewire::test(EmployeeIndex::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_and_search_employees(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('hr_admin');

        $emp1 = Employee::factory()->create(['full_name' => 'Siti Nurhaliza', 'nip' => 'PEG-2026001', 'status' => 'active']);
        $emp2 = Employee::factory()->create(['full_name' => 'Budi Santoso', 'nip' => 'PEG-2026002', 'status' => 'active']);

        $this->actingAs($user);

        $this->get(route('employees.index'))
            ->assertStatus(200)
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Budi Santoso');

        Livewire::test(EmployeeTable::class)
            ->assertStatus(200)
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Budi Santoso')
            ->set('search', 'Siti')
            ->assertSee('Siti Nurhaliza')
            ->assertDontSee('Budi Santoso');
    }

    public function test_hr_admin_can_register_new_employee(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('hr_admin');

        $unit = Unit::factory()->create(['name' => 'Instalasi Rawat Inap']);
        $position = Position::factory()->create(['title' => 'Perawat Pelaksana']);

        $this->actingAs($user);

        Livewire::test(EmployeeIndex::class)
            ->call('openCreateModal')
            ->set('nip', 'PEG-999901')
            ->set('name', 'Rahmat Hidayat, S.Kep')
            ->set('email', 'rahmat.hidayat@rsjuwita.co.id')
            ->set('phone', '081234567899')
            ->set('gender', 'male')
            ->set('birthPlace', 'Bandung')
            ->set('birthDate', '1995-05-12')
            ->set('ktpNumber', '3201011205950001')
            ->set('unitId', $unit->id)
            ->set('positionId', $position->id)
            ->set('contractType', 'permanent')
            ->set('joinDate', Carbon::today()->toDateString())
            ->call('createEmployee')
            ->assertHasNoErrors()
            ->assertSee('berhasil didaftarkan');

        $this->assertDatabaseHas('employees', [
            'nip' => 'PEG-999901',
            'full_name' => 'Rahmat Hidayat, S.Kep',
            'email' => 'rahmat.hidayat@rsjuwita.co.id',
            'birth_place' => 'Bandung',
            'unit_id' => $unit->id,
            'position_id' => $position->id,
        ]);

        $this->assertDatabaseHas('employee_employments', [
            'contract_type' => 'permanent',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.created',
        ]);
    }

    public function test_hr_admin_can_update_employee_email_via_biodata_modal(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('hr_admin');

        $employee = Employee::factory()->create([
            'full_name' => 'Siti Aminah',
            'phone_number' => '081234567890',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'email' => 'siti.old@rsjuwita.co.id',
            'ktp_number' => '3201010101900001',
        ]);

        $this->actingAs($user);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBioModal')
            ->assertSet('showBioModal', true)
            ->assertSet('bioEmail', 'siti.old@rsjuwita.co.id')
            ->set('bioEmail', 'siti.new@rsjuwita.co.id')
            ->call('saveBio')
            ->assertHasNoErrors()
            ->assertSet('showBioModal', false);

        $employee->refresh();
        $this->assertEquals('siti.new@rsjuwita.co.id', $employee->email);
    }

    public function test_regular_employee_cannot_view_another_employees_profile_idor(): void
    {
        $viewerUser = User::factory()->create(['is_active' => true]);
        $viewerUser->assignRole('employee'); // Regular employee without employee.view

        $targetUser = User::factory()->create(['is_active' => true]);
        $targetEmployee = Employee::factory()->create([
            'user_id' => $targetUser->id,
            'full_name' => 'Ahmad Dahlan',
        ]);

        $this->actingAs($viewerUser);

        // Access denied for another employee
        Livewire::test(EmployeeShow::class, ['id' => $targetEmployee->id])
            ->assertForbidden();
    }

    public function test_supervisor_cannot_view_employees_salary_or_bank(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor'); // Has employee.view, but NOT view_salary or view_bank

        $targetUser = User::factory()->create(['is_active' => true]);
        $targetEmployee = Employee::factory()->create([
            'user_id' => $targetUser->id,
            'full_name' => 'Ahmad Dahlan',
            'ktp_number' => '3201123456789012',
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $targetEmployee->id,
            'basic_salary' => 8500000,
        ]);

        EmployeeBankAccount::factory()->create([
            'employee_id' => $targetEmployee->id,
            'bank_name' => 'Bank Mandiri',
            'account_number' => '1234567890123',
            'is_primary' => true,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(EmployeeShow::class, ['id' => $targetEmployee->id])
            ->assertStatus(200)
            ->assertSee('Ahmad Dahlan')
            ->assertSee('Tersamarkan - Akses PII Dibatasi')
            ->set('activeTab', 'salary')
            ->assertSee('Akses Terbatas: Data Gaji')
            ->assertDontSee('8.500.000')
            ->set('activeTab', 'bank')
            ->assertSee('Akses Terbatas: Data Perbankan')
            ->assertDontSee('1234567890123');
    }

    public function test_hr_manager_can_view_salary_and_bank_details(): void
    {
        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager'); // Has employee.view_salary and employee.view_bank

        $employee = Employee::factory()->create([
            'full_name' => 'Ratna Sari',
            'ktp_number' => '3201998877665544',
        ]);

        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 9500000,
        ]);

        EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'bank_name' => 'Bank BCA',
            'account_number' => '5432109876',
            'is_primary' => true,
        ]);

        $this->actingAs($hrManager);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->assertStatus(200)
            ->set('activeTab', 'salary')
            ->assertSee('9.500.000')
            ->set('activeTab', 'bank')
            ->assertSee('Bank BCA')
            ->assertSee('5432109876');
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeShow;
use App\Models\Attendance;
use App\Models\BpjsRule;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeSalaryComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(PayrollSeeder::class);
    }

    public function test_unauthorized_user_cannot_update_salary_or_components(): void
    {
        $hrAdmin = User::factory()->create(['is_active' => true]);
        $hrAdmin->assignRole('hr_admin'); // lacks employee.update_salary

        $employee = Employee::factory()->create();

        $this->actingAs($hrAdmin);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('saveSalary')
            ->assertForbidden();

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('saveComponent')
            ->assertForbidden();
    }

    public function test_hr_manager_can_revise_basic_salary(): void
    {
        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager');

        $employee = Employee::factory()->create();
        $initialSalary = EmployeeSalary::create([
            'employee_id' => $employee->id,
            'basic_salary' => 6000000.00,
            'effective_from' => '2026-01-01',
            'revision_reason' => 'Penetapan Awal',
            'created_by' => $hrManager->id,
        ]);

        $this->actingAs($hrManager);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('basicSalary', '7500000')
            ->set('salaryEffectiveFrom', '2026-04-01')
            ->set('revisionReason', 'Kenaikan Berkala 2026')
            ->call('saveSalary')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_salaries', [
            'employee_id' => $employee->id,
            'basic_salary' => 7500000.00,
            'revision_reason' => 'Kenaikan Berkala 2026',
        ]);

        $latestSalary = $employee->fresh()->currentSalary;
        $this->assertEquals(7500000.00, (float) $latestSalary->basic_salary);
        $this->assertEquals('2026-04-01', $latestSalary->effective_from?->toDateString());
        $this->assertEquals('2026-03-31', $initialSalary->fresh()->effective_to?->toDateString());
    }

    public function test_hr_manager_can_assign_edit_and_delete_salary_components(): void
    {
        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager');

        $employee = Employee::factory()->create();

        $allowance = SalaryComponent::create([
            'code' => 'TUNJ_JAB',
            'name' => 'Tunjangan Jabatan',
            'type' => 'allowance',
            'default_amount' => 750000.00,
            'is_active' => true,
        ]);

        $deduction = SalaryComponent::create([
            'code' => 'KOP_RS',
            'name' => 'Koperasi Karyawan',
            'type' => 'deduction',
            'default_amount' => 100000.00,
            'is_active' => true,
        ]);

        $this->actingAs($hrManager);

        // 1. Assign allowance
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('selectedComponentId', $allowance->id)
            ->set('componentAmount', '750000')
            ->set('componentEffectiveFrom', '2026-01-01')
            ->set('componentNotes', 'Penetapan Kepala Unit')
            ->call('saveComponent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_salary_components', [
            'employee_id' => $employee->id,
            'salary_component_id' => $allowance->id,
            'amount' => 750000.00,
            'is_active' => true,
        ]);

        $assignment = EmployeeSalaryComponent::where('employee_id', $employee->id)
            ->where('salary_component_id', $allowance->id)
            ->first();

        // 2. Edit allowance amount
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openComponentModal', $assignment->id)
            ->set('componentAmount', '900000')
            ->call('saveComponent')
            ->assertHasNoErrors();

        $this->assertEquals(900000.00, (float) $assignment->fresh()->amount);

        // 3. Delete assignment
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('deleteComponent', $assignment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_salary_components', [
            'id' => $assignment->id,
        ]);
    }

    public function test_payroll_calculation_incorporates_assigned_employee_components(): void
    {
        $employee = Employee::factory()->create();
        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'basic_salary' => 6000000.00,
            'effective_from' => '2026-01-01',
            'revision_reason' => 'Penetapan Gaji Pokok',
            'created_by' => 1,
        ]);

        $allowance = SalaryComponent::create([
            'code' => 'TUNJ_TRANSPORT',
            'name' => 'Tunjangan Transportasi',
            'type' => 'allowance',
            'default_amount' => 500000.00,
            'is_active' => true,
        ]);

        $deduction = SalaryComponent::create([
            'code' => 'IURAN_KOP',
            'name' => 'Simpanan Wajib Koperasi',
            'type' => 'deduction',
            'default_amount' => 200000.00,
            'is_active' => true,
        ]);

        EmployeeSalaryComponent::create([
            'employee_id' => $employee->id,
            'salary_component_id' => $allowance->id,
            'amount' => 500000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        EmployeeSalaryComponent::create([
            'employee_id' => $employee->id,
            'salary_component_id' => $deduction->id,
            'amount' => 200000.00,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll April 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'cutoff_date' => '2026-04-25',
            'is_closed' => false,
        ]);

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'run_date' => Carbon::now(),
            'status' => 'draft',
            'total_gross_pay' => 0,
            'total_deductions' => 0,
            'total_net_pay' => 0,
            'processed_by' => 1,
        ]);

        $service = new PayrollCalculationService();
        $service->calculateRun($run, new \Illuminate\Database\Eloquent\Collection([$employee]));

        $item = $run->fresh()->items()->first();

        $this->assertNotNull($item);
        $this->assertEquals(6000000.00, (float) $item->basic_salary);
        $this->assertEquals(500000.00, (float) $item->allowances);
        $this->assertEquals(200000.00, (float) $item->deductions);

        // Check child PayrollComponent records
        $this->assertCount(2, $item->components);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_item_id' => $item->id,
            'name' => 'Tunjangan Transportasi',
            'type' => 'allowance',
            'amount' => 500000.00,
        ]);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_item_id' => $item->id,
            'name' => 'Simpanan Wajib Koperasi',
            'type' => 'deduction',
            'amount' => 200000.00,
        ]);
    }

    public function test_payroll_calculation_calculates_variable_allowance_based_on_attendance(): void
    {
        $employeeWithAttendance = Employee::factory()->create();
        $employeeWithoutAttendance = Employee::factory()->create();

        EmployeeSalary::create([
            'employee_id' => $employeeWithAttendance->id,
            'basic_salary' => 5000000.00,
            'effective_from' => '2026-01-01',
            'revision_reason' => 'Gaji Pokok',
            'created_by' => 1,
        ]);

        EmployeeSalary::create([
            'employee_id' => $employeeWithoutAttendance->id,
            'basic_salary' => 5000000.00,
            'effective_from' => '2026-01-01',
            'revision_reason' => 'Gaji Pokok',
            'created_by' => 1,
        ]);

        // 1. Tunjangan Rutin (is_fixed = true)
        $fixedComp = SalaryComponent::create([
            'code' => 'TUNJ_JAB_RUTIN',
            'name' => 'Tunjangan Jabatan',
            'type' => 'allowance',
            'is_fixed' => true,
            'default_amount' => 1000000.00,
            'is_active' => true,
        ]);

        // 2. Tunjangan Variabel (is_fixed = false) - misal Uang Makan Rp 50.000 / hari
        $variableComp = SalaryComponent::create([
            'code' => 'TUNJ_MAKAN_VAR',
            'name' => 'Uang Makan',
            'type' => 'allowance',
            'is_fixed' => false,
            'default_amount' => 50000.00,
            'is_active' => true,
        ]);

        // Assign both to employeeWithAttendance
        EmployeeSalaryComponent::create([
            'employee_id' => $employeeWithAttendance->id,
            'salary_component_id' => $fixedComp->id,
            'amount' => 1000000.00,
            'effective_from' => '2026-04-01',
            'is_active' => true,
        ]);
        EmployeeSalaryComponent::create([
            'employee_id' => $employeeWithAttendance->id,
            'salary_component_id' => $variableComp->id,
            'amount' => 50000.00,
            'effective_from' => '2026-04-01',
            'is_active' => true,
        ]);

        // Assign both to employeeWithoutAttendance
        EmployeeSalaryComponent::create([
            'employee_id' => $employeeWithoutAttendance->id,
            'salary_component_id' => $fixedComp->id,
            'amount' => 1000000.00,
            'effective_from' => '2026-04-01',
            'is_active' => true,
        ]);
        EmployeeSalaryComponent::create([
            'employee_id' => $employeeWithoutAttendance->id,
            'salary_component_id' => $variableComp->id,
            'amount' => 50000.00,
            'effective_from' => '2026-04-01',
            'is_active' => true,
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Payroll April 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'cutoff_date' => '2026-04-25',
            'is_closed' => false,
        ]);

        // Create 18 present, 2 late (total 20 valid attendances), and 3 absent within cutoff period
        for ($i = 1; $i <= 18; $i++) {
            Attendance::create([
                'employee_id' => $employeeWithAttendance->id,
                'date' => sprintf('2026-04-%02d', $i),
                'clock_in' => sprintf('2026-04-%02d 07:55:00', $i),
                'clock_out' => sprintf('2026-04-%02d 16:00:00', $i),
                'status' => 'present',
                'work_duration_minutes' => 485,
            ]);
        }
        for ($i = 19; $i <= 20; $i++) {
            Attendance::create([
                'employee_id' => $employeeWithAttendance->id,
                'date' => sprintf('2026-04-%02d', $i),
                'clock_in' => sprintf('2026-04-%02d 08:15:00', $i),
                'clock_out' => sprintf('2026-04-%02d 16:00:00', $i),
                'status' => 'late',
                'work_duration_minutes' => 465,
                'late_duration_minutes' => 15,
            ]);
        }
        for ($i = 21; $i <= 23; $i++) {
            Attendance::create([
                'employee_id' => $employeeWithAttendance->id,
                'date' => sprintf('2026-04-%02d', $i),
                'status' => 'absent',
            ]);
        }

        // Attendance after cutoff date (e.g. 2026-04-26) should NOT be counted in April payroll
        Attendance::create([
            'employee_id' => $employeeWithAttendance->id,
            'date' => '2026-04-26',
            'clock_in' => '2026-04-26 07:55:00',
            'clock_out' => '2026-04-26 16:00:00',
            'status' => 'present',
        ]);

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'run_date' => '2026-04-26',
            'status' => 'draft',
            'total_gross_pay' => 0,
            'total_deductions' => 0,
            'total_net_pay' => 0,
            'processed_by' => 1,
        ]);

        $service = new PayrollCalculationService();
        $service->calculateRun($run, new \Illuminate\Database\Eloquent\Collection([
            $employeeWithAttendance,
            $employeeWithoutAttendance,
        ]));

        $itemWithAttendance = $run->fresh()->items()->where('employee_id', $employeeWithAttendance->id)->first();
        $itemWithoutAttendance = $run->fresh()->items()->where('employee_id', $employeeWithoutAttendance->id)->first();

        $this->assertNotNull($itemWithAttendance);
        $this->assertNotNull($itemWithoutAttendance);

        // Employee with attendance:
        // Rutin (1.000.000) + Variabel (20 hari * 50.000 = 1.000.000) = 2.000.000
        $this->assertEquals(2000000.00, (float) $itemWithAttendance->allowances);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_item_id' => $itemWithAttendance->id,
            'name' => 'Tunjangan Jabatan',
            'type' => 'allowance',
            'amount' => 1000000.00,
        ]);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_item_id' => $itemWithAttendance->id,
            'name' => 'Uang Makan (20 hari × Rp 50.000)',
            'type' => 'allowance',
            'amount' => 1000000.00,
        ]);

        // Employee without attendance:
        // Rutin (1.000.000) + Variabel (0 hari * 50.000 = 0) = 1.000.000
        $this->assertEquals(1000000.00, (float) $itemWithoutAttendance->allowances);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_item_id' => $itemWithoutAttendance->id,
            'name' => 'Tunjangan Jabatan',
            'type' => 'allowance',
            'amount' => 1000000.00,
        ]);
        $this->assertDatabaseHas('payroll_components', [
            'payroll_item_id' => $itemWithoutAttendance->id,
            'name' => 'Uang Makan (0 hari × Rp 50.000)',
            'type' => 'allowance',
            'amount' => 0.00,
        ]);
    }
}

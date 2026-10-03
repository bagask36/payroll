<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeShow;
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
}

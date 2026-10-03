<?php

namespace Tests\Feature;

use App\Models\BpjsRule;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            MasterPayrollAndSystemSeeder::class,
            OrganizationSeeder::class,
            EmployeeSeeder::class,
            DoctorSeeder::class,
            ScheduleSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
            PayrollSeeder::class,
        ]);
    }

    public function test_it_seeds_regulatory_bpjs_rules_and_period(): void
    {
        $this->assertDatabaseHas('bpjs_rules', ['program_name' => 'kesehatan', 'employee_rate' => 0.0100]);
        $this->assertDatabaseHas('bpjs_rules', ['program_name' => 'jht', 'employee_rate' => 0.0200]);
        $this->assertDatabaseHas('bpjs_rules', ['program_name' => 'jp', 'employee_rate' => 0.0100]);
        $this->assertDatabaseHas('payroll_periods', ['name' => 'Payroll Periode Oktober 2026']);
    }

    public function test_bpjs_capped_contribution_calculations(): void
    {
        $kesRule = BpjsRule::where('program_name', 'kesehatan')->firstOrFail();

        // High earner: Rp 18,000,000 (exceeds 12,000,000 ceiling)
        $salary = 18000000.00;
        $this->assertEquals(120000.00, $kesRule->calculateEmployeeContribution($salary));
        $this->assertEquals(480000.00, $kesRule->calculateEmployerContribution($salary));

        // Standard earner: Rp 6,000,000 (below 12,000,000 ceiling)
        $standardSalary = 6000000.00;
        $this->assertEquals(60000.00, $kesRule->calculateEmployeeContribution($standardSalary));
        $this->assertEquals(240000.00, $kesRule->calculateEmployerContribution($standardSalary));
    }

    public function test_payroll_calculation_service_generates_payslips(): void
    {
        $run = PayrollRun::firstOrFail();
        $this->assertEquals('calculated', $run->status);
        $this->assertGreaterThan(0.00, (float) $run->total_gross_pay);
        $this->assertGreaterThan(0.00, (float) $run->total_net_pay);

        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $item = PayrollItem::where('employee_id', $nurse->id)->firstOrFail();

        $this->assertEquals(5200000.00, (float) $item->basic_salary);
        $this->assertGreaterThan(0.00, (float) $item->bpjs_kes_employee);
        $this->assertGreaterThan(0.00, (float) $item->bpjs_tk_employee);
        $this->assertGreaterThan(0.00, (float) $item->pph21_tax);
        $this->assertEquals('BNI', $item->bank_name);
        $this->assertNotNull($item->bank_account_number);

        // Verify net salary formula: gross - (deductions + kes + tk + pph21)
        $expectedNet = (float) $item->basic_salary + (float) $item->allowances
            - ((float) $item->deductions + (float) $item->bpjs_kes_employee + (float) $item->bpjs_tk_employee + (float) $item->pph21_tax);
        $this->assertEquals(round($expectedNet, 2), (float) $item->net_salary);
    }

    public function test_immutability_protection_on_finalized_payroll(): void
    {
        $run = PayrollRun::firstOrFail();
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        // Finalize the run
        $run->finalize($hrManager);
        $this->assertTrue($run->isFinalized());

        // Attempting to modify a finalized item throws DomainException
        $item = $run->items->first();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Immutability violation');
        $item->update(['basic_salary' => 9999999.00]);
    }

    public function test_finalized_run_cannot_be_deleted(): void
    {
        $run = PayrollRun::firstOrFail();
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        $run->finalize($hrManager);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Immutability violation');
        $run->delete();
    }

    public function test_payroll_policy_authorization(): void
    {
        $run = PayrollRun::firstOrFail();
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $item = PayrollItem::where('employee_id', $nurse->id)->firstOrFail();
        $nurseUser = $nurse->user;

        // 1. Employee viewing their OWN payslip item: ALLOWED
        $this->assertTrue(Gate::forUser($nurseUser)->allows('view', $item));

        // 2. Employee viewing hospital-wide payroll run: DENIED
        $this->assertFalse(Gate::forUser($nurseUser)->allows('view', $run));
        $this->assertFalse(Gate::forUser($nurseUser)->allows('calculate', PayrollRun::class));
        $this->assertFalse(Gate::forUser($nurseUser)->allows('finalize', $run));

        // 3. Payroll Officer: can calculate, but CANNOT finalize
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');
        $this->assertTrue(Gate::forUser($payrollUser)->allows('view', $run));
        $this->assertTrue(Gate::forUser($payrollUser)->allows('calculate', PayrollRun::class));
        $this->assertFalse(Gate::forUser($payrollUser)->allows('finalize', $run));

        // 4. Finance Officer: CAN finalize (Segregation of Duties)
        $financeUser = User::factory()->create();
        $financeUser->assignRole('finance');
        $this->assertTrue(Gate::forUser($financeUser)->allows('finalize', $run));

        // 5. HR Manager: CANNOT finalize (segregated to Finance)
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');
        $this->assertFalse(Gate::forUser($hrManager)->allows('finalize', $run));
    }
}

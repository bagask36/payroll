<?php

namespace Tests\Feature;

use App\Models\BpjsRule;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Pph21Parameter;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\Pph21CalculationService;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Pph21CalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            MasterPayrollAndSystemSeeder::class,
        ]);
    }

    public function test_single_employee_tax_calculation_tk0(): void
    {
        $service = new Pph21CalculationService();

        $employee = Employee::factory()->create([
            'marital_status' => 'single',
            'tax_dependents' => 0,
        ]);

        // Monthly salary: 5,200,000
        // Kes Employer (4%): 208,000
        // JKK Employer (0.24%): 12,480
        // JKM Employer (0.30%): 15,600
        // JHT Employee (2%): 104,000
        // JP Employee (1%): 52,000
        // Taxable Gross = 5,436,080
        // Biaya Jabatan (5%) = 271,804
        // Pensiun Deductions = 156,000
        // Total Deductions = 427,804
        // Monthly Net = 5,008,276
        // Annual Net = 60,099,312
        // PTKP (TK/0) = 54,000,000
        // Annual PKP = 6,099,000
        // Tax (5%) = 304,950 / 12 = 25,412.50
        $result = $service->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 5200000.00,
            taxableAllowances: 0.0,
            employerBpjs: [
                'kes' => 208000.00,
                'jkk' => 12480.00,
                'jkm' => 15600.00,
            ],
            employeeBpjs: [
                'jht' => 104000.00,
                'jp' => 52000.00,
            ],
            asOfDate: '2026-10-01'
        );

        $this->assertEquals('PTKP_TK0', $result['ptkp_code']);
        $this->assertEquals(54000000.00, $result['ptkp_annual']);
        $this->assertEquals(5436080.00, $result['taxable_gross']);
        $this->assertEquals(271804.00, $result['biaya_jabatan']);
        $this->assertEquals(156000.00, $result['pensiun_deductions']);
        $this->assertEquals(6099000.00, $result['annual_pkp']);
        $this->assertEquals(25412.50, $result['monthly_tax']);
    }

    public function test_married_employee_with_dependents_k1(): void
    {
        $service = new Pph21CalculationService();

        $employee = Employee::factory()->create([
            'marital_status' => 'married',
            'tax_dependents' => 1,
        ]);

        $result = $service->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 6500000.00,
            taxableAllowances: 0.0,
            employerBpjs: [
                'kes' => 260000.00,
                'jkk' => 15600.00,
                'jkm' => 19500.00,
            ],
            employeeBpjs: [
                'jht' => 130000.00,
                'jp' => 65000.00,
            ],
            asOfDate: '2026-10-01'
        );

        $this->assertEquals('PTKP_K1', $result['ptkp_code']);
        $this->assertEquals(63000000.00, $result['ptkp_annual']);
        $this->assertGreaterThan(0.0, $result['monthly_tax']);
    }

    public function test_high_income_doctor_crosses_progressive_brackets(): void
    {
        $service = new Pph21CalculationService();

        $doctor = Employee::factory()->create([
            'marital_status' => 'married',
            'tax_dependents' => 2,
        ]);

        // Basic salary: 18,000,000
        $result = $service->calculateMonthlyTax(
            employee: $doctor,
            basicSalary: 18000000.00,
            taxableAllowances: 2500000.00,
            employerBpjs: [
                'kes' => 480000.00,
                'jkk' => 43200.00,
                'jkm' => 54000.00,
            ],
            employeeBpjs: [
                'jht' => 360000.00,
                'jp' => 100423.00,
            ],
            asOfDate: '2026-10-01'
        );

        // Biaya jabatan is capped at 500,000
        $this->assertEquals(500000.00, $result['biaya_jabatan']);
        $this->assertEquals('PTKP_K2', $result['ptkp_code']);
        $this->assertEquals(67500000.00, $result['ptkp_annual']);
        // PKP should be well over 60,000,000 (crossing into Lapisan II 15%)
        $this->assertGreaterThan(60000000.00, $result['annual_pkp']);
        $this->assertGreaterThan(1000000.00, $result['monthly_tax']);
    }

    public function test_income_below_ptkp_yields_zero_tax(): void
    {
        $service = new Pph21CalculationService();

        $employee = Employee::factory()->create([
            'marital_status' => 'single',
            'tax_dependents' => 0,
        ]);

        // Monthly salary: 3,500,000 (annualized ~ 42m < 54m PTKP)
        $result = $service->calculateMonthlyTax(
            employee: $employee,
            basicSalary: 3500000.00,
            taxableAllowances: 0.0,
            employerBpjs: ['kes' => 140000.0, 'jkk' => 8400.0, 'jkm' => 10500.0],
            employeeBpjs: ['jht' => 70000.0, 'jp' => 35000.0],
            asOfDate: '2026-10-01'
        );

        $this->assertEquals(0.00, $result['annual_pkp']);
        $this->assertEquals(0.00, $result['monthly_tax']);
    }

    public function test_payroll_run_calculation_integrates_pph21_and_deducts_from_net_salary(): void
    {
        $this->seed([
            \Database\Seeders\OrganizationSeeder::class,
            \Database\Seeders\EmployeeSeeder::class,
            PayrollSeeder::class,
        ]);

        $employee = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $run = PayrollRun::firstOrFail();

        $item = PayrollItem::where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertGreaterThan(0.00, (float) $item->pph21_tax);
        $this->assertEquals(25412.50, (float) $item->pph21_tax);

        // Verify net pay includes PPh 21 deduction
        $gross = (float) $item->basic_salary + (float) $item->allowances;
        $totalDeductions = (float) $item->deductions + (float) $item->bpjs_kes_employee + (float) $item->bpjs_tk_employee + (float) $item->pph21_tax;
        $this->assertEquals(round($gross - $totalDeductions, 2), (float) $item->net_salary);
    }
}

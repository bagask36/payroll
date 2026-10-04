<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalary;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Services\Payroll\ThrCalculationService;
use Carbon\Carbon;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected ThrCalculationService $thrService;
    protected Unit $unit;
    protected Position $position;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            MasterPayrollAndSystemSeeder::class,
        ]);

        $this->thrService = app(ThrCalculationService::class);
        $dept = \App\Models\Department::create(['name' => 'Pelayanan Medis', 'code' => 'YANMED']);
        $this->unit = Unit::create(['name' => 'Instalasi Gawat Darurat', 'code' => 'IGD', 'department_id' => $dept->id]);
        $this->position = Position::create([
            'title' => 'Perawat Pelaksana',
            'code' => 'NURSE-01',
            'department_id' => $dept->id,
            'default_basic_salary' => 5000000.00,
            'default_positional_allowance' => 500000.00,
        ]);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');
    }

    private function createEmployeeWithContract(
        string $nip,
        string $startDate,
        float $basicSalary,
        string $religion = 'islam',
        ?float $positionalAllowance = 500000.00
    ): Employee {
        $position = Position::create([
            'title' => 'Staf '.$nip,
            'code' => 'POS-'.$nip,
            'default_basic_salary' => $basicSalary,
            'default_positional_allowance' => $positionalAllowance,
        ]);

        $emp = Employee::create([
            'nip' => $nip,
            'full_name' => 'Karyawan '.$nip,
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'marital_status' => 'single',
            'religion' => $religion,
            'tax_dependents' => 0,
            'phone_number' => '08123456789',
            'residential_address' => 'Jl. Test',
            'unit_id' => $this->unit->id,
            'position_id' => $position->id,
            'ktp_number' => '3201234567890001',
            'status' => 'active',
        ]);

        EmployeeEmployment::create([
            'employee_id' => $emp->id,
            'unit_id' => $this->unit->id,
            'position_id' => $position->id,
            'contract_type' => 'permanent',
            'contract_number' => 'SPK/'.$nip,
            'start_date' => $startDate,
            'is_active' => true,
        ]);

        EmployeeSalary::create([
            'employee_id' => $emp->id,
            'basic_salary' => $basicSalary,
            'effective_from' => $startDate,
            'revision_reason' => 'Initial Salary',
            'created_by' => $this->adminUser->id,
        ]);

        EmployeeBankAccount::create([
            'employee_id' => $emp->id,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Karyawan '.$nip,
            'is_primary' => true,
        ]);

        return $emp;
    }

    public function test_employee_with_less_than_one_month_tenure_is_not_eligible_for_thr(): void
    {
        $cutoffDate = Carbon::parse('2026-04-10'); // Lebaran ~April 2026
        // Hired 2 weeks before cutoff
        $emp = $this->createEmployeeWithContract('EMP-NEW', '2026-03-28', 5000000.00);

        $preview = $this->thrService->previewEmployeeThr($emp, $cutoffDate);

        $this->assertNotNull($preview);
        $this->assertEquals(0, $preview['tenure_months']);
        $this->assertEquals(0.0, $preview['factor']);
        $this->assertFalse($preview['is_eligible']);
        $this->assertEquals(0.0, $preview['gross_thr']);
    }

    public function test_employee_with_twelve_months_or_more_receives_full_thr(): void
    {
        $cutoffDate = Carbon::parse('2026-04-10');
        // Hired 2 years prior
        $emp = $this->createEmployeeWithContract('EMP-SENIOR', '2024-01-01', 6000000.00, 'islam', 1000000.00);

        $preview = $this->thrService->previewEmployeeThr($emp, $cutoffDate);

        $this->assertNotNull($preview);
        $this->assertGreaterThanOrEqual(12, $preview['tenure_months']);
        $this->assertEquals(1.0, $preview['factor']);
        $this->assertTrue($preview['is_eligible']);
        $this->assertEquals(7000000.00, $preview['gross_thr']); // 6jt basic + 1jt allowance
        $this->assertEquals(6000000.00, $preview['thr_basic']);
        $this->assertEquals(1000000.00, $preview['thr_allowance']);
    }

    public function test_employee_with_six_months_tenure_receives_pro_rated_fifty_percent_thr(): void
    {
        $cutoffDate = Carbon::parse('2026-04-10');
        // Hired exactly 6 months prior
        $emp = $this->createEmployeeWithContract('EMP-MID', '2025-10-10', 6000000.00, 'islam', 0.00);

        $preview = $this->thrService->previewEmployeeThr($emp, $cutoffDate);

        $this->assertNotNull($preview);
        $this->assertEquals(6, $preview['tenure_months']);
        $this->assertEquals(0.5, $preview['factor']); // 6 / 12 = 0.5
        $this->assertTrue($preview['is_eligible']);
        $this->assertEquals(3000000.00, $preview['gross_thr']); // 50% of 6,000,000
    }

    public function test_religion_filter_excludes_non_matching_employees(): void
    {
        $cutoffDate = Carbon::parse('2026-04-10');
        $empMuslim = $this->createEmployeeWithContract('EMP-ISLAM', '2024-01-01', 5000000.00, 'islam');
        $empChristian = $this->createEmployeeWithContract('EMP-KRISTEN', '2024-01-01', 5000000.00, 'kristen');

        // Target: Idul Fitri (Islam only)
        $previewMuslim = $this->thrService->previewEmployeeThr($empMuslim, $cutoffDate, ['target_religion' => 'islam']);
        $previewChristian = $this->thrService->previewEmployeeThr($empChristian, $cutoffDate, ['target_religion' => 'islam']);

        $this->assertNotNull($previewMuslim);
        $this->assertTrue($previewMuslim['is_eligible']);
        $this->assertNull($previewChristian); // Excluded by filter
    }

    public function test_thr_run_calculation_creates_items_without_bpjs_and_with_correct_metadata(): void
    {
        $period = PayrollPeriod::create([
            'name' => 'Off-Cycle THR Idul Fitri 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-10',
            'cutoff_date' => '2026-04-10',
            'is_closed' => false,
        ]);

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'run_date' => '2026-04-10',
            'type' => 'thr',
            'status' => 'draft',
            'processed_by' => $this->adminUser->id,
        ]);

        $emp1 = $this->createEmployeeWithContract('EMP-1', '2024-01-01', 5000000.00, 'islam', 500000.00);
        $emp2 = $this->createEmployeeWithContract('EMP-2', '2025-10-10', 4000000.00, 'islam', 0.00); // 6 mos = 50%
        $empShort = $this->createEmployeeWithContract('EMP-SHORT', '2026-04-01', 5000000.00, 'islam', 0.00); // <1 mo

        $employees = Employee::whereIn('id', [$emp1->id, $emp2->id, $empShort->id])->get();

        $this->thrService->calculateThrRun(
            $run,
            $employees,
            '2026-04-10',
            ['holiday_name' => 'Idul Fitri 1447 H', 'target_religion' => 'islam']
        );

        $run->refresh();
        $this->assertEquals('calculated', $run->status);
        $this->assertEquals('thr', $run->type);
        $this->assertTrue($run->isThr());
        $this->assertEquals(2, $run->metadata['processed_count']); // Only emp1 and emp2 (empShort excluded)
        $this->assertEquals('Idul Fitri 1447 H', $run->metadata['holiday_name']);

        // Check Items
        $items = $run->items;
        $this->assertCount(2, $items);

        $item1 = $items->where('employee_id', $emp1->id)->first();
        $this->assertNotNull($item1);
        $this->assertEquals(5000000.00, (float) $item1->basic_salary);
        $this->assertEquals(500000.00, (float) $item1->allowances);
        $this->assertEquals(0.00, (float) $item1->bpjs_kes_employee);
        $this->assertEquals(0.00, (float) $item1->bpjs_tk_employee);
        $this->assertEquals(0.00, (float) $item1->bpjs_kes_employer);
        $this->assertEquals(0.00, (float) $item1->bpjs_tk_employer);
        $this->assertGreaterThanOrEqual(0.00, (float) $item1->pph21_tax);
        $this->assertEquals((float) $item1->basic_salary + (float) $item1->allowances - (float) $item1->pph21_tax, (float) $item1->net_salary);

        $item2 = $items->where('employee_id', $emp2->id)->first();
        $this->assertNotNull($item2);
        // 50% of 4,000,000 = 2,000,000
        $this->assertEquals(2000000.00, (float) $item2->basic_salary);

        // Verify component itemization
        $this->assertTrue($item1->components()->where('name', 'like', '%THR Gaji Pokok%')->exists());
        $this->assertTrue($item1->components()->where('name', 'like', '%THR Tunjangan Tetap%')->exists());
    }

    public function test_finalized_thr_run_is_strictly_immutable(): void
    {
        $period = PayrollPeriod::create([
            'name' => 'Off-Cycle THR Natal 2026',
            'start_date' => '2026-12-15',
            'end_date' => '2026-12-24',
            'cutoff_date' => '2026-12-24',
            'is_closed' => false,
        ]);

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'run_date' => '2026-12-24',
            'type' => 'thr',
            'status' => 'draft',
            'processed_by' => $this->adminUser->id,
        ]);

        $emp = $this->createEmployeeWithContract('EMP-KRIS', '2024-01-01', 5000000.00, 'kristen');
        $this->thrService->calculateThrRun($run, collect([$emp]), '2026-12-24');

        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');
        $run->finalize($hrManager);

        $this->assertTrue($run->isFinalized());

        // Attempting to recalculate throws DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Immutability violation');
        $this->thrService->calculateThrRun($run, collect([$emp]), '2026-12-24');
    }
}

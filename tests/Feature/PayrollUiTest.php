<?php

namespace Tests\Feature;

use App\Livewire\Payroll\MyPayslips;
use App\Livewire\Payroll\PayrollIndex;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
            EmployeeSeeder::class,
            DoctorSeeder::class,
            ScheduleSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
            PayrollSeeder::class,
        ]);
    }

    public function test_unauthorized_employee_cannot_access_payroll_management_screen(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');

        $this->actingAs($employeeUser);

        $this->get(route('payroll.index'))
            ->assertStatus(403);
    }

    public function test_payroll_officer_can_view_payroll_index_and_run_items(): void
    {
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');

        $run = PayrollRun::firstOrFail();

        $this->actingAs($payrollUser);

        Livewire::test(PayrollIndex::class)
            ->assertStatus(200)
            ->assertSee('Manajemen Penggajian')
            ->assertSee('Kalkulasi kompensasi berkala')
            ->assertSee('Total Gaji Bersih')
            ->assertSee('Rincian Slip Gaji Personil');
    }

    public function test_payroll_officer_can_calculate_payroll_for_open_period(): void
    {
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');

        // Create a new fresh open period
        $period = PayrollPeriod::create([
            'name' => 'Payroll Periode November 2026',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'cutoff_date' => '2026-11-25',
            'is_closed' => false,
        ]);

        $this->actingAs($payrollUser);

        Livewire::test(PayrollIndex::class)
            ->set('selectedPeriodId', $period->id)
            ->call('calculatePayroll')
            ->assertHasNoErrors()
            ->assertSee('berhasil dikalkulasi');

        $this->assertDatabaseHas('payroll_runs', [
            'payroll_period_id' => $period->id,
            'status' => 'calculated',
        ]);

        $newRun = PayrollRun::where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertGreaterThan(0.00, (float) $newRun->total_net_pay);
        $this->assertGreaterThan(0, $newRun->items()->count());
    }

    public function test_payroll_officer_cannot_finalize_payroll_due_to_segregation_of_duties(): void
    {
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');

        $run = PayrollRun::firstOrFail();

        $this->actingAs($payrollUser);

        // Expect 403 Forbidden because only finance or super_admin has payroll.finalize
        Livewire::test(PayrollIndex::class, ['selectedRunId' => $run->id])
            ->call('finalizeRun')
            ->assertStatus(403);

        $this->assertFalse($run->fresh()->isFinalized());
    }

    public function test_finance_manager_can_finalize_payroll_and_lock_immutability(): void
    {
        $financeUser = User::factory()->create();
        $financeUser->assignRole('finance');

        $run = PayrollRun::firstOrFail();
        $this->assertEquals('calculated', $run->status);

        $this->actingAs($financeUser);

        Livewire::test(PayrollIndex::class, ['selectedRunId' => $run->id])
            ->call('finalizeRun')
            ->assertHasNoErrors()
            ->assertSee('Penggajian berhasil difinalisasi dan dikunci');

        $this->assertTrue($run->fresh()->isFinalized());
        $this->assertEquals('finalized', $run->fresh()->status);
        $this->assertEquals($financeUser->id, $run->fresh()->finalized_by);

        // Verify immutability: modification throws DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Immutability violation');
        $run->fresh()->items()->first()->update(['basic_salary' => 99999999.00]);
    }

    public function test_employee_can_access_my_payslips_and_view_their_own_breakdown(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;

        $this->actingAs($nurseUser);

        $run = PayrollRun::firstOrFail();
        $item = PayrollItem::where('employee_id', $nurse->id)->firstOrFail();

        Livewire::test(MyPayslips::class)
            ->assertStatus(200)
            ->assertSee('Riwayat Slip Gaji Saya')
            ->assertSee($nurse->full_name)
            ->assertSee('PEG-2025042')
            ->call('viewSlip', $item->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('SLIP GAJI ELEKTRONIK')
            ->assertSee('Gaji Bersih Diterima');
    }

    public function test_user_without_linked_employee_is_forbidden_from_my_payslips(): void
    {
        $userWithoutEmployee = User::factory()->create();

        $this->actingAs($userWithoutEmployee);

        Livewire::test(MyPayslips::class)
            ->assertStatus(403);
    }

    public function test_finance_manager_can_export_bank_disbursement_csv(): void
    {
        $financeUser = User::factory()->create();
        $financeUser->assignRole('finance');

        $run = PayrollRun::firstOrFail();

        $this->actingAs($financeUser);

        $response = Livewire::test(PayrollIndex::class, ['selectedRunId' => $run->id])
            ->call('exportBankDisbursement');

        $response->assertFileDownloaded();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.exported',
            'auditable_id' => $run->id,
        ]);
    }

    public function test_payroll_officer_without_export_permission_cannot_export_bank_disbursement(): void
    {
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');

        $run = PayrollRun::firstOrFail();

        $this->actingAs($payrollUser);

        Livewire::test(PayrollIndex::class, ['selectedRunId' => $run->id])
            ->call('exportBankDisbursement')
            ->assertStatus(403);
    }

    public function test_employee_can_view_official_printable_payslip(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $item = PayrollItem::where('employee_id', $nurse->id)->firstOrFail();

        $this->actingAs($nurse->user);

        $response = $this->get(route('payroll.payslips.print', $item->id));
        $response->assertStatus(200);
        $response->assertSee('RUMAH SAKIT JUWITA');
        $response->assertSee('Slip Penghasilan Pegawai');
        $response->assertSee($nurse->full_name);
        $response->assertSee('PEG-2025042');
        $response->assertSee('Dokumen Rahasia');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.payslip_printed',
            'auditable_id' => $item->id,
        ]);
    }

    public function test_unauthorized_employee_cannot_view_another_employee_printable_payslip(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $item = PayrollItem::where('employee_id', $nurse->id)->firstOrFail();

        // Create a different employee user
        $otherUser = User::factory()->create();
        $otherUser->assignRole('employee');

        $this->actingAs($otherUser);

        $response = $this->get(route('payroll.payslips.print', $item->id));
        $response->assertStatus(403);
    }
}

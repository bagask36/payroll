<?php

namespace Tests\Feature;

use App\Livewire\Report\Pph21ReportIndex;
use App\Livewire\Tables\Pph21ReportTable;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Pph21ReportTest extends TestCase
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
            PayrollSeeder::class,
        ]);
    }

    public function test_authorized_finance_user_can_access_pph21_report_page(): void
    {
        $financeUser = User::factory()->create();
        $financeUser->assignRole('finance');

        $this->actingAs($financeUser)
            ->get(route('reports.pph21'))
            ->assertStatus(200)
            ->assertSee('Laporan Pemotongan PPh 21 Pegawai')
            ->assertSee('Total PPh 21 Terutang')
            ->assertSee('Total Penghasilan Bruto')
            ->assertSee('Total Peserta Terdaftar');
    }

    public function test_authorized_payroll_user_can_access_pph21_report_page(): void
    {
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');

        $this->actingAs($payrollUser)
            ->get(route('reports.pph21'))
            ->assertStatus(200)
            ->assertSee('Laporan Pemotongan PPh 21 Pegawai');
    }

    public function test_unauthorized_employee_cannot_access_pph21_report_page(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');

        $this->actingAs($employeeUser)
            ->get(route('reports.pph21'))
            ->assertStatus(403);
    }

    public function test_pph21_report_index_renders_livewire_component_and_metrics(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();

        $this->actingAs($admin);

        $totalPph = PayrollItem::sum('pph21_tax');

        Livewire::test(Pph21ReportIndex::class)
            ->assertStatus(200)
            ->assertSee('Laporan Pemotongan PPh 21 Pegawai')
            ->assertSee(number_format((float) $totalPph, 0, ',', '.'))
            ->assertSeeLivewire(Pph21ReportTable::class);
    }

    public function test_pph21_report_table_renders_employee_tax_data(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();
        $this->actingAs($admin);

        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();

        Livewire::test(Pph21ReportTable::class)
            ->assertStatus(200)
            ->assertSee($nurse->full_name)
            ->assertSee($nurse->nip)
            ->assertSee('TK/0');
    }

    public function test_pph21_report_can_export_csv(): void
    {
        $financeUser = User::factory()->create();
        $financeUser->assignRole('finance');

        $this->actingAs($financeUser);

        $component = Livewire::test(Pph21ReportIndex::class)
            ->call('exportCsv');

        $response = $component->effects['download'] ?? null;
        $this->assertNotNull($response, 'CSV download response should be generated');
        $this->assertStringContainsString('laporan-pph21-rs-juwita', $response['name'] ?? '');
    }

    public function test_pph21_report_filtering_by_period(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();
        $this->actingAs($admin);

        $period = PayrollPeriod::firstOrFail();

        Livewire::test(Pph21ReportIndex::class)
            ->set('periodFilter', $period->id)
            ->assertDispatched('pg:filterChanged');
    }
}

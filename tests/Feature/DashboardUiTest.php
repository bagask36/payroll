<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_authenticated_user_can_access_dashboard_page(): void
    {
        $user = User::factory()->create([
            'name' => 'dr. Juwita Sp.A',
            'is_active' => true,
        ]);
        $user->assignRole('doctor');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Portal Tenaga Medis');
        $response->assertSee('dr. Juwita Sp.A');
    }

    public function test_employee_dashboard_renders_personal_self_service_and_not_executive_metrics(): void
    {
        $employeeUser = User::factory()->create([
            'name' => 'Budi Santoso',
            'is_active' => true,
        ]);
        $employeeUser->assignRole('employee');

        Employee::factory()->count(10)->create(['status' => 'ACTIVE']);

        // Doctor with expiring STR
        Doctor::factory()->create([
            'name' => 'dr. Hendra Sp.PD',
            'str_valid_until' => Carbon::today()->addDays(10)->toDateString(),
            'sip_valid_until' => Carbon::today()->addYears(2)->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($employeeUser);

        Livewire::test(Dashboard::class)
            ->assertSee('Portal Layanan Mandiri Pegawai')
            ->assertSee('Jadwal Tugas Hari Ini')
            ->assertSee('Presensi Hari Ini')
            ->assertSee('Sisa Saldo Cuti')
            ->assertDontSee('Gaji Bersih')
            // Should NOT see hospital-wide executive metrics
            ->assertDontSee('Distribusi Ketenagaan Rumah Sakit')
            ->assertDontSee('Peringatan Kepatuhan Izin Praktik Medis')
            ->assertDontSee('Total Karyawan Aktif')
            ->assertStatus(200);
    }

    public function test_supervisor_dashboard_renders_unit_team_metrics(): void
    {
        $supervisorUser = User::factory()->create([
            'name' => 'Siti Supervisor',
            'is_active' => true,
        ]);
        $supervisorUser->assignRole('supervisor');

        $unit = \App\Models\Unit::factory()->create(['name' => 'Instalasi Rawat Inap']);
        Employee::factory()->create([
            'user_id' => $supervisorUser->id,
            'unit_id' => $unit->id,
            'status' => 'ACTIVE',
        ]);
        Employee::factory()->count(3)->create([
            'unit_id' => $unit->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($supervisorUser);

        Livewire::test(Dashboard::class)
            ->assertSee('Dashboard Operasional Unit Kerja')
            ->assertSee('Monitoring Tim Unit (Instalasi Rawat Inap)')
            ->assertSee('Total Staf Unit')
            ->assertSee('Antrean Cuti Tier 1')
            ->assertStatus(200);
    }

    public function test_payroll_finance_dashboard_renders_payroll_metrics(): void
    {
        $payrollUser = User::factory()->create([
            'name' => 'Dewi Payroll',
            'is_active' => true,
        ]);
        $payrollUser->assignRole('payroll');

        $period = \App\Models\PayrollPeriod::create([
            'name' => 'April 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'cutoff_date' => '2026-04-25',
            'is_closed' => false,
        ]);

        \App\Models\PayrollRun::create([
            'payroll_period_id' => $period->id,
            'run_date' => Carbon::now(),
            'status' => 'finalized',
            'total_gross_pay' => 75000000,
            'total_deductions' => 7500000,
            'total_net_pay' => 67500000,
            'processed_by' => $payrollUser->id,
        ]);

        $this->actingAs($payrollUser);

        Livewire::test(Dashboard::class)
            ->assertSee('Dashboard Penggajian')
            ->assertSee('Rekapitulasi Penggajian Terakhir')
            ->assertSee('Total Penghasilan Kotor (Bruto)')
            ->assertSee('Dana Bersih (Netto) Disalurkan')
            ->assertStatus(200);
    }

    public function test_management_dashboard_renders_hospital_wide_metrics(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        Employee::factory()->count(5)->create(['status' => 'ACTIVE']);

        Doctor::factory()->create([
            'name' => 'dr. Hendra Sp.PD',
            'str_valid_until' => Carbon::today()->addDays(10)->toDateString(),
            'sip_valid_until' => Carbon::today()->addYears(2)->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(Dashboard::class)
            ->assertSee('Total Karyawan Aktif')
            ->assertSee('Distribusi Ketenagaan Rumah Sakit')
            ->assertSee('dr. Hendra Sp.PD')
            ->assertSee('Peringatan Kepatuhan Izin Praktik Medis')
            ->assertSee('Statistik &amp; Demografi SDM Rumah Sakit', false)
            ->assertSee('Demografi Rentang Usia')
            ->assertSee('Proporsi Jenis Kelamin')
            ->assertSee('Tingkat Pendidikan')
            ->assertSee('Tingkat Kehadiran')
            ->assertSee('Ketepatan Waktu')
            ->assertSee('Kepatuhan Disiplin')
            ->assertSee('Masa Kerja Pegawai (Tenure)')
            ->assertDontSee('Navigasi Operasional Cepat')
            ->call('refreshData')
            ->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $hrAdminUser;

    private User $hrManagerUser;

    private User $payrollUser;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(TestUserSeeder::class);
        $this->seed(MasterPayrollAndSystemSeeder::class);

        $this->adminUser = User::where('email', 'admin@juwitahospital.com')->first();
        $this->hrAdminUser = User::where('email', 'hr@juwitahospital.com')->first();
        $this->hrManagerUser = User::where('email', 'hr.manager@juwitahospital.com')->first();
        $this->payrollUser = User::where('email', 'payroll@juwitahospital.com')->first();
        $this->employeeUser = User::where('email', 'employee@juwitahospital.com')->first();
    }

    public function test_admin_can_access_all_five_reports(): void
    {
        $routes = [
            '/reports/employees',
            '/reports/attendance',
            '/reports/leaves',
            '/reports/payroll',
            '/reports/doctors',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->adminUser)->get($route);
            $response->assertStatus(200, "Admin failed to access {$route}");
        }
    }

    public function test_hr_admin_can_access_operational_reports_but_not_payroll(): void
    {
        $this->actingAs($this->hrAdminUser)->get('/reports/employees')->assertStatus(200);
        $this->actingAs($this->hrAdminUser)->get('/reports/attendance')->assertStatus(200);
        $this->actingAs($this->hrAdminUser)->get('/reports/leaves')->assertStatus(200);
        $this->actingAs($this->hrAdminUser)->get('/reports/doctors')->assertStatus(200);

        // HR Admin must NOT access payroll report per SoD policy
        $this->actingAs($this->hrAdminUser)->get('/reports/payroll')->assertStatus(403);
    }

    public function test_hr_manager_and_payroll_can_access_payroll_report(): void
    {
        $this->actingAs($this->hrManagerUser)->get('/reports/payroll')->assertStatus(200);
        $this->actingAs($this->payrollUser)->get('/reports/payroll')->assertStatus(200);
    }

    public function test_standard_employee_cannot_access_any_report(): void
    {
        $routes = [
            '/reports/employees',
            '/reports/attendance',
            '/reports/leaves',
            '/reports/payroll',
            '/reports/doctors',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->employeeUser)->get($route);
            $response->assertStatus(403, "Employee should not access {$route}");
        }
    }

    public function test_reports_can_export_csv(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Report\EmployeeReportIndex::class)
            ->call('exportCsv')
            ->assertFileDownloaded('laporan-kepegawaian-rs-juwita.csv');

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Report\AttendanceReportIndex::class)
            ->call('exportCsv')
            ->assertFileDownloaded('laporan-kehadiran-rs-juwita.csv');

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Report\LeaveReportIndex::class)
            ->call('exportCsv')
            ->assertFileDownloaded('laporan-cuti-izin-rs-juwita.csv');

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Report\PayrollReportIndex::class)
            ->call('exportCsv')
            ->assertFileDownloaded('laporan-payroll-rs-juwita.csv');

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Report\DoctorReportIndex::class)
            ->call('exportCsv')
            ->assertFileDownloaded('laporan-dokter-rs-juwita.csv');
    }
}

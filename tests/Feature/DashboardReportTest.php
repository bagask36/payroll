<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Reporting\HospitalDashboardService;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DocumentSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardReportTest extends TestCase
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
            DocumentSeeder::class,
        ]);
    }

    public function test_it_generates_accurate_staffing_headcount_metrics(): void
    {
        $service = app(HospitalDashboardService::class);
        $metrics = $service->getStaffingMetrics();

        $this->assertArrayHasKey('total_active_employees', $metrics);
        $this->assertArrayHasKey('total_active_doctors', $metrics);
        $this->assertArrayHasKey('total_workforce', $metrics);
        $this->assertArrayHasKey('contract_breakdown', $metrics);

        $this->assertGreaterThan(0, $metrics['total_active_employees']);
        $this->assertGreaterThan(0, $metrics['total_active_doctors']);
        $this->assertEquals(
            $metrics['total_active_employees'] + $metrics['total_active_doctors'],
            $metrics['total_workforce']
        );
        $this->assertArrayHasKey('permanent', $metrics['contract_breakdown']);
    }

    public function test_it_generates_daily_attendance_metrics(): void
    {
        $service = app(HospitalDashboardService::class);
        $today = Carbon::today()->toDateString();
        $metrics = $service->getAttendanceMetricsForDate($today);

        $this->assertEquals($today, $metrics['date']);
        $this->assertGreaterThan(0, $metrics['total_scheduled']);
        $this->assertGreaterThan(0, $metrics['total_checked_in']);
        $this->assertEquals($metrics['present_count'], $metrics['total_checked_in']);
        $this->assertEquals(0, $metrics['total_late_minutes']);
    }

    public function test_it_generates_monthly_payroll_summary(): void
    {
        $service = app(HospitalDashboardService::class);
        $run = PayrollRun::firstOrFail();
        $summary = $service->getPayrollSummary($run->id);

        $this->assertEquals($run->id, $summary['run_id']);
        $this->assertGreaterThan(0, $summary['total_payslips']);
        $this->assertGreaterThan(0, $summary['total_gross_pay']);
        $this->assertGreaterThan(0, $summary['total_net_pay']);
        $this->assertGreaterThan(0, $summary['total_bpjs_kesehatan']);
        $this->assertGreaterThan(0, $summary['total_bpjs_ketenagakerjaan']);
    }

    public function test_it_detects_doctor_credentials_expiring_soon(): void
    {
        $service = app(HospitalDashboardService::class);

        // Create a doctor whose SIP expires in 25 days
        $expiringDoc = Doctor::factory()->create([
            'sip_valid_until' => Carbon::today()->addDays(25),
            'str_valid_until' => Carbon::today()->addYears(3),
        ]);

        $alerts = $service->getDoctorCredentialsAlerts(30);

        $this->assertNotEmpty($alerts);
        $alertDocIds = array_column($alerts, 'doctor_id');
        $this->assertContains($expiringDoc->id, $alertDocIds);
    }

    public function test_audit_log_service_records_security_events(): void
    {
        $auditService = app(AuditService::class);
        $user = User::firstOrFail();
        $employee = Employee::firstOrFail();

        $log = $auditService->log(
            action: 'employee.salary_revision',
            auditable: $employee,
            oldValues: ['basic_salary' => 5000000, 'password' => 'secret123'],
            newValues: ['basic_salary' => 6500000, 'face_embedding' => 'raw_vector_xyz'],
            userId: $user->id
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'employee.salary_revision',
            'user_id' => $user->id,
            'auditable_type' => $employee->getMorphClass(),
            'auditable_id' => $employee->id,
        ]);

        // Redaction verification
        $this->assertEquals('[REDACTED]', $log->old_values['password']);
        $this->assertEquals('[REDACTED]', $log->new_values['face_embedding']);
        $this->assertEquals(6500000, $log->new_values['basic_salary']);
    }
}

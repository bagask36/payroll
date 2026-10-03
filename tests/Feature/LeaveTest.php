<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LeaveTest extends TestCase
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
        ]);
    }

    public function test_it_seeds_standard_hospital_leave_types(): void
    {
        $this->assertDatabaseHas('leave_types', ['code' => 'ANNUAL', 'deducts_balance' => true]);
        $this->assertDatabaseHas('leave_types', ['code' => 'SICK', 'deducts_balance' => false]);
        $this->assertDatabaseHas('leave_types', ['code' => 'MATERNITY', 'default_days_per_year' => 90]);
    }

    public function test_leave_balance_deductions_and_restorations(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $annual = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $balance = LeaveBalance::where('employee_id', $nurse->id)
            ->where('leave_type_id', $annual->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertEquals(12.0, $balance->remaining_days);
        $this->assertEquals(0.0, $balance->used_days);

        // Deduct 3 days
        $balance->deduct(3.0);
        $this->assertEquals(9.0, $balance->remaining_days);
        $this->assertEquals(3.0, $balance->used_days);

        // Restore 3 days
        $balance->restore(3.0);
        $this->assertEquals(12.0, $balance->remaining_days);
        $this->assertEquals(0.0, $balance->used_days);

        // Exceeding balance throws DomainException
        $this->expectException(DomainException::class);
        $balance->deduct(15.0);
    }

    public function test_leave_approval_workflow_tier1_and_tier2(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $supervisor = User::where('email', 'icu.head@juwitahospital.com')->firstOrFail();
        $hrAdmin = User::where('email', 'hr.officer@juwitahospital.com')->firstOrFail();

        $request = LeaveRequest::where('employee_id', $nurse->id)->firstOrFail();
        $this->assertEquals('pending_tier1', $request->status);

        // Tier 1 approval by Supervisor
        $request->approveTier1($supervisor, 'Disetujui, jadwal pengganti perawat siap');
        $request->refresh();
        $this->assertEquals('pending_tier2', $request->status);
        $this->assertCount(1, $request->approvals);

        // Tier 2 final approval by HR Manager
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        $request->approveTier2($hrManager, 'Disetujui oleh Bagian SDM');
        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertCount(2, $request->approvals);

        // Balance should be automatically deducted by 3.0 days
        $annual = LeaveType::where('code', 'ANNUAL')->firstOrFail();
        $balance = LeaveBalance::where('employee_id', $nurse->id)
            ->where('leave_type_id', $annual->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertEquals(9.0, $balance->remaining_days);
        $this->assertEquals(3.0, $balance->used_days);
    }

    public function test_self_approval_prevention(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;
        $request = LeaveRequest::where('employee_id', $nurse->id)->firstOrFail();

        // Nurse cannot approve their own request
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Self-approval violation');
        $request->approveTier1($nurseUser);
    }

    public function test_leave_request_policy_authorization(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;
        $request = LeaveRequest::where('employee_id', $nurse->id)->firstOrFail();

        $supervisor = User::where('email', 'icu.head@juwitahospital.com')->firstOrFail();
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        // 1. Applicant can view their OWN request
        $this->assertTrue(Gate::forUser($nurseUser)->allows('view', $request));

        // 2. Applicant CANNOT approve their own request via Gate
        $this->assertFalse(Gate::forUser($nurseUser)->allows('approveTier1', $request));
        $this->assertFalse(Gate::forUser($nurseUser)->allows('approveTier2', $request));

        // 3. Supervisor can approve Tier 1
        $this->assertTrue(Gate::forUser($supervisor)->allows('approveTier1', $request));
        $this->assertFalse(Gate::forUser($supervisor)->allows('approveTier2', $request));

        // 4. HR Manager can approve Tier 2
        $this->assertTrue(Gate::forUser($hrManager)->allows('approveTier2', $request));
    }
}

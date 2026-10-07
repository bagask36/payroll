<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\LeaveTier1ApprovalTable;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveHierarchicalApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $icuHeadUser;
    private Employee $icuHeadEmployee;
    private Position $icuHeadPosition;

    private User $igdHeadUser;
    private Employee $igdHeadEmployee;
    private Position $igdHeadPosition;

    private User $icuNurseUser;
    private Employee $icuNurseEmployee;
    private Position $icuNursePosition;

    private User $igdNurseUser;
    private Employee $igdNurseEmployee;
    private Position $igdNursePosition;

    private User $hrManagerUser;
    private LeaveType $annualLeaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            LeaveSeeder::class,
        ]);

        $this->annualLeaveType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        // 1. Department & Units
        $dept = Department::create(['code' => 'YANMED_TEST', 'name' => 'Pelayanan Medik Test', 'is_active' => true]);
        $icuUnit = Unit::create(['department_id' => $dept->id, 'code' => 'ICU_TEST', 'name' => 'ICU Test', 'is_active' => true]);
        $igdUnit = Unit::create(['department_id' => $dept->id, 'code' => 'IGD_TEST', 'name' => 'IGD Test', 'is_active' => true]);

        // 2. Positions with Hierarchy
        $this->icuHeadPosition = Position::create([
            'unit_id' => $icuUnit->id,
            'title' => 'Kepala Ruang ICU Test',
            'grade_level' => 'supervisor',
            'reports_to_id' => null,
        ]);

        $this->icuNursePosition = Position::create([
            'unit_id' => $icuUnit->id,
            'title' => 'Perawat ICU Test',
            'grade_level' => 'staff',
            'reports_to_id' => $this->icuHeadPosition->id,
        ]);

        $this->igdHeadPosition = Position::create([
            'unit_id' => $igdUnit->id,
            'title' => 'Kepala IGD Test',
            'grade_level' => 'supervisor',
            'reports_to_id' => null,
        ]);

        $this->igdNursePosition = Position::create([
            'unit_id' => $igdUnit->id,
            'title' => 'Perawat IGD Test',
            'grade_level' => 'staff',
            'reports_to_id' => $this->igdHeadPosition->id,
        ]);

        // 3. Users and Employees
        // ICU Head
        $this->icuHeadUser = User::factory()->create(['name' => 'ICU Head User', 'is_active' => true]);
        $this->icuHeadUser->assignRole('supervisor');
        $this->icuHeadEmployee = Employee::factory()->create([
            'user_id' => $this->icuHeadUser->id,
            'unit_id' => $icuUnit->id,
            'position_id' => $this->icuHeadPosition->id,
            'full_name' => 'Ratna ICU Head',
            'status' => 'active',
        ]);

        // IGD Head
        $this->igdHeadUser = User::factory()->create(['name' => 'IGD Head User', 'is_active' => true]);
        $this->igdHeadUser->assignRole('supervisor');
        $this->igdHeadEmployee = Employee::factory()->create([
            'user_id' => $this->igdHeadUser->id,
            'unit_id' => $igdUnit->id,
            'position_id' => $this->igdHeadPosition->id,
            'full_name' => 'Bambang IGD Head',
            'status' => 'active',
        ]);

        // ICU Nurse (Subordinate of ICU Head)
        $this->icuNurseUser = User::factory()->create(['name' => 'ICU Nurse User', 'is_active' => true]);
        $this->icuNurseUser->assignRole('employee');
        $this->icuNurseEmployee = Employee::factory()->create([
            'user_id' => $this->icuNurseUser->id,
            'unit_id' => $icuUnit->id,
            'position_id' => $this->icuNursePosition->id,
            'full_name' => 'Siti Perawat ICU',
            'status' => 'active',
        ]);

        // IGD Nurse (Subordinate of IGD Head)
        $this->igdNurseUser = User::factory()->create(['name' => 'IGD Nurse User', 'is_active' => true]);
        $this->igdNurseUser->assignRole('employee');
        $this->igdNurseEmployee = Employee::factory()->create([
            'user_id' => $this->igdNurseUser->id,
            'unit_id' => $igdUnit->id,
            'position_id' => $this->igdNursePosition->id,
            'full_name' => 'Agus Perawat IGD',
            'status' => 'active',
        ]);

        // HR Manager
        $this->hrManagerUser = User::factory()->create(['name' => 'HR Manager User', 'is_active' => true]);
        $this->hrManagerUser->assignRole('hr_manager');

        // Leave Balances
        LeaveBalance::create([
            'employee_id' => $this->icuNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'year' => 2026,
            'assigned_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);
    }

    public function test_position_hierarchy_relationships(): void
    {
        $this->assertEquals($this->icuHeadPosition->id, $this->icuNursePosition->supervisorPosition->id);
        $this->assertTrue($this->icuHeadPosition->subordinatePositions->contains($this->icuNursePosition));
    }

    public function test_employee_supervisor_detection(): void
    {
        // ICU Head is supervisor of ICU Nurse
        $this->assertTrue($this->icuHeadEmployee->isSupervisorOf($this->icuNurseEmployee));
        $this->assertTrue($this->icuNurseEmployee->getDirectSupervisors()->contains($this->icuHeadEmployee));

        // ICU Head is NOT supervisor of IGD Nurse
        $this->assertFalse($this->icuHeadEmployee->isSupervisorOf($this->igdNurseEmployee));

        // IGD Head is supervisor of IGD Nurse
        $this->assertTrue($this->igdHeadEmployee->isSupervisorOf($this->igdNurseEmployee));
        $this->assertFalse($this->igdHeadEmployee->isSupervisorOf($this->icuNurseEmployee));

        // Subordinates collection
        $this->assertTrue($this->icuHeadEmployee->getSubordinates()->contains($this->icuNurseEmployee));
        $this->assertFalse($this->icuHeadEmployee->getSubordinates()->contains($this->igdNurseEmployee));
    }

    public function test_leave_tier1_approval_policy_enforces_organizational_hierarchy(): void
    {
        $icuLeave = LeaveRequest::factory()->create([
            'employee_id' => $this->icuNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => Carbon::parse('2026-06-01'),
            'end_date' => Carbon::parse('2026-06-03'),
            'total_days' => 3.0,
            'reason' => 'Cuti liburan',
            'status' => 'pending_tier1',
            'created_by' => $this->icuNurseUser->id,
        ]);

        // 1. Direct supervisor (ICU Head) can approve Tier 1
        $this->assertTrue(Gate::forUser($this->icuHeadUser)->allows('approveTier1', $icuLeave));

        // 2. Non-direct supervisor (IGD Head) CANNOT approve Tier 1 for ICU Nurse
        $this->assertFalse(Gate::forUser($this->igdHeadUser)->allows('approveTier1', $icuLeave));

        // 3. Applicant cannot approve own request (Anti-self-approval)
        $this->assertFalse(Gate::forUser($this->icuNurseUser)->allows('approveTier1', $icuLeave));

        // 4. Super Admin and HR Manager have hospital-wide override authority
        $this->assertTrue(Gate::forUser($this->hrManagerUser)->allows('approveTier1', $icuLeave));

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->assertTrue(Gate::forUser($admin)->allows('approveTier1', $icuLeave));
    }

    public function test_full_hierarchical_leave_approval_workflow_to_hr_finalization(): void
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->icuNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => Carbon::parse('2026-06-10'),
            'end_date' => Carbon::parse('2026-06-12'),
            'total_days' => 3.0,
            'reason' => 'Cuti keperluan keluarga',
            'status' => 'pending_tier1',
            'created_by' => $this->icuNurseUser->id,
        ]);

        // Step 1: Direct supervisor approves Tier 1
        $leave->approveTier1($this->icuHeadUser, 'Disetujui atasan langsung, jadwal perawat aman');
        $leave->refresh();

        $this->assertEquals('pending_tier2', $leave->status);
        $this->assertCount(1, $leave->approvals);
        $this->assertEquals(1, $leave->approvals->first()->tier);
        $this->assertEquals('approved', $leave->approvals->first()->status);
        $this->assertEquals($this->icuHeadUser->id, $leave->approvals->first()->approver_id);

        // Step 2: Direct supervisor CANNOT approve Tier 2 (only HR Manager can)
        $this->assertFalse(Gate::forUser($this->icuHeadUser)->allows('approveTier2', $leave));
        $this->assertTrue(Gate::forUser($this->hrManagerUser)->allows('approveTier2', $leave));

        // Step 3: HR Manager approves Tier 2 (Final sign-off)
        $leave->approveTier2($this->hrManagerUser, 'Diverifikasi HRD, hak cuti memenuhi syarat');
        $leave->refresh();

        $this->assertEquals('approved', $leave->status);
        $this->assertCount(2, $leave->approvals);
        $this->assertEquals(2, $leave->approvals->last()->tier);
        $this->assertEquals('approved', $leave->approvals->last()->status);
        $this->assertEquals($this->hrManagerUser->id, $leave->approvals->last()->approver_id);

        // Balance must be decremented
        $balance = LeaveBalance::where('employee_id', $this->icuNurseEmployee->id)
            ->where('leave_type_id', $this->annualLeaveType->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertEquals(9.0, $balance->remaining_days);
        $this->assertEquals(3.0, $balance->used_days);
    }

    public function test_leave_tier1_approval_table_filters_by_subordinates(): void
    {
        // ICU Leave Request
        $icuLeave = LeaveRequest::factory()->create([
            'employee_id' => $this->icuNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => Carbon::parse('2026-07-01'),
            'end_date' => Carbon::parse('2026-07-02'),
            'total_days' => 2.0,
            'reason' => 'Izin ICU',
            'status' => 'pending_tier1',
            'created_by' => $this->icuNurseUser->id,
        ]);

        // IGD Leave Request
        $igdLeave = LeaveRequest::factory()->create([
            'employee_id' => $this->igdNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => Carbon::parse('2026-07-05'),
            'end_date' => Carbon::parse('2026-07-06'),
            'total_days' => 2.0,
            'reason' => 'Izin IGD',
            'status' => 'pending_tier1',
            'created_by' => $this->igdNurseUser->id,
        ]);

        // When ICU Head views the Tier 1 table:
        $this->actingAs($this->icuHeadUser);
        Livewire::test(LeaveTier1ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Siti Perawat ICU')
            ->assertSee('Izin ICU')
            ->assertDontSee('Agus Perawat IGD')
            ->assertDontSee('Izin IGD');

        // When IGD Head views the Tier 1 table:
        $this->actingAs($this->igdHeadUser);
        Livewire::test(LeaveTier1ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Agus Perawat IGD')
            ->assertSee('Izin IGD')
            ->assertDontSee('Siti Perawat ICU')
            ->assertDontSee('Izin ICU');
    }

    public function test_leave_approval_table_keeps_approved_requests_visible(): void
    {
        // 1. Create an approved leave request
        $approvedLeave = LeaveRequest::factory()->create([
            'employee_id' => $this->icuNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => Carbon::parse('2026-08-01'),
            'end_date' => Carbon::parse('2026-08-03'),
            'total_days' => 3.0,
            'reason' => 'Cuti Sudah Disetujui Final',
            'status' => 'approved',
            'created_by' => $this->icuNurseUser->id,
        ]);

        // Create approval record
        $approvedLeave->approvals()->create([
            'approver_id' => $this->icuHeadUser->id,
            'tier' => 1,
            'status' => 'approved',
            'action_at' => Carbon::now(),
            'notes' => 'Disetujui atasan',
        ]);

        // When ICU Head (Supervisor) views LeaveApprovalTable:
        $this->actingAs($this->icuHeadUser);

        Livewire::test(\App\Livewire\Tables\LeaveApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Siti Perawat ICU')
            ->assertSee('Cuti Sudah Disetujui Final')
            ->assertSee('Disetujui');

        $table = new \App\Livewire\Tables\LeaveApprovalTable();
        $actions = $table->actions($approvedLeave);

        // Actions for approved request is empty because finalized requests have no action buttons to keep the table clean
        $this->assertCount(0, $actions);
    }

    public function test_leave_index_handles_hierarchical_approval_dispatch(): void
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->icuNurseEmployee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => Carbon::parse('2026-08-10'),
            'end_date' => Carbon::parse('2026-08-11'),
            'total_days' => 2.0,
            'reason' => 'Pengajuan untuk uji hierarkis',
            'status' => 'pending_tier1',
            'created_by' => $this->icuNurseUser->id,
        ]);

        $this->actingAs($this->icuHeadUser);

        Livewire::test(\App\Livewire\Leave\LeaveIndex::class)
            ->set('activeTab', 'approvals')
            ->assertStatus(200)
            ->assertSee('Persetujuan Cuti')
            ->call('approveHierarchical', $leave->id)
            ->assertSee('Persetujuan berhasil diberikan');

        $leave->refresh();
        $this->assertCount(1, $leave->approvals);
        $this->assertEquals('approved', $leave->approvals->first()->status);
        $this->assertEquals($this->icuHeadUser->id, $leave->approvals->first()->approver_id);
    }
}

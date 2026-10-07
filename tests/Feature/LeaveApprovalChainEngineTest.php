<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Services\Leave\LeaveApprovalChainService;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaveApprovalChainEngineTest extends TestCase
{
    use RefreshDatabase;

    private LeaveApprovalChainService $engine;
    private LeaveType $annualLeave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            LeaveSeeder::class,
        ]);

        $this->engine = app(LeaveApprovalChainService::class);
        $this->annualLeave = LeaveType::where('code', 'ANNUAL')->firstOrFail();
    }

    public function test_it_generates_three_step_chain_for_it_staff(): void
    {
        // 1. Setup Director, Kabag Umum, PJ IT, Staff IT
        $dept = Department::create(['code' => 'UM-SDM-KEU', 'name' => 'Umum, SDM & Keu', 'is_active' => true]);
        $itUnit = Unit::create(['department_id' => $dept->id, 'code' => 'IT', 'name' => 'Unit SIMRS & TI', 'is_active' => true]);

        $dirPos = Position::create(['title' => 'Direktur Rumah Sakit Juwita', 'grade_level' => 'director']);
        $kabagPos = Position::create(['title' => 'Kepala Bagian Umum, SDM dan Keuangan', 'grade_level' => 'manager', 'reports_to_id' => $dirPos->id]);
        $pjItPos = Position::create(['unit_id' => $itUnit->id, 'title' => 'Penanggung Jawab (PJ) Unit IT & SIMRS', 'grade_level' => 'supervisor', 'reports_to_id' => $kabagPos->id]);
        $staffItPos = Position::create(['unit_id' => $itUnit->id, 'title' => 'Staff Programmer SIMRS', 'grade_level' => 'staff', 'reports_to_id' => $pjItPos->id]);

        // Staff IT employee
        $staffUser = User::factory()->create();
        $staffUser->assignRole('employee');
        $staffEmp = Employee::factory()->create([
            'user_id' => $staffUser->id,
            'unit_id' => $itUnit->id,
            'position_id' => $staffItPos->id,
            'full_name' => 'Ahmad Programmer',
            'status' => 'active',
        ]);

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $staffEmp->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => Carbon::parse('2026-08-01'),
            'end_date' => Carbon::parse('2026-08-03'),
            'total_days' => 3.0,
            'reason' => 'Cuti Tahunan Staf IT',
            'status' => 'pending_tier1',
            'created_by' => $staffUser->id,
        ]);

        $chain = $this->engine->buildChain($leave);

        // Expect 3 stages:
        // Stage 1: PJ Unit IT & SIMRS
        // Stage 2: Kepala Bagian Umum, SDM dan Keuangan
        // Stage 3: HR / Bagian SDM (Final)
        $this->assertCount(3, $chain);

        $this->assertEquals(1, $chain[0]['tier']);
        $this->assertEquals($pjItPos->id, $chain[0]['position_id']);
        $this->assertStringContainsString('(PJ) Unit IT', $chain[0]['title']);
        $this->assertFalse($chain[0]['is_hr_final']);

        $this->assertEquals(2, $chain[1]['tier']);
        $this->assertEquals($kabagPos->id, $chain[1]['position_id']);
        $this->assertStringContainsString('Kepala Bagian Umum', $chain[1]['title']);
        $this->assertFalse($chain[1]['is_hr_final']);

        $this->assertEquals(3, $chain[2]['tier']);
        $this->assertNull($chain[2]['position_id']);
        $this->assertStringContainsString('HR / Bagian SDM', $chain[2]['title']);
        $this->assertTrue($chain[2]['is_hr_final']);
    }

    public function test_it_generates_two_step_chain_for_kabag_umum(): void
    {
        // Kabag Umum -> Direktur -> HR
        $dept = Department::create(['code' => 'UM-SDM-KEU', 'name' => 'Umum, SDM & Keu', 'is_active' => true]);
        $dirPos = Position::create(['title' => 'Direktur Rumah Sakit Juwita', 'grade_level' => 'director']);
        $kabagPos = Position::create(['title' => 'Kepala Bagian Umum, SDM dan Keuangan', 'grade_level' => 'manager', 'reports_to_id' => $dirPos->id]);

        $kabagUser = User::factory()->create();
        $kabagUser->assignRole('supervisor');
        $kabagEmp = Employee::factory()->create([
            'user_id' => $kabagUser->id,
            'position_id' => $kabagPos->id,
            'full_name' => 'Pak Kabag Umum',
            'status' => 'active',
        ]);

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $kabagEmp->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => Carbon::parse('2026-09-01'),
            'end_date' => Carbon::parse('2026-09-02'),
            'total_days' => 2.0,
            'reason' => 'Cuti Kabag',
            'status' => 'pending_tier1',
            'created_by' => $kabagUser->id,
        ]);

        $chain = $this->engine->buildChain($leave);

        // Expect 2 stages:
        // Stage 1: Direktur RS Permata Bunda
        // Stage 2: HR / Bagian SDM (Final)
        $this->assertCount(2, $chain);

        $this->assertEquals(1, $chain[0]['tier']);
        $this->assertEquals($dirPos->id, $chain[0]['position_id']);
        $this->assertStringContainsString('Direktur', $chain[0]['title']);
        $this->assertFalse($chain[0]['is_hr_final']);

        $this->assertEquals(2, $chain[1]['tier']);
        $this->assertTrue($chain[1]['is_hr_final']);
    }

    public function test_full_chain_advancement_and_final_quota_deduction(): void
    {
        $dept = Department::create(['code' => 'UM-SDM-KEU', 'name' => 'Umum, SDM & Keu', 'is_active' => true]);
        $itUnit = Unit::create(['department_id' => $dept->id, 'code' => 'IT', 'name' => 'Unit IT', 'is_active' => true]);

        $dirPos = Position::create(['title' => 'Direktur Rumah Sakit Juwita', 'grade_level' => 'director']);
        $kabagPos = Position::create(['title' => 'Kepala Bagian Umum, SDM dan Keuangan', 'grade_level' => 'manager', 'reports_to_id' => $dirPos->id]);
        $pjItPos = Position::create(['unit_id' => $itUnit->id, 'title' => 'Penanggung Jawab (PJ) Unit IT & SIMRS', 'grade_level' => 'supervisor', 'reports_to_id' => $kabagPos->id]);
        $staffItPos = Position::create(['unit_id' => $itUnit->id, 'title' => 'Staff Programmer SIMRS', 'grade_level' => 'staff', 'reports_to_id' => $pjItPos->id]);

        // Users
        $pjUser = User::factory()->create();
        $pjUser->assignRole('supervisor');
        $pjEmp = Employee::factory()->create(['user_id' => $pjUser->id, 'position_id' => $pjItPos->id, 'status' => 'active']);

        $kabagUser = User::factory()->create();
        $kabagUser->assignRole('supervisor');
        $kabagEmp = Employee::factory()->create(['user_id' => $kabagUser->id, 'position_id' => $kabagPos->id, 'status' => 'active']);

        $hrUser = User::factory()->create();
        $hrUser->assignRole('hr_manager');

        $staffUser = User::factory()->create();
        $staffUser->assignRole('employee');
        $staffEmp = Employee::factory()->create(['user_id' => $staffUser->id, 'position_id' => $staffItPos->id, 'status' => 'active']);

        LeaveBalance::create([
            'employee_id' => $staffEmp->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'assigned_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $staffEmp->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => Carbon::parse('2026-10-10'),
            'end_date' => Carbon::parse('2026-10-12'),
            'total_days' => 3.0,
            'reason' => 'Libur tahunan',
            'status' => 'pending_tier1',
            'created_by' => $staffUser->id,
        ]);

        // Stage 1: PJ IT approves
        $this->assertTrue($this->engine->canUserApprove($pjUser, $leave));
        $this->assertFalse($this->engine->canUserApprove($kabagUser, $leave)); // not active stage yet
        $this->assertFalse($this->engine->canUserApprove($staffUser, $leave)); // anti-self-approval

        $this->engine->approveStage($pjUser, $leave, 'PJ approve');
        $leave->refresh();
        $this->assertEquals(1, $leave->approvals()->count());
        $this->assertNotEquals('approved', $leave->status);

        // Stage 2: Kabag Umum approves
        $this->assertTrue($this->engine->canUserApprove($kabagUser, $leave));
        $this->engine->approveStage($kabagUser, $leave, 'Kabag approve');
        $leave->refresh();
        $this->assertEquals(2, $leave->approvals()->count());
        $this->assertNotEquals('approved', $leave->status);

        // Stage 3: HR Manager approves (Final)
        $this->assertTrue($this->engine->canUserApprove($hrUser, $leave));
        $this->engine->approveStage($hrUser, $leave, 'HR final sign-off');
        $leave->refresh();

        $this->assertEquals(3, $leave->approvals()->count());
        $this->assertEquals('approved', $leave->status);

        // Check balance deducted
        $balance = LeaveBalance::where('employee_id', $staffEmp->id)
            ->where('leave_type_id', $this->annualLeave->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertEquals(9.0, $balance->remaining_days);
        $this->assertEquals(3.0, $balance->used_days);
    }
}

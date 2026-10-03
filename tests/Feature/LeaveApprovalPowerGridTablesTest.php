<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Leave\LeaveIndex;
use App\Livewire\Tables\LeaveTier1ApprovalTable;
use App\Livewire\Tables\LeaveTier2ApprovalTable;
use App\Models\Employee;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveApprovalPowerGridTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            LeaveSeeder::class,
        ]);
    }

    public function test_tier1_approval_table_renders_pending_tier1_requests_for_supervisor(): void
    {
        $employeeUser = User::factory()->create(['is_active' => true]);
        $employeeUser->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'full_name' => 'Budi Santoso',
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 3.0,
            'reason' => 'Keperluan mendesak keluarga',
            'status' => 'pending_tier1',
            'created_by' => $employeeUser->id,
        ]);

        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(LeaveTier1ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Budi Santoso')
            ->assertSee('Keperluan mendesak keluarga')
            ->assertSee('3 Hari');

        $table = new LeaveTier1ApprovalTable();
        $actions = $table->actions($leaveRequest);
        $this->assertCount(2, $actions);
        $this->assertEquals('Setujui (T1)', $actions[0]->slot);
        $this->assertEquals('Tolak', $actions[1]->slot);
    }

    public function test_tier1_approval_table_anti_self_approval_filter(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $employee = Employee::factory()->create([
            'user_id' => $supervisor->id,
            'full_name' => 'Supervisor Diri Sendiri',
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'total_days' => 2.0,
            'reason' => 'Cuti pribadi supervisor',
            'status' => 'pending_tier1',
            'created_by' => $supervisor->id,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(LeaveTier1ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Cuti pribadi supervisor');

        $table = new LeaveTier1ApprovalTable();
        $actions = $table->actions($leaveRequest);
        $this->assertCount(1, $actions);
        $this->assertEquals('Pengajuan Sendiri', $actions[0]->slot);
    }

    public function test_tier1_approval_table_super_admin_can_approve_any(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super_admin');

        $employee = Employee::factory()->create([
            'user_id' => $admin->id,
            'full_name' => 'Admin Pemohon',
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'total_days' => 2.0,
            'reason' => 'Cuti admin test',
            'status' => 'pending_tier1',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(LeaveTier1ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Cuti admin test');

        $table = new LeaveTier1ApprovalTable();
        $actions = $table->actions($leaveRequest);
        $this->assertCount(2, $actions);
        $this->assertEquals('Setujui (T1)', $actions[0]->slot);
    }

    public function test_tier1_approval_table_empty_for_unauthorized_user(): void
    {
        $regularUser = User::factory()->create(['is_active' => true]);
        $regularUser->assignRole('employee');

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        LeaveRequest::factory()->create([
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'total_days' => 2.0,
            'reason' => 'Rahasia permohonan staf lain',
            'status' => 'pending_tier1',
        ]);

        $this->actingAs($regularUser);

        Livewire::test(LeaveTier1ApprovalTable::class)
            ->assertStatus(200)
            ->assertDontSee('Rahasia permohonan staf lain');
    }

    public function test_tier2_approval_table_renders_pending_tier2_requests_for_hr_manager(): void
    {
        $employeeUser = User::factory()->create(['is_active' => true]);
        $employeeUser->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'full_name' => 'Siti Nurhaliza',
            'status' => 'active',
        ]);

        $supervisor = User::factory()->create(['name' => 'Pak Supervisor', 'is_active' => true]);
        $supervisor->assignRole('supervisor');

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(5),
            'end_date' => Carbon::today()->addDays(7),
            'total_days' => 3.0,
            'reason' => 'Istirahat tahunan cuti bersama',
            'status' => 'pending_tier2',
            'created_by' => $employeeUser->id,
        ]);

        LeaveApproval::create([
            'leave_request_id' => $leaveRequest->id,
            'tier' => 1,
            'approver_id' => $supervisor->id,
            'status' => 'approved',
            'action_at' => Carbon::now(),
            'notes' => 'Disetujui tingkat unit',
        ]);

        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager');

        $this->actingAs($hrManager);

        Livewire::test(LeaveTier2ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Istirahat tahunan cuti bersama')
            ->assertSee('3 Hari')
            ->assertSee('Pak Supervisor');

        $table = new LeaveTier2ApprovalTable();
        $actions = $table->actions($leaveRequest);
        $this->assertCount(2, $actions);
        $this->assertEquals('Setujui Final (T2)', $actions[0]->slot);
        $this->assertEquals('Tolak', $actions[1]->slot);
    }

    public function test_tier2_approval_table_anti_self_approval_filter(): void
    {
        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager');

        $employee = Employee::factory()->create([
            'user_id' => $hrManager->id,
            'full_name' => 'HR Manager Sendiri',
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(3),
            'total_days' => 2.0,
            'reason' => 'Cuti pribadi HR Manager',
            'status' => 'pending_tier2',
            'created_by' => $hrManager->id,
        ]);

        $this->actingAs($hrManager);

        Livewire::test(LeaveTier2ApprovalTable::class)
            ->assertStatus(200)
            ->assertSee('Cuti pribadi HR Manager');

        $table = new LeaveTier2ApprovalTable();
        $actions = $table->actions($leaveRequest);
        $this->assertCount(1, $actions);
        $this->assertEquals('Pengajuan Sendiri', $actions[0]->slot);
    }

    public function test_leave_index_responds_to_powergrid_dispatched_actions(): void
    {
        $employeeUser = User::factory()->create(['is_active' => true]);
        $employeeUser->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $balance = LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'year' => (int) Carbon::today()->format('Y'),
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(3),
            'end_date' => Carbon::today()->addDays(4),
            'total_days' => 2.0,
            'status' => 'pending_tier1',
            'created_by' => $employeeUser->id,
        ]);

        // Supervisor approves tier 1 via event
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(LeaveIndex::class)
            ->dispatch('approve-tier1', requestId: $leaveRequest->id)
            ->assertDispatched('pg:eventRefresh-leave_tier1_approval_table')
            ->assertDispatched('pg:eventRefresh-leave_tier2_approval_table');

        $leaveRequest->refresh();
        $this->assertEquals('pending_tier2', $leaveRequest->status);

        // HR Manager approves tier 2 via event
        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager');

        $this->actingAs($hrManager);

        Livewire::test(LeaveIndex::class)
            ->dispatch('approve-tier2', requestId: $leaveRequest->id)
            ->assertDispatched('pg:eventRefresh-leave_tier2_approval_table');

        $leaveRequest->refresh();
        $this->assertEquals('approved', $leaveRequest->status);

        $balance->refresh();
        $this->assertEquals(2.0, $balance->used_days);
    }
}

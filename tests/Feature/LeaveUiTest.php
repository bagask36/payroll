<?php

namespace Tests\Feature;

use App\Livewire\Leave\LeaveApply;
use App\Livewire\Leave\LeaveIndex;
use App\Models\Employee;
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

class LeaveUiTest extends TestCase
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

    public function test_unauthorized_user_cannot_access_leaves(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        Livewire::test(LeaveIndex::class)
            ->assertForbidden();

        Livewire::test(LeaveApply::class)
            ->assertForbidden();
    }

    public function test_employee_can_view_leave_index_and_balances(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Dewi Lestari',
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();
        $year = (int) Carbon::today()->format('Y');

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'year' => $year,
            'allocated_days' => 12.0,
            'used_days' => 2.0,
            'remaining_days' => 10.0,
        ]);

        $this->actingAs($user);

        Livewire::test(LeaveIndex::class)
            ->assertStatus(200)
            ->assertSee('Manajemen Cuti')
            ->assertSee('10') // remaining days
            ->assertSee('Hari Tersisa');
    }

    public function test_employee_can_submit_valid_leave_request(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();
        $year = (int) Carbon::today()->format('Y');

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'year' => $year,
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        $this->actingAs($user);

        $start = Carbon::today()->addDays(5)->toDateString();
        $end = Carbon::today()->addDays(6)->toDateString(); // 2 days

        Livewire::test(LeaveApply::class)
            ->set('leaveTypeId', $annualType->id)
            ->set('startDate', $start)
            ->set('endDate', $end)
            ->set('reason', 'Keperluan keluarga penting di luar kota')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'total_days' => 2.0,
            'status' => 'pending_tier1',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.submitted',
        ]);
    }

    public function test_leave_submission_rejected_when_exceeding_balance(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();
        $year = (int) Carbon::today()->format('Y');

        LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'year' => $year,
            'allocated_days' => 12.0,
            'used_days' => 11.0,
            'remaining_days' => 1.0, // Only 1 day left
        ]);

        $this->actingAs($user);

        $start = Carbon::today()->addDays(5)->toDateString();
        $end = Carbon::today()->addDays(9)->toDateString(); // 5 days

        Livewire::test(LeaveApply::class)
            ->set('leaveTypeId', $annualType->id)
            ->set('startDate', $start)
            ->set('endDate', $end)
            ->set('reason', 'Liburan panjang bersama keluarga')
            ->call('submit')
            ->assertSee('Saldo cuti tidak mencukupi');

        $this->assertDatabaseMissing('leave_requests', [
            'employee_id' => $employee->id,
        ]);
    }

    public function test_multi_tier_approval_and_balance_deduction(): void
    {
        // 1. Employee submits 3-day leave
        $employeeUser = User::factory()->create(['is_active' => true]);
        $employeeUser->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();
        $year = (int) Carbon::today()->format('Y');

        $balance = LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'year' => $year,
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(3),
            'end_date' => Carbon::today()->addDays(5),
            'total_days' => 3.0,
            'status' => 'pending_tier1',
            'created_by' => $employeeUser->id,
        ]);

        // 2. Supervisor approves Tier 1
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(LeaveIndex::class)
            ->set('activeTab', 'tier1_approvals')
            ->call('approveTier1', $leaveRequest->id)
            ->assertSee('Persetujuan Tier 1 berhasil');

        $leaveRequest->refresh();
        $this->assertEquals('pending_tier2', $leaveRequest->status);

        // 3. HR Manager approves Tier 2 (Final)
        $hrManager = User::factory()->create(['is_active' => true]);
        $hrManager->assignRole('hr_manager');

        $this->actingAs($hrManager);

        Livewire::test(LeaveIndex::class)
            ->set('activeTab', 'tier2_approvals')
            ->call('approveTier2', $leaveRequest->id)
            ->assertSee('Persetujuan Final Tier 2 berhasil');

        $leaveRequest->refresh();
        $this->assertEquals('approved', $leaveRequest->status);

        // 4. Verify balance deducted
        $balance->refresh();
        $this->assertEquals(3.0, $balance->used_days);
        $this->assertEquals(9.0, $balance->remaining_days);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.approved_tier2',
        ]);
    }

    public function test_anti_self_approval_is_prevented(): void
    {
        // Supervisor submits a leave request for herself
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $employee = Employee::factory()->create([
            'user_id' => $supervisor->id,
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'total_days' => 1.0,
            'status' => 'pending_tier1',
            'created_by' => $supervisor->id,
        ]);

        $this->actingAs($supervisor);

        // Supervisor attempts to approve her own request
        Livewire::test(LeaveIndex::class)
            ->call('approveTier1', $leaveRequest->id)
            ->assertForbidden();

        $leaveRequest->refresh();
        $this->assertEquals('pending_tier1', $leaveRequest->status);
    }
}

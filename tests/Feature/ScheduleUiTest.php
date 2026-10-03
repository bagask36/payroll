<?php

namespace Tests\Feature;

use App\Livewire\Schedule\ScheduleRoster;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthorized_user_cannot_access_schedules(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        Livewire::test(ScheduleRoster::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_weekly_schedule_roster(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee'); // Has schedule.view

        $shift = Shift::factory()->create(['name' => 'Shift Pagi IGD', 'start_time' => '07:00:00']);

        $employee = Employee::factory()->create([
            'full_name' => 'Ns. Ratna Dewi',
            'status' => 'active',
        ]);

        $today = Carbon::today();
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => $today->toDateString(),
            'status' => 'scheduled',
        ]);

        $this->actingAs($user);

        Livewire::test(ScheduleRoster::class)
            ->assertStatus(200)
            ->assertSee('Roster')
            ->assertSee('Jadwal Dinas')
            ->assertSee('Ns. Ratna Dewi')
            ->assertSee('Shift Pagi IGD')
            ->call('nextWeek')
            ->assertDontSee('Ns. Ratna Dewi') // Moved outside of that week
            ->call('currentWeek')
            ->assertSee('Ns. Ratna Dewi');
    }

    public function test_supervisor_can_assign_shift_to_employee(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor'); // Has schedule.assign_team

        $shift = Shift::factory()->create(['name' => 'Shift Malam ICU']);
        $employee = Employee::factory()->create(['full_name' => 'Perawat Doni', 'status' => 'active']);
        $targetDate = Carbon::today()->addDays(2)->toDateString();

        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'employee')
            ->set('selectedEmployeeId', $employee->id)
            ->set('selectedShiftId', $shift->id)
            ->set('scheduleDate', $targetDate)
            ->set('notes', 'Penjagaan pasien kritis ICU')
            ->call('assignShift')
            ->assertHasNoErrors()
            ->assertSee('berhasil diterbitkan');

        $assignment = ScheduleAssignment::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals($shift->id, $assignment->shift_id);
        $this->assertEquals('scheduled', $assignment->status);
        $this->assertEquals($targetDate, $assignment->schedule_date->toDateString());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'schedule.assigned',
        ]);
    }

    public function test_duplicate_shift_assignment_on_same_date_is_rejected(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift1 = Shift::factory()->create(['name' => 'Shift Pagi']);
        $shift2 = Shift::factory()->create(['name' => 'Shift Siang']);
        $employee = Employee::factory()->create(['status' => 'active']);
        $targetDate = Carbon::today()->toDateString();

        // Existing assignment
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift1->id,
            'schedule_date' => $targetDate,
            'status' => 'scheduled',
        ]);

        $this->actingAs($supervisor);

        // Attempting to assign second shift on same date
        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'employee')
            ->set('selectedEmployeeId', $employee->id)
            ->set('selectedShiftId', $shift2->id)
            ->set('scheduleDate', $targetDate)
            ->call('assignShift')
            ->assertSee('Terdapat bentrok jadwal');

        $this->assertDatabaseMissing('schedule_assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shift2->id,
        ]);
    }

    public function test_supervisor_can_cancel_schedule_assignment(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift = Shift::factory()->create();
        $employee = Employee::factory()->create(['status' => 'active']);

        $assignment = ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
            'status' => 'scheduled',
        ]);

        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('cancelAssignment', $assignment->id)
            ->assertSee('berhasil dibatalkan');

        $assignment->refresh();
        $this->assertEquals('cancelled', $assignment->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'schedule.cancelled',
        ]);
    }
}

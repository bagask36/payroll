<?php

namespace Tests\Feature;

use App\Livewire\Schedule\ScheduleRoster;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleMultiAssignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_can_assign_shift_to_multiple_employees_across_multiple_dates_atomically(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift = Shift::factory()->create(['name' => 'Shift Pagi UGD']);
        $employee1 = Employee::factory()->create(['full_name' => 'Perawat Alpha', 'status' => 'active']);
        $employee2 = Employee::factory()->create(['full_name' => 'Perawat Beta', 'status' => 'active']);

        $date1 = Carbon::today()->addDays(1)->toDateString();
        $date2 = Carbon::today()->addDays(2)->toDateString();
        $date3 = Carbon::today()->addDays(3)->toDateString();

        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'employee')
            ->set('selectedEmployeeIds', [$employee1->id, $employee2->id])
            ->set('selectedShiftId', $shift->id)
            ->set('dateSelectionMode', 'multiple')
            ->set('scheduleDates', [$date1, $date2, $date3])
            ->set('notes', 'Penugasan Posko')
            ->call('assignShift')
            ->assertHasNoErrors()
            ->assertSee('penugasan shift berhasil diterbitkan');

        // Verify that exactly 6 distinct rows were created in schedule_assignments
        $this->assertEquals(6, ScheduleAssignment::count());

        foreach ([$employee1->id, $employee2->id] as $empId) {
            foreach ([$date1, $date2, $date3] as $d) {
                $assignment = ScheduleAssignment::where('employee_id', $empId)
                    ->whereDate('schedule_date', $d)
                    ->first();

                $this->assertNotNull($assignment);
                $this->assertEquals($shift->id, $assignment->shift_id);
                $this->assertNull($assignment->doctor_id);
                $this->assertEquals('scheduled', $assignment->status);
                $this->assertEquals('Penugasan Posko', $assignment->notes);
                $this->assertEquals($supervisor->id, $assignment->created_by);
            }
        }

        // Verify audit log has the multi-assignment detail
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'schedule.assigned',
        ]);
    }

    public function test_can_assign_shift_using_date_range_with_day_of_week_filter(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift = Shift::factory()->create(['name' => 'Shift Siang']);
        $employee = Employee::factory()->create(['full_name' => 'Budi Santoso', 'status' => 'active']);

        // Pick next Monday as start date and next Sunday as end date (7 days)
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);

        // Only assign on Monday (1) and Wednesday (3)
        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'employee')
            ->set('selectedEmployeeIds', [$employee->id])
            ->set('selectedShiftId', $shift->id)
            ->set('dateSelectionMode', 'range')
            ->set('rangeStartDate', $monday->toDateString())
            ->set('rangeEndDate', $sunday->toDateString())
            ->set('rangeDaysOfWeek', [1, 3])
            ->call('assignShift')
            ->assertHasNoErrors()
            ->assertSee('Sebanyak 2 penugasan shift berhasil diterbitkan');

        $this->assertEquals(2, ScheduleAssignment::where('employee_id', $employee->id)->count());

        $this->assertTrue(
            ScheduleAssignment::where('employee_id', $employee->id)
                ->whereDate('schedule_date', $monday->toDateString())
                ->where('shift_id', $shift->id)
                ->exists()
        );

        $this->assertTrue(
            ScheduleAssignment::where('employee_id', $employee->id)
                ->whereDate('schedule_date', $monday->copy()->addDays(2)->toDateString())
                ->where('shift_id', $shift->id)
                ->exists()
        );
    }

    public function test_can_assign_shift_to_multiple_doctors(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift = Shift::factory()->create(['name' => 'Jaga Malam Dokter']);
        $doctor1 = Doctor::factory()->create(['name' => 'dr. Tirta']);
        $doctor2 = Doctor::factory()->create(['name' => 'dr. Tompi']);

        $date = Carbon::today()->addDays(5)->toDateString();

        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'doctor')
            ->set('selectedDoctorIds', [$doctor1->id, $doctor2->id])
            ->set('selectedShiftId', $shift->id)
            ->set('dateSelectionMode', 'single')
            ->set('scheduleDate', $date)
            ->call('assignShift')
            ->assertHasNoErrors()
            ->assertSee('Sebanyak 2 penugasan shift berhasil diterbitkan');

        $this->assertEquals(2, ScheduleAssignment::whereNull('employee_id')->count());

        $this->assertTrue(
            ScheduleAssignment::where('doctor_id', $doctor1->id)
                ->whereNull('employee_id')
                ->whereDate('schedule_date', $date)
                ->exists()
        );

        $this->assertTrue(
            ScheduleAssignment::where('doctor_id', $doctor2->id)
                ->whereNull('employee_id')
                ->whereDate('schedule_date', $date)
                ->exists()
        );
    }

    public function test_partial_conflicts_are_reported_and_non_conflicting_assignments_are_saved(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift1 = Shift::factory()->create(['name' => 'Shift Pagi']);
        $shift2 = Shift::factory()->create(['name' => 'Shift Siang']);

        $employee1 = Employee::factory()->create(['status' => 'active']);
        $employee2 = Employee::factory()->create(['status' => 'active']);

        $targetDate = Carbon::today()->addDays(1)->toDateString();

        // Employee 1 already has shift1 on targetDate
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee1->id,
            'shift_id' => $shift1->id,
            'schedule_date' => $targetDate,
            'status' => 'scheduled',
        ]);

        $this->actingAs($supervisor);

        // Assign shift2 on targetDate to both Employee 1 (conflict) and Employee 2 (free)
        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'employee')
            ->set('selectedEmployeeIds', [$employee1->id, $employee2->id])
            ->set('selectedShiftId', $shift2->id)
            ->set('dateSelectionMode', 'single')
            ->set('scheduleDate', $targetDate)
            ->call('assignShift')
            ->assertSee('1 jadwal dilewati karena sudah memiliki jadwal aktif')
            ->assertSee('Sebanyak 1 penugasan shift berhasil diterbitkan');

        // Total assignments on targetDate should be 2 (employee 1 shift 1, employee 2 shift 2)
        $this->assertEquals(2, ScheduleAssignment::count());
        $this->assertTrue(
            ScheduleAssignment::where('employee_id', $employee2->id)
                ->where('shift_id', $shift2->id)
                ->whereDate('schedule_date', $targetDate)
                ->exists()
        );
    }

    public function test_all_conflicts_aborts_with_error_message(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();
        $employee = Employee::factory()->create(['status' => 'active']);
        $date = Carbon::today()->addDays(2)->toDateString();

        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift1->id,
            'schedule_date' => $date,
            'status' => 'scheduled',
        ]);

        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('assignTargetType', 'employee')
            ->set('selectedEmployeeIds', [$employee->id])
            ->set('selectedShiftId', $shift2->id)
            ->set('dateSelectionMode', 'single')
            ->set('scheduleDate', $date)
            ->call('assignShift')
            ->assertSee('Terdapat bentrok jadwal');

        // Only the initial record exists
        $this->assertEquals(1, ScheduleAssignment::count());
    }

    public function test_modal_date_management_methods(): void
    {
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(ScheduleRoster::class)
            ->call('openAssignModal')
            ->set('scheduleDates', [])
            ->set('newDateToAdd', '2026-10-10')
            ->call('addSpecificDate')
            ->assertSet('scheduleDates', ['2026-10-10'])
            ->set('newDateToAdd', '2026-10-11')
            ->call('addSpecificDate')
            ->assertSet('scheduleDates', ['2026-10-10', '2026-10-11'])
            ->call('removeSpecificDate', '2026-10-10')
            ->assertSet('scheduleDates', ['2026-10-11'])
            ->call('toggleDayOfWeek', 3)
            ->assertSet('rangeDaysOfWeek', [1, 2, 4, 5, 6, 7]) // 3 removed
            ->call('toggleDayOfWeek', 3)
            ->assertSet('rangeDaysOfWeek', [1, 2, 3, 4, 5, 6, 7]); // 3 re-added
    }
}

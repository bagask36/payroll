<?php

namespace Tests\Feature;

use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Attendance\ClockInOut;
use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\Employee;
use App\Models\GeofenceLocation;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        // Seed RS Juwita Main Geofence Location
        GeofenceLocation::factory()->create([
            'name' => 'RS Juwita Kampus Utama',
            'latitude' => -6.195000,
            'longitude' => 106.820000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_access_clock_in_out(): void
    {
        $user = User::factory()->create(['is_active' => true]); // No roles or permissions

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->assertForbidden();
    }

    public function test_authorized_employee_can_view_clock_in_out(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->assertStatus(200)
            ->assertSee('Presensi Mandiri Terverifikasi');
    }

    public function test_clock_in_outside_geofence_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);

        // Coordinates far away (e.g. -6.250000, 106.900000 > 5km away)
        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.250000, 106.900000)
            ->assertSet('isWithinGeofence', false)
            ->call('clockIn')
            ->assertSee('Posisi Anda berada di luar radius rumah sakit');

        $this->assertDatabaseMissing('attendances', [
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_inside_geofence_succeeds_and_records_event(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        // Scheduled shift starting in 10 minutes (so employee is on time)
        $shift = Shift::factory()->create([
            'start_time' => Carbon::now()->addMinutes(10)->format('H:i:s'),
            'name' => 'Shift Sore',
        ]);
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($user);

        // Exact coordinates of RS Juwita
        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->assertSet('isWithinGeofence', true)
            ->call('clockIn')
            ->assertSee('Clock In berhasil dicatat');

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals('present', $attendance->status);

        $this->assertDatabaseHas('attendance_events', [
            'event_type' => 'clock_in',
            'is_within_geofence' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.clock_in',
        ]);
    }

    public function test_clock_out_calculates_work_duration(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        // Create an existing clock in 8 hours ago
        $clockInTime = Carbon::now()->subHours(8);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => $clockInTime,
            'clock_out' => null,
            'status' => 'present',
        ]);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->call('clockOut')
            ->assertSee('Clock Out berhasil dicatat');

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertGreaterThanOrEqual(470, $attendance->work_duration_minutes); // ~480 mins

        $this->assertDatabaseHas('attendance_events', [
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_out',
        ]);
    }

    public function test_attendance_adjustment_request_and_approval_flow(): void
    {
        // 1. Employee creates adjustment request
        $employeeUser = User::factory()->create(['is_active' => true]);
        $employeeUser->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'status' => 'ACTIVE',
        ]);

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(9, 30),
            'clock_out' => null,
            'status' => 'late',
        ]);

        $this->actingAs($employeeUser);

        Livewire::test(AttendanceIndex::class)
            ->call('openAdjustmentModal', $attendance->id)
            ->set('adjustedClockIn', '08:00')
            ->set('adjustedClockOut', '16:00')
            ->set('adjustmentReason', 'Perangkat scanner presensi sempat error di lobi utama')
            ->call('submitAdjustment')
            ->assertSee('Pengajuan koreksi presensi berhasil dikirimkan ke atasan');

        $adjustment = AttendanceAdjustment::where('attendance_id', $attendance->id)->firstOrFail();
        $this->assertEquals('pending', $adjustment->status);

        // 2. Supervisor approves adjustment
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(AttendanceIndex::class)
            ->call('approveAdjustment', $adjustment->id)
            ->assertSee('Koreksi presensi berhasil disetujui');

        $adjustment->refresh();
        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals($supervisor->id, $adjustment->approved_by);

        $attendance->refresh();
        $this->assertEquals('08:00:00', $attendance->clock_in->toTimeString());
        $this->assertEquals('16:00:00', $attendance->clock_out->toTimeString());
        $this->assertEquals(480, $attendance->work_duration_minutes);
    }
}

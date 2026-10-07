<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\Employee;
use App\Models\GeofenceLocation;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AttendanceTest extends TestCase
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
        ]);
    }

    public function test_it_seeds_geofence_and_attendance_records(): void
    {
        $geofence = GeofenceLocation::where('name', 'RS Permata Bunda')->firstOrFail();
        $this->assertEquals(100, $geofence->radius_meters);

        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $attendance = Attendance::where('employee_id', $nurse->id)->firstOrFail();

        $this->assertEquals('present', $attendance->status);
        $this->assertCount(2, $attendance->events);
        $this->assertTrue($attendance->events->every(fn ($e) => $e->face_matched));
    }

    public function test_geofence_haversine_distance_and_boundary_checks(): void
    {
        $geofence = GeofenceLocation::where('name', 'RS Permata Bunda')->firstOrFail();

        // 1. Point ~15m away (inside hospital courtyard): INSIDE GEOFENCE
        $insideLat = -6.91745000;
        $insideLon = 107.61911000;
        $this->assertTrue($geofence->isWithinGeofence($insideLat, $insideLon));
        $this->assertLessThanOrEqual(100, $geofence->calculateDistanceMeters($insideLat, $insideLon));

        // 2. Point ~1.5km away (Alun-Alun Bandung): OUTSIDE GEOFENCE
        $outsideLat = -6.92186000;
        $outsideLon = 107.60747000;
        $this->assertFalse($geofence->isWithinGeofence($outsideLat, $outsideLon));
        $this->assertGreaterThan(100, $geofence->calculateDistanceMeters($outsideLat, $outsideLon));
    }

    public function test_record_clock_in_evaluates_shift_lateness(): void
    {
        $shift = Shift::where('code', 'PAGI')->firstOrFail(); // 07:00:00, tolerance 15 min
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();
        $admin = User::firstOrFail();

        $date = Carbon::parse('2026-10-10');
        $assignment = ScheduleAssignment::create([
            'shift_id' => $shift->id,
            'employee_id' => $employee->id,
            'schedule_date' => $date,
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);

        $attendance = Attendance::create([
            'schedule_assignment_id' => $assignment->id,
            'employee_id' => $employee->id,
            'date' => $date,
        ]);

        // Clock in at 07:35 (20 mins beyond tolerance limit of 07:15)
        $lateTime = Carbon::parse('2026-10-10 07:35:00');
        $attendance->recordClockIn($lateTime);

        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(35, $attendance->late_duration_minutes);
    }

    public function test_attendance_adjustment_workflow_and_approval(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $supervisor = User::where('email', 'icu.head@juwitahospital.com')->firstOrFail();
        $attendance = Attendance::where('employee_id', $nurse->id)->firstOrFail();

        $originalClockIn = $attendance->clock_in;

        // Nurse requests adjustment
        $adjustedTime = Carbon::today()->setTime(6, 45);
        $adjustment = AttendanceAdjustment::create([
            'attendance_id' => $attendance->id,
            'original_clock_in' => $originalClockIn,
            'adjusted_clock_in' => $adjustedTime,
            'reason' => 'Fingerprint device queue at emergency room entry',
            'status' => 'pending',
            'requested_by' => $nurse->user->id,
        ]);

        $this->assertEquals('pending', $adjustment->status);

        // Supervisor approves adjustment
        $adjustment->approve($supervisor);

        $adjustment->refresh();
        $attendance->refresh();

        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals($supervisor->id, $adjustment->approved_by);
        $this->assertNotNull($adjustment->approved_at);
        $this->assertEquals($adjustedTime->toDateTimeString(), $attendance->clock_in->toDateTimeString());
    }

    public function test_attendance_policy_authorization(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $attendance = Attendance::where('employee_id', $nurse->id)->firstOrFail();
        $nurseUser = $nurse->user;

        // 1. Employee viewing their OWN attendance: ALLOWED
        $this->assertTrue(Gate::forUser($nurseUser)->allows('view', $attendance));

        // 2. Employee requesting adjustment: ALLOWED
        $this->assertTrue(Gate::forUser($nurseUser)->allows('requestAdjustment', Attendance::class));

        // 3. Employee attempting to approve adjustment: DENIED
        $this->assertFalse(Gate::forUser($nurseUser)->allows('approveAdjustment', Attendance::class));

        // 4. Supervisor: ALLOWED to approve adjustments
        $supervisor = User::where('email', 'icu.head@juwitahospital.com')->firstOrFail();
        $this->assertTrue(Gate::forUser($supervisor)->allows('approveAdjustment', Attendance::class));
    }
}

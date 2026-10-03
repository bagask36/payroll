<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ScheduleTest extends TestCase
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
        ]);
    }

    public function test_it_seeds_standard_hospital_shifts(): void
    {
        $this->assertDatabaseHas('shifts', ['code' => 'PAGI', 'is_overnight' => false]);
        $this->assertDatabaseHas('shifts', ['code' => 'SIANG', 'is_overnight' => false]);
        $this->assertDatabaseHas('shifts', ['code' => 'MALAM', 'is_overnight' => true]);
        $this->assertDatabaseHas('shifts', ['code' => 'NON-SHIFT', 'is_overnight' => false]);
    }

    public function test_shift_duration_and_lateness_calculations(): void
    {
        $pagi = Shift::where('code', 'PAGI')->firstOrFail();
        $this->assertEquals(7.0, $pagi->getDurationHours());
        $this->assertFalse($pagi->isLate('07:10:00'));
        $this->assertFalse($pagi->isLate('07:15:00'));
        $this->assertTrue($pagi->isLate('07:16:00'));

        $malam = Shift::where('code', 'MALAM')->firstOrFail();
        // 21:00 to 07:00 next day = 10 hours
        $this->assertEquals(10.0, $malam->getDurationHours());
        $this->assertFalse($malam->isLate('21:10:00'));
        $this->assertTrue($malam->isLate('21:20:00'));
    }

    public function test_schedule_assignment_relationships(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();

        $this->assertNotEmpty($nurse->scheduleAssignments);
        $this->assertEquals('PAGI', $nurse->scheduleAssignments->first()->shift->code);

        $this->assertNotEmpty($doctor->scheduleAssignments);
        $this->assertEquals('NON-SHIFT', $doctor->scheduleAssignments->first()->shift->code);
    }

    public function test_schedule_conflict_detection(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $today = Carbon::today()->toDateString();
        $nextMonth = Carbon::today()->addMonths(2)->toDateString();

        // Nurse is already scheduled for today in seed
        $this->assertTrue(ScheduleAssignment::hasConflict($nurse->id, null, $today));

        // Nurse is not scheduled for next month
        $this->assertFalse(ScheduleAssignment::hasConflict($nurse->id, null, $nextMonth));

        // An existing assignment should not conflict with itself when ignoring its own id
        $assignment = ScheduleAssignment::where('employee_id', $nurse->id)
            ->whereDate('schedule_date', $today)
            ->firstOrFail();
        $this->assertFalse(ScheduleAssignment::hasConflict($nurse->id, null, $today, $assignment->id));
    }

    public function test_schedule_policy_authorization(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $today = Carbon::today()->toDateString();
        $assignment = ScheduleAssignment::where('employee_id', $nurse->id)
            ->whereDate('schedule_date', $today)
            ->firstOrFail();

        $nurseUser = $nurse->user;

        // 1. Employee viewing their OWN duty roster: ALLOWED
        $this->assertTrue(Gate::forUser($nurseUser)->allows('view', $assignment));

        // 2. Employee attempting to create/manage duty roster: DENIED
        $this->assertFalse(Gate::forUser($nurseUser)->allows('create', ScheduleAssignment::class));
        $this->assertFalse(Gate::forUser($nurseUser)->allows('update', $assignment));
        $this->assertFalse(Gate::forUser($nurseUser)->allows('delete', $assignment));

        // 3. Supervisor (Head Nurse): ALLOWED to create, update, delete
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->assertTrue(Gate::forUser($supervisor)->allows('viewAny', ScheduleAssignment::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('create', ScheduleAssignment::class));
        $this->assertTrue(Gate::forUser($supervisor)->allows('update', $assignment));
        $this->assertTrue(Gate::forUser($supervisor)->allows('delete', $assignment));

        // 4. HR Admin: ALLOWED
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('create', ScheduleAssignment::class));
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('update', $assignment));
    }
}

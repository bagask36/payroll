<?php

namespace Tests\Feature;

use App\Livewire\Attendance\ClockInOut;
use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Employee\EmployeeShow;
use App\Livewire\Master\MasterIndex;
use App\Livewire\Schedule\ScheduleRoster;
use App\Livewire\Tables\EmployeeTable;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftAndSchedulePatternTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
            EmployeeSeeder::class,
            DoctorSeeder::class,
            ScheduleSeeder::class,
            MasterPayrollAndSystemSeeder::class,
        ]);

        $this->adminUser = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
    }

    public function test_new_employee_defaults_to_non_shift_schedule_type(): void
    {
        $employee = Employee::create([
            'nip' => 'PEG-TEST-999',
            'full_name' => 'Staf Kantor Percobaan',
            'phone_number' => '081234567899',
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1995-01-01',
            'ktp_number' => '3201010101950009',
            'residential_address' => 'Jl. Test No. 9',
            'unit_id' => 1,
            'position_id' => 1,
            'status' => 'active',
        ]);

        $this->assertEquals('non_shift', $employee->schedule_type);
        $this->assertTrue($employee->isNonShiftWorker());
        $this->assertFalse($employee->isShiftWorker());
    }

    public function test_shift_model_supports_shift_and_non_shift_types(): void
    {
        $pagi = Shift::where('code', 'PAGI')->firstOrFail();
        $this->assertEquals('shift', $pagi->type);
        $this->assertTrue($pagi->isShift());
        $this->assertFalse($pagi->isNonShift());

        $nonShift = Shift::where('code', 'NON-SHIFT')->firstOrFail();
        $this->assertEquals('non_shift', $nonShift->type);
        $this->assertTrue($nonShift->isNonShift());
        $this->assertFalse($nonShift->isShift());

        $this->assertGreaterThanOrEqual(3, Shift::shift()->count());
        $this->assertGreaterThanOrEqual(1, Shift::nonShift()->count());
    }

    public function test_master_index_saves_shift_type(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(MasterIndex::class)
            ->set('activeTab', 'shifts')
            ->call('openCreateShift')
            ->set('shiftCode', 'POLI-SORE')
            ->set('shiftName', 'Poli Sore Reguler')
            ->set('shiftType', 'non_shift')
            ->set('shiftStartTime', '15:00')
            ->set('shiftEndTime', '20:00')
            ->set('shiftLateTolerance', 15)
            ->call('saveShift')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shifts', [
            'code' => 'POLI-SORE',
            'type' => 'non_shift',
        ]);
    }

    public function test_employee_index_registers_employee_with_schedule_type(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EmployeeIndex::class)
            ->call('openCreateModal')
            ->set('nip', 'PEG-TEST-888')
            ->set('name', 'Perawat Shift IGD Baru')
            ->set('phone', '081234567888')
            ->set('gender', 'female')
            ->set('birthDate', '1998-02-02')
            ->set('birthPlace', 'Bogor')
            ->set('ktpNumber', '3201010202980008')
            ->set('address', 'Jl. Pajajaran No. 8')
            ->set('unitId', 1)
            ->set('positionId', 1)
            ->set('contractType', 'contract')
            ->set('joinDate', '2026-01-01')
            ->set('scheduleType', 'shift')
            ->call('createEmployee')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'nip' => 'PEG-TEST-888',
            'schedule_type' => 'shift',
        ]);
    }

    public function test_employee_table_filters_by_schedule_type(): void
    {
        // Nurse Budi Santoso is shift
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $this->assertEquals('shift', $nurse->schedule_type);

        Livewire::actingAs($this->adminUser)
            ->test(EmployeeTable::class, ['scheduleTypeFilter' => 'shift'])
            ->assertSee('Budi Santoso');

        Livewire::actingAs($this->adminUser)
            ->test(EmployeeTable::class, ['scheduleTypeFilter' => 'non_shift'])
            ->assertDontSee('Budi Santoso');
    }

    public function test_schedule_roster_defaults_to_shift_workers_in_assign_modal(): void
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(ScheduleRoster::class)
            ->call('openAssignModal');

        // Budi Santoso (shift) must be in the employee options
        $component->assertSee('Budi Santoso');

        // Dewi Sartika (HR officer - non_shift) should not be present when showAllEmployeesInAssign is false
        $component->assertDontSee('Dewi Sartika');

        // When toggled to show all, Dewi Sartika appears
        $component->set('showAllEmployeesInAssign', true)
            ->assertSee('Dewi Sartika');
    }

    public function test_clock_in_out_automatically_assigns_regular_shift_for_non_shift_worker(): void
    {
        $hrUser = User::where('email', 'hr.officer@juwitahospital.com')->firstOrFail();
        $employee = $hrUser->employee;
        $this->assertNotNull($employee);
        $this->assertTrue($employee->isNonShiftWorker());

        $today = Carbon::today()->toDateString();

        // Ensure no schedule assignment exists yet for today
        ScheduleAssignment::where('employee_id', $employee->id)->whereDate('schedule_date', $today)->delete();

        Livewire::actingAs($hrUser)
            ->test(ClockInOut::class)
            ->assertSet('todaySchedule.shift.code', 'NON-SHIFT');

        $this->assertDatabaseHas('schedule_assignments', [
            'employee_id' => $employee->id,
            'notes' => 'Jadwal Reguler Otomatis (Non-Shift)',
        ]);
    }

    public function test_employee_show_updates_schedule_type_via_bio(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail(); // Dewi Sartika
        $this->assertEquals('non_shift', $employee->schedule_type);

        Livewire::actingAs($this->adminUser)
            ->test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBioModal')
            ->set('bioScheduleType', 'shift')
            ->call('saveBio')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertEquals('shift', $employee->schedule_type);
    }
}

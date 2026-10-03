<?php

namespace Tests\Feature;

use App\Livewire\Master\MasterIndex;
use App\Models\BpjsRule;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
            ScheduleSeeder::class,
            PayrollSeeder::class,
        ]);
    }

    public function test_unauthorized_user_cannot_access_master_data_hub(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');

        $this->actingAs($employeeUser);

        $this->get(route('master.index'))
            ->assertStatus(403);
    }

    public function test_hr_admin_can_view_master_data_hub(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->assertStatus(200)
            ->assertSee('Pusat Pengaturan Master Data')
            ->assertSee('Ruangan')
            ->assertSee('Standar Jabatan')
            ->assertSee('Jam Dinas')
            ->assertSee('Kebijakan Cuti')
            ->assertSee('Titik Geofence');
    }

    public function test_hr_admin_can_create_and_update_unit(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $department = Department::firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'units')
            ->call('openCreateUnit')
            ->set('unitCode', 'HEMO-1')
            ->set('unitName', 'Instalasi Hemodialisa')
            ->set('unitDepartmentId', $department->id)
            ->set('unitIsActive', true)
            ->call('saveUnit')
            ->assertHasNoErrors()
            ->assertSee('Instalasi Hemodialisa berhasil disimpan');

        $this->assertDatabaseHas('units', [
            'code' => 'HEMO-1',
            'name' => 'Instalasi Hemodialisa',
            'is_active' => true,
        ]);

        $unit = Unit::where('code', 'HEMO-1')->firstOrFail();

        // Update
        Livewire::test(MasterIndex::class)
            ->call('editUnit', $unit->id)
            ->set('unitName', 'Instalasi Hemodialisa & Dialisis')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Instalasi Hemodialisa & Dialisis',
        ]);
    }

    public function test_hr_admin_can_create_and_update_position(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'positions')
            ->call('openCreatePosition')
            ->set('positionTitle', 'Perawat Mahir Dialisis')
            ->set('positionGradeLevel', 'III/b')
            ->call('savePosition')
            ->assertHasNoErrors()
            ->assertSee('Perawat Mahir Dialisis berhasil disimpan');

        $this->assertDatabaseHas('positions', [
            'title' => 'Perawat Mahir Dialisis',
            'grade_level' => 'III/b',
        ]);
    }

    public function test_hr_admin_can_create_and_update_shift(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'shifts')
            ->call('openCreateShift')
            ->set('shiftCode', 'MIDNIGHT-SP')
            ->set('shiftName', 'Shift Khusus Tengah Malam')
            ->set('shiftStartTime', '23:00')
            ->set('shiftEndTime', '07:00')
            ->set('shiftIsOvernight', true)
            ->set('shiftLateTolerance', 10)
            ->call('saveShift')
            ->assertHasNoErrors()
            ->assertSee('Shift Khusus Tengah Malam berhasil disimpan');

        $this->assertDatabaseHas('shifts', [
            'code' => 'MIDNIGHT-SP',
            'is_overnight' => true,
            'late_tolerance_minutes' => 10,
        ]);
    }

    public function test_hr_admin_can_create_and_update_leave_type(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'leave_types')
            ->call('openCreateLeaveType')
            ->set('leaveTypeCode', 'HAJJ')
            ->set('leaveTypeName', 'Cuti Ibadah Haji')
            ->set('leaveTypeDefaultDays', 40)
            ->set('leaveTypeDeductsBalance', false)
            ->set('leaveTypeRequiresAttachment', true)
            ->call('saveLeaveType')
            ->assertHasNoErrors()
            ->assertSee('Cuti Ibadah Haji berhasil disimpan');

        $this->assertDatabaseHas('leave_types', [
            'code' => 'HAJJ',
            'default_days_per_year' => 40,
            'deducts_balance' => false,
            'requires_attachment' => true,
        ]);
    }

    public function test_hr_admin_can_create_and_update_geofence(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'geofences')
            ->call('openCreateGeofence')
            ->set('geofenceName', 'Gedung Diagnostic Center')
            ->set('geofenceLat', -6.209123)
            ->set('geofenceLng', 106.845789)
            ->set('geofenceRadius', 120)
            ->call('saveGeofence')
            ->assertHasNoErrors()
            ->assertSee('Gedung Diagnostic Center berhasil disimpan');

        $this->assertDatabaseHas('geofence_locations', [
            'name' => 'Gedung Diagnostic Center',
            'radius_meters' => 120,
        ]);
    }

    public function test_hr_admin_can_create_payroll_period(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'payroll_periods')
            ->call('openCreatePeriod')
            ->set('periodName', 'Payroll Periode Desember 2026')
            ->set('periodStartDate', '2026-12-01')
            ->set('periodEndDate', '2026-12-31')
            ->set('periodCutoffDate', '2026-12-25')
            ->set('periodIsClosed', false)
            ->call('savePeriod')
            ->assertHasNoErrors()
            ->assertSee('Payroll Periode Desember 2026 berhasil disimpan');

        $this->assertDatabaseHas('payroll_periods', [
            'name' => 'Payroll Periode Desember 2026',
            'is_closed' => false,
        ]);
    }

    public function test_hr_admin_can_update_bpjs_rule_rates(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        $rule = BpjsRule::where('program_name', 'kesehatan')->firstOrFail();

        Livewire::test(MasterIndex::class)
            ->set('activeTab', 'bpjs_rules')
            ->call('editBpjs', $rule->id)
            ->set('bpjsEmployeeRate', 0.0125)
            ->set('bpjsEmployerRate', 0.0450)
            ->set('bpjsWageCeiling', 15000000)
            ->call('saveBpjs')
            ->assertHasNoErrors()
            ->assertSee('Tarif BPJS kesehatan berhasil diperbarui');

        $this->assertDatabaseHas('bpjs_rules', [
            'id' => $rule->id,
            'employee_rate' => 0.0125,
            'employer_rate' => 0.0450,
            'wage_ceiling' => 15000000,
        ]);
    }
}

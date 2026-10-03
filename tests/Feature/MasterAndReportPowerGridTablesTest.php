<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\AttendanceReportTable;
use App\Livewire\Tables\DeductionComponentTable;
use App\Livewire\Tables\DoctorReportTable;
use App\Livewire\Tables\EarningComponentTable;
use App\Livewire\Tables\EmployeeReportTable;
use App\Livewire\Tables\GeofenceTable;
use App\Livewire\Tables\LeaveReportTable;
use App\Livewire\Tables\LeaveTypeTable;
use App\Livewire\Tables\PayrollPeriodTable;
use App\Livewire\Tables\PayrollReportTable;
use App\Livewire\Tables\PositionTable;
use App\Livewire\Tables\ShiftTable;
use App\Livewire\Tables\UnitTable;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\GeofenceLocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterAndReportPowerGridTablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(MasterPayrollAndSystemSeeder::class);

        $this->admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_unit_table_renders(): void
    {
        Livewire::test(UnitTable::class)
            ->assertStatus(200)
            ->assertSee('IGD');
    }

    public function test_position_table_renders(): void
    {
        Livewire::test(PositionTable::class)
            ->assertStatus(200);
    }

    public function test_shift_table_renders(): void
    {
        Shift::firstOrCreate(
            ['code' => 'PAGI'],
            [
                'name' => 'Shift Pagi',
                'start_time' => '07:00:00',
                'end_time' => '14:00:00',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        Livewire::test(ShiftTable::class)
            ->assertStatus(200)
            ->assertSee('PAGI');
    }

    public function test_leave_type_table_renders(): void
    {
        LeaveType::firstOrCreate(
            ['code' => 'ANNUAL'],
            [
                'name' => 'Cuti Tahunan',
                'default_days' => 12,
                'deducts_annual_leave' => true,
                'requires_attachment' => false,
                'is_active' => true,
            ]
        );

        Livewire::test(LeaveTypeTable::class)
            ->assertStatus(200)
            ->assertSee('ANNUAL');
    }

    public function test_geofence_table_renders(): void
    {
        GeofenceLocation::firstOrCreate(
            ['name' => 'RS Juwita Kampus Utama'],
            [
                'latitude' => -6.91746400,
                'longitude' => 107.61912200,
                'radius_meters' => 100,
                'is_active' => true,
            ]
        );

        Livewire::test(GeofenceTable::class)
            ->assertStatus(200)
            ->assertSee('RS Juwita Kampus Utama');
    }

    public function test_payroll_period_table_renders(): void
    {
        Livewire::test(PayrollPeriodTable::class)
            ->assertStatus(200);
    }

    public function test_earning_component_table_renders(): void
    {
        Livewire::test(EarningComponentTable::class)
            ->assertStatus(200)
            ->assertSee('TUNJ_JABATAN');
    }

    public function test_deduction_component_table_renders(): void
    {
        Livewire::test(DeductionComponentTable::class)
            ->assertStatus(200)
            ->assertSee('POT_KETERLAMBATAN');
    }

    public function test_employee_report_table_renders(): void
    {
        Livewire::test(EmployeeReportTable::class)
            ->assertStatus(200);
    }

    public function test_attendance_report_table_renders(): void
    {
        Livewire::test(AttendanceReportTable::class)
            ->assertStatus(200);
    }

    public function test_leave_report_table_renders(): void
    {
        Livewire::test(LeaveReportTable::class)
            ->assertStatus(200);
    }

    public function test_payroll_report_table_renders(): void
    {
        Livewire::test(PayrollReportTable::class)
            ->assertStatus(200);
    }

    public function test_doctor_report_table_renders(): void
    {
        Livewire::test(DoctorReportTable::class)
            ->assertStatus(200);
    }
}

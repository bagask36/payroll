<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\MyPayslipTable;
use App\Livewire\Tables\PayrollItemTable;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollTableTest extends TestCase
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
            LeaveSeeder::class,
            PayrollSeeder::class,
        ]);
    }

    public function test_powergrid_payroll_item_table_renders_data(): void
    {
        $payrollUser = User::factory()->create();
        $payrollUser->assignRole('payroll');

        $run = PayrollRun::firstOrFail();
        $item = PayrollItem::where('payroll_run_id', $run->id)->firstOrFail();

        $this->actingAs($payrollUser);

        Livewire::test(PayrollItemTable::class, ['payrollRunId' => $run->id])
            ->assertStatus(200)
            ->assertSee($item->employee->full_name)
            ->assertSee('Gaji Pokok');
    }

    public function test_powergrid_my_payslip_table_renders_data(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;

        $this->actingAs($nurseUser);

        Livewire::test(MyPayslipTable::class)
            ->assertStatus(200)
            ->assertSee('Payroll')
            ->assertSee('Estimasi');
    }
}

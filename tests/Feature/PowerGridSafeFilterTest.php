<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Tables\EmployeeTable;
use App\Livewire\Tables\DoctorTable;
use App\Livewire\Tables\LeaveRequestTable;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PowerGridSafeFilterTest extends TestCase
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
        ]);

        $this->adminUser = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
    }

    public function test_clear_filter_does_not_throw_type_error_for_dotted_nested_fields(): void
    {
        $employeeTable = app(EmployeeTable::class);

        // Directly verify clearFilter with dotted fields (which threw TypeError in PHP 8.4)
        $employeeTable->clearFilter('employees.schedule_type');
        $employeeTable->clearFilter('employees.full_name');
        $employeeTable->clearFilter('employees.nip');
        $employeeTable->clearFilter('employees.status');

        $doctorTable = app(DoctorTable::class);
        $doctorTable->clearFilter('doctors.name');
        $doctorTable->clearFilter('doctors.specialization');

        $leaveTable = app(LeaveRequestTable::class);
        $leaveTable->clearFilter('leave_requests.status');

        $this->assertTrue(true);
    }

    public function test_employee_table_can_clear_select_filter_via_livewire_call(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EmployeeTable::class, ['scheduleTypeFilter' => 'non_shift'])
            ->call('filterSelect', 'employees.schedule_type', 'Pola Kerja')
            ->assertOk();
    }

    public function test_employee_index_with_schedule_type_filter_query_parameter_renders_safely(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/employees?scheduleTypeFilter=non_shift')
            ->assertOk();

        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['scheduleTypeFilter' => 'non_shift'])
            ->test(EmployeeIndex::class)
            ->assertOk();
    }
}

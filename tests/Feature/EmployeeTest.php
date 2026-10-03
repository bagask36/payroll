<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
            EmployeeSeeder::class,
        ]);
    }

    public function test_it_creates_employee_with_auto_generated_uuid(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();

        $this->assertNotNull($employee->uuid);
        $this->assertTrue(uuid_is_valid($employee->uuid));
    }

    public function test_ktp_and_bank_account_are_encrypted_at_rest(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();
        $rawEmployee = DB::table('employees')->where('id', $employee->id)->first();

        // Database value must NOT equal the plain plaintext KTP
        $this->assertNotEquals('3201015504950001', $rawEmployee->ktp_number);
        // Model accessor decrypts it back to plain text
        $this->assertEquals('3201015504950001', $employee->ktp_number);

        // Same for bank account
        $bank = $employee->primaryBankAccount;
        $this->assertNotNull($bank);
        $rawBank = DB::table('employee_bank_accounts')->where('id', $bank->id)->first();
        $this->assertNotEquals('8410293847', $rawBank->account_number);
        $this->assertEquals('8410293847', $bank->account_number);
    }

    public function test_employee_relationships_and_accessors(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();

        $this->assertNotNull($employee->user);
        $this->assertEquals($employee->id, $employee->user->employee->id);

        $this->assertNotNull($employee->unit);
        $this->assertEquals('SDM', $employee->unit->code);

        $this->assertNotNull($employee->position);
        $this->assertEquals('Staff Penggajian & HRIS', $employee->position->title);

        $this->assertNotNull($employee->currentEmployment);
        $this->assertEquals('permanent', $employee->currentEmployment->contract_type);

        $this->assertNotNull($employee->currentSalary);
        $this->assertEquals(6500000.00, (float) $employee->currentSalary->basic_salary);

        $this->assertNotNull($employee->primaryBankAccount);
        $this->assertEquals('BCA', $employee->primaryBankAccount->bank_name);
    }

    public function test_restricts_deletion_of_unit_with_assigned_employees(): void
    {
        $unit = Unit::where('code', 'SDM')->firstOrFail();

        $this->expectException(QueryException::class);
        $unit->delete();
    }

    public function test_employee_supports_soft_deletes(): void
    {
        $employee = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $employee->delete();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertNull(Employee::find($employee->id));
        $this->assertNotNull(Employee::withTrashed()->find($employee->id));
    }

    public function test_scope_active_filters_inactive_employees(): void
    {
        $unit = Unit::where('code', 'SDM')->firstOrFail();
        $pos = Position::where('unit_id', $unit->id)->firstOrFail();

        Employee::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $pos->id,
            'status' => 'terminated',
        ]);

        $activeEmployees = Employee::active()->get();
        $this->assertTrue($activeEmployees->every(fn (Employee $e) => $e->status === 'active'));
    }

    public function test_employee_policy_view_sensitive_pii(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();
        $owner = $employee->user;

        // 1. Employee viewing their own profile: ALLOWED
        $this->assertTrue(Gate::forUser($owner)->allows('viewSensitive', $employee));

        // 2. HR Admin with employee.view_sensitive: ALLOWED
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('viewSensitive', $employee));

        // 3. Regular employee without permission viewing another employee: DENIED
        $otherEmployee = User::factory()->create();
        $otherEmployee->assignRole('employee');
        $this->assertFalse(Gate::forUser($otherEmployee)->allows('viewSensitive', $employee));
    }

    public function test_employee_policy_view_salary(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();

        // 1. Super Admin: ALLOWED
        $superAdmin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewSalary', $employee));

        // 2. HR Manager: ALLOWED
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');
        $this->assertTrue(Gate::forUser($hrManager)->allows('viewSalary', $employee));

        // 3. Payroll Officer: ALLOWED
        $payroll = User::factory()->create();
        $payroll->assignRole('payroll');
        $this->assertTrue(Gate::forUser($payroll)->allows('viewSalary', $employee));

        // 4. HR Admin (lacks employee.view_salary): DENIED
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');
        $this->assertFalse(Gate::forUser($hrAdmin)->allows('viewSalary', $employee));

        // 5. Supervisor: DENIED
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->assertFalse(Gate::forUser($supervisor)->allows('viewSalary', $employee));
    }

    public function test_employee_policy_salary_and_employment_mutations(): void
    {
        $employee = Employee::where('nip', 'PEG-2024001')->firstOrFail();

        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');

        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        // HR Admin can update employment contracts, but CANNOT update salary or deactivate
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('updateEmployment', $employee));
        $this->assertFalse(Gate::forUser($hrAdmin)->allows('updateSalary', $employee));
        $this->assertFalse(Gate::forUser($hrAdmin)->allows('deactivate', $employee));

        // HR Manager CAN update employment contracts, update salary, and deactivate
        $this->assertTrue(Gate::forUser($hrManager)->allows('updateEmployment', $employee));
        $this->assertTrue(Gate::forUser($hrManager)->allows('updateSalary', $employee));
        $this->assertTrue(Gate::forUser($hrManager)->allows('deactivate', $employee));
    }
}

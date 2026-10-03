<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeShow;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeShowManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    // ---------------------------------------------------------------
    // BIODATA
    // ---------------------------------------------------------------

    public function test_hr_admin_can_update_biodata_including_sensitive_fields(): void
    {
        $employee = Employee::factory()->create(['full_name' => 'Nama Lama']);
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBioModal')
            ->assertSet('showBioModal', true)
            ->set('bioFullName', 'Nama Baru')
            ->set('bioPhone', '081234567890')
            ->set('bioAddress', 'Jl. Melati No. 5')
            ->set('bioBirthPlace', 'Bandung')
            ->set('bioBirthDate', '1990-05-10')
            ->set('bioMaritalStatus', 'married')
            ->set('bioTaxDependents', 2)
            ->set('bioNpwp', '12.345.678.9-012.000')
            ->set('bioKtp', '3201234567890123')
            ->call('saveBio')
            ->assertHasNoErrors()
            ->assertSet('showBioModal', false);

        $employee->refresh();
        $this->assertSame('Nama Baru', $employee->full_name);
        $this->assertSame('married', $employee->marital_status);
        $this->assertSame(2, $employee->tax_dependents);
        $this->assertSame('3201234567890123', $employee->ktp_number);
        $this->assertSame('Jl. Melati No. 5', $employee->residential_address);

        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.biodata_updated']);
    }

    public function test_biodata_audit_log_never_contains_raw_ktp(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBioModal')
            ->set('bioKtp', '3201999988887777')
            ->call('saveBio')
            ->assertHasNoErrors();

        $log = AuditLog::where('action', 'employee.biodata_updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('3201999988887777', json_encode($log->new_values));
        $this->assertSame('[changed]', $log->new_values['ktp_number'] ?? null);
    }

    public function test_biodata_validation_rejects_invalid_ktp_and_dependents(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBioModal')
            ->set('bioKtp', '123')
            ->set('bioTaxDependents', 5)
            ->call('saveBio')
            ->assertHasErrors(['bioKtp', 'bioTaxDependents']);
    }

    public function test_supervisor_cannot_update_biodata(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('supervisor'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBioModal')
            ->assertForbidden();

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('bioFullName', 'Hacked')
            ->call('saveBio')
            ->assertForbidden();

        $this->assertNotSame('Hacked', $employee->fresh()->full_name);
    }

    // ---------------------------------------------------------------
    // BANK ACCOUNTS
    // ---------------------------------------------------------------

    public function test_hr_admin_can_add_edit_and_delete_bank_account_with_single_primary(): void
    {
        $employee = Employee::factory()->create();
        $existing = EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'bank_name' => 'BCA',
            'is_primary' => true,
        ]);
        $this->actingAs($this->userWithRole('hr_admin'));

        // Add new primary account → old one must lose primary flag
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBankModal')
            ->set('bankName', 'Mandiri')
            ->set('accountNumber', '1234567890')
            ->set('accountHolder', 'Pegawai Uji')
            ->set('isPrimaryBank', true)
            ->call('saveBank')
            ->assertHasNoErrors();

        $new = EmployeeBankAccount::where('employee_id', $employee->id)->where('bank_name', 'Mandiri')->firstOrFail();
        $this->assertTrue($new->is_primary);
        $this->assertFalse($existing->fresh()->is_primary);
        $this->assertSame('1234567890', $new->account_number);

        // Encrypted at rest
        $raw = \DB::table('employee_bank_accounts')->where('id', $new->id)->value('account_number');
        $this->assertNotSame('1234567890', $raw);

        // Edit
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBankModal', $new->id)
            ->set('accountNumber', '9999988888')
            ->call('saveBank')
            ->assertHasNoErrors();
        $this->assertSame('9999988888', $new->fresh()->account_number);

        // Audit only stores masked number
        $log = AuditLog::where('action', 'employee.bank_account_updated')->latest('id')->first();
        $this->assertSame('****8888', $log->new_values['account_number']);

        // Delete
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('deleteBank', $new->id)
            ->assertHasNoErrors();
        $this->assertDatabaseMissing('employee_bank_accounts', ['id' => $new->id]);
    }

    public function test_bank_account_number_must_be_numeric(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBankModal')
            ->set('bankName', 'BNI')
            ->set('accountNumber', '12-AB-34')
            ->set('accountHolder', 'X')
            ->call('saveBank')
            ->assertHasErrors(['accountNumber']);
    }

    public function test_cannot_edit_bank_account_belonging_to_another_employee(): void
    {
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $otherBank = EmployeeBankAccount::factory()->create(['employee_id' => $other->id]);
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('deleteBank', $otherBank->id)
            ->assertNotFound();

        $this->assertDatabaseHas('employee_bank_accounts', ['id' => $otherBank->id]);
    }

    public function test_employee_cannot_self_manage_own_bank_account(): void
    {
        $user = $this->userWithRole('employee');
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openBankModal')
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // DOCUMENTS
    // ---------------------------------------------------------------

    public function test_hr_admin_can_upload_document_for_employee(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openDocModal')
            ->set('docUploadType', 'ijazah')
            ->set('docUploadedFile', UploadedFile::fake()->create('ijazah.pdf', 200, 'application/pdf'))
            ->call('uploadDoc')
            ->assertHasNoErrors();

        $doc = Document::where('documentable_id', $employee->id)
            ->where('documentable_type', Employee::class)
            ->firstOrFail();

        $this->assertSame('ijazah', $doc->document_type);
        $this->assertSame('ijazah.pdf', $doc->original_name);
        Storage::disk('local')->assertExists($doc->storage_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.uploaded']);
    }

    public function test_document_upload_rejects_disallowed_mime(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openDocModal')
            ->set('docUploadedFile', UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'))
            ->call('uploadDoc')
            ->assertHasErrors(['docUploadedFile']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_supervisor_cannot_upload_document_for_other_employee(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs($this->userWithRole('supervisor'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openDocModal')
            ->assertForbidden();
    }

    public function test_hr_manager_can_soft_delete_document_but_hr_admin_cannot(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $doc = Document::create([
            'documentable_type' => Employee::class,
            'documentable_id' => $employee->id,
            'document_type' => 'ktp',
            'original_name' => 'ktp.pdf',
            'storage_path' => 'compliance/ktp/ktp.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'storage_provider' => 'local',
            'uploaded_by' => User::factory()->create()->id,
        ]);

        // hr_admin lacks document.delete
        $this->actingAs($this->userWithRole('hr_admin'));
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('deleteDoc', $doc->id)
            ->assertForbidden();
        $this->assertNotSoftDeleted($doc);

        $this->actingAs($this->userWithRole('hr_manager'));
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('deleteDoc', $doc->id)
            ->assertHasNoErrors();
        $this->assertSoftDeleted($doc);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeShow;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\Unit;
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

    // ---------------------------------------------------------------
    // RIWAYAT PENUGASAN & MUTASI (EMPLOYMENT)
    // ---------------------------------------------------------------

    public function test_hr_admin_can_record_new_assignment_and_it_updates_employee_profile_and_history(): void
    {
        $oldUnit = Unit::factory()->create(['name' => 'Rawat Inap']);
        $newUnit = Unit::factory()->create(['name' => 'IGD']);
        $oldPos = Position::factory()->create(['title' => 'Perawat Pratama']);
        $newPos = Position::factory()->create(['title' => 'Kepala Ruang IGD']);

        $employee = Employee::factory()->create([
            'unit_id' => $oldUnit->id,
            'position_id' => $oldPos->id,
        ]);

        $initialEmployment = EmployeeEmployment::create([
            'employee_id' => $employee->id,
            'unit_id' => $oldUnit->id,
            'position_id' => $oldPos->id,
            'contract_type' => 'permanent',
            'contract_number' => 'SK/001',
            'start_date' => '2024-01-01',
            'is_active' => true,
        ]);

        $this->actingAs($this->userWithRole('hr_admin'));

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openEmploymentModal')
            ->assertSet('showEmploymentModal', true)
            ->set('employmentUnitId', $newUnit->id)
            ->set('employmentPositionId', $newPos->id)
            ->set('employmentContractType', 'permanent')
            ->set('employmentContractNumber', 'SK/PROMOSI/2026/099')
            ->set('employmentStartDate', '2026-02-01')
            ->set('employmentNotes', 'Promosi dan mutasi ke IGD')
            ->call('saveEmployment')
            ->assertHasNoErrors()
            ->assertSet('showEmploymentModal', false);

        // Verify old employment deactivated
        $initialEmployment->refresh();
        $this->assertFalse((bool) $initialEmployment->is_active);
        $this->assertEquals($oldUnit->id, $initialEmployment->unit_id);
        $this->assertEquals($oldPos->id, $initialEmployment->position_id);

        // Verify new employment created
        $newEmployment = EmployeeEmployment::where('employee_id', $employee->id)
            ->where('contract_number', 'SK/PROMOSI/2026/099')
            ->first();
        $this->assertNotNull($newEmployment);
        $this->assertTrue((bool) $newEmployment->is_active);
        $this->assertEquals($newUnit->id, $newEmployment->unit_id);
        $this->assertEquals($newPos->id, $newEmployment->position_id);
        $this->assertEquals('Promosi dan mutasi ke IGD', $newEmployment->notes);

        // Verify employee main profile automatically updated
        $employee->refresh();
        $this->assertEquals($newUnit->id, $employee->unit_id);
        $this->assertEquals($newPos->id, $employee->position_id);

        // Verify audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.assignment_recorded',
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
        ]);
    }

    public function test_unauthorized_user_cannot_record_assignment(): void
    {
        $user = $this->userWithRole('employee');
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $newUnit = Unit::factory()->create();
        $newPos = Position::factory()->create();

        $this->actingAs($user);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openEmploymentModal')
            ->assertForbidden();

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('employmentUnitId', $newUnit->id)
            ->set('employmentPositionId', $newPos->id)
            ->set('employmentContractType', 'permanent')
            ->set('employmentContractNumber', 'SK/HACK/001')
            ->set('employmentStartDate', '2026-02-01')
            ->call('saveEmployment')
            ->assertForbidden();
    }

    public function test_recording_new_sk_assignment_syncs_standard_salary_and_positional_allowance_from_master_position(): void
    {
        $manager = $this->userWithRole('hr_manager');
        $this->actingAs($manager);

        // Setup master salary component for tunjangan jabatan
        $tunjJabatan = SalaryComponent::create([
            'code' => 'TUNJ_JABATAN',
            'name' => 'Tunjangan Jabatan Struktural',
            'type' => 'allowance',
            'is_taxable' => true,
            'is_fixed' => true,
            'default_amount' => 1500000,
            'is_active' => true,
        ]);

        $unit = Unit::factory()->create();
        $pos = Position::factory()->create([
            'unit_id' => $unit->id,
            'title' => 'Kepala Ruang ICU',
            'default_basic_salary' => 7800000,
            'default_positional_allowance' => 2000000,
        ]);

        $employee = Employee::factory()->create([
            'unit_id' => $unit->id,
            'position_id' => $pos->id,
        ]);

        // Initial base salary
        EmployeeSalary::create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'effective_from' => '2025-01-01',
            'revision_reason' => 'Gaji Awal',
            'created_by' => $manager->id,
        ]);

        $targetUnit = Unit::factory()->create();
        $targetPos = Position::factory()->create([
            'unit_id' => $targetUnit->id,
            'title' => 'Kepala Instalasi Rawat Inap',
            'default_basic_salary' => 9500000,
            'default_positional_allowance' => 3000000,
        ]);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openEmploymentModal')
            ->assertSet('showEmploymentModal', true)
            ->set('employmentPositionId', $targetPos->id)
            ->assertSet('employmentBasicSalary', '9500000')
            ->assertSet('employmentPositionalAllowance', '3000000')
            ->set('employmentUnitId', $targetUnit->id)
            ->set('employmentContractType', 'permanent')
            ->set('employmentContractNumber', 'SK/RSJ/2026/088')
            ->set('employmentStartDate', '2026-03-01')
            ->call('saveEmployment')
            ->assertHasNoErrors()
            ->assertSet('showEmploymentModal', false);

        // Check new employment
        $this->assertDatabaseHas('employee_employments', [
            'employee_id' => $employee->id,
            'position_id' => $targetPos->id,
            'unit_id' => $targetUnit->id,
            'contract_number' => 'SK/RSJ/2026/088',
            'is_active' => true,
        ]);

        // Check salary synchronized
        $this->assertDatabaseHas('employee_salaries', [
            'employee_id' => $employee->id,
            'basic_salary' => 9500000,
            'revision_reason' => 'SK Penetapan Jabatan Kepala Instalasi Rawat Inap (No. SK/RSJ/2026/088)',
        ]);

        $latestSalary = $employee->fresh()->currentSalary;
        $this->assertEquals(9500000.00, (float) $latestSalary->basic_salary);
        $this->assertEquals('2026-03-01', $latestSalary->effective_from?->toDateString());

        // Check positional allowance synchronized
        $this->assertDatabaseHas('employee_salary_components', [
            'employee_id' => $employee->id,
            'salary_component_id' => $tunjJabatan->id,
            'amount' => 3000000,
            'is_active' => true,
        ]);
    }

    public function test_can_apply_position_default_salary_in_salary_modal(): void
    {
        $manager = $this->userWithRole('hr_manager');
        $this->actingAs($manager);

        $pos = Position::factory()->create([
            'title' => 'Dokter Jaga IGD',
            'default_basic_salary' => 8500000,
        ]);

        $employee = Employee::factory()->create([
            'position_id' => $pos->id,
        ]);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openSalaryModal')
            ->assertSet('showSalaryModal', true)
            ->call('applyPositionDefaultSalary')
            ->assertSet('basicSalary', '8500000')
            ->assertSet('revisionReason', 'Standar SK Jabatan Dokter Jaga IGD');
    }

    public function test_hr_admin_can_record_assignment_with_physical_sk_document_upload(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $unit = Unit::factory()->create();
        $pos = Position::factory()->create();

        $this->actingAs($this->userWithRole('hr_admin'));

        $file = UploadedFile::fake()->create('sk_pengangkatan.pdf', 500, 'application/pdf');

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openEmploymentModal')
            ->set('employmentUnitId', $unit->id)
            ->set('employmentPositionId', $pos->id)
            ->set('employmentContractType', 'permanent')
            ->set('employmentContractNumber', 'SK/DIR/2026/055')
            ->set('employmentStartDate', '2026-03-01')
            ->set('employmentDocumentFile', $file)
            ->call('saveEmployment')
            ->assertHasNoErrors()
            ->assertSet('showEmploymentModal', false);

        $employment = EmployeeEmployment::where('employee_id', $employee->id)
            ->where('contract_number', 'SK/DIR/2026/055')
            ->firstOrFail();

        $this->assertNotNull($employment->document_id);
        $this->assertNotNull($employment->document);
        $this->assertSame('sk_pengangkatan.pdf', $employment->document->original_name);
        $this->assertSame('contract', $employment->document->document_type);
        $this->assertTrue(Storage::disk('local')->exists($employment->document->storage_path));
    }

    public function test_backdated_assignment_retroactively_adjusts_previous_employment_and_salary(): void
    {
        $manager = $this->userWithRole('hr_manager');
        $this->actingAs($manager);

        $oldUnit = Unit::factory()->create();
        $newUnit = Unit::factory()->create();
        $oldPos = Position::factory()->create(['title' => 'Staf Awal']);
        $newPos = Position::factory()->create(['title' => 'Kepala Unit Baru', 'default_basic_salary' => 8000000]);

        $employee = Employee::factory()->create([
            'unit_id' => $oldUnit->id,
            'position_id' => $oldPos->id,
        ]);

        $prevEmployment = EmployeeEmployment::create([
            'employee_id' => $employee->id,
            'unit_id' => $oldUnit->id,
            'position_id' => $oldPos->id,
            'contract_type' => 'permanent',
            'contract_number' => 'SK/LAMA/2024',
            'start_date' => '2024-01-01',
            'is_active' => true,
        ]);

        $prevSalary = EmployeeSalary::create([
            'employee_id' => $employee->id,
            'basic_salary' => 5000000,
            'effective_from' => '2024-01-01',
            'revision_reason' => 'Gaji Lama',
            'created_by' => $manager->id,
        ]);

        // Input retroactive SK dated '2025-06-01'
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openEmploymentModal')
            ->set('employmentUnitId', $newUnit->id)
            ->set('employmentPositionId', $newPos->id)
            ->set('employmentContractType', 'permanent')
            ->set('employmentContractNumber', 'SK/RETRO/2025/001')
            ->set('employmentStartDate', '2025-06-01')
            ->call('saveEmployment')
            ->assertHasNoErrors();

        // Prev employment should be closed at 2025-05-31
        $prevEmployment->refresh();
        $this->assertFalse((bool) $prevEmployment->is_active);
        $this->assertEquals('2025-05-31', $prevEmployment->end_date?->toDateString());

        // Prev salary should have effective_to closed at 2025-05-31
        $prevSalary->refresh();
        $this->assertEquals('2025-05-31', $prevSalary->effective_to?->toDateString());

        // New employment is active and starts at 2025-06-01
        $newEmp = EmployeeEmployment::where('contract_number', 'SK/RETRO/2025/001')->firstOrFail();
        $this->assertTrue((bool) $newEmp->is_active);
        $this->assertEquals('2025-06-01', $newEmp->start_date?->toDateString());
    }

    public function test_hr_admin_can_edit_past_employment_sk_and_upload_missing_document(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $unit = Unit::factory()->create();
        $pos = Position::factory()->create();

        $pastEmployment = EmployeeEmployment::create([
            'employee_id' => $employee->id,
            'unit_id' => $unit->id,
            'position_id' => $pos->id,
            'contract_type' => 'permanent',
            'contract_number' => 'SK/DRAFT/001',
            'start_date' => '2023-01-01',
            'end_date' => '2024-12-31',
            'is_active' => false,
            'notes' => 'Catatan lama',
        ]);

        $this->actingAs($this->userWithRole('hr_admin'));

        $file = UploadedFile::fake()->create('sk_lampau.pdf', 300, 'application/pdf');

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('editEmployment', $pastEmployment->id)
            ->assertSet('editingEmploymentId', $pastEmployment->id)
            ->assertSet('employmentContractNumber', 'SK/DRAFT/001')
            ->set('employmentContractNumber', 'SK/DEFINITIF/2023/001')
            ->set('employmentNotes', 'Catatan diperbarui dengan fisik SK')
            ->set('employmentDocumentFile', $file)
            ->call('saveEmployment')
            ->assertHasNoErrors()
            ->assertSet('showEmploymentModal', false);

        $pastEmployment->refresh();
        $this->assertEquals('SK/DEFINITIF/2023/001', $pastEmployment->contract_number);
        $this->assertEquals('Catatan diperbarui dengan fisik SK', $pastEmployment->notes);
        $this->assertNotNull($pastEmployment->document_id);
        $this->assertSame('sk_lampau.pdf', $pastEmployment->document->original_name);
    }

    public function test_authorized_user_can_download_employment_sk_document(): void
    {
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $user = $this->userWithRole('hr_admin');
        $this->actingAs($user);

        // Upload fake storage file
        $storagePath = 'documents/compliance/contract/test_sk.pdf';
        Storage::disk('local')->put($storagePath, 'PDF_CONTENT_TEST');

        $document = Document::create([
            'documentable_type' => Employee::class,
            'documentable_id' => $employee->id,
            'uploaded_by' => $user->id,
            'document_type' => 'contract',
            'original_name' => 'sk_asli.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 16,
            'storage_path' => $storagePath,
            'storage_provider' => 'local',
        ]);

        $employment = EmployeeEmployment::create([
            'employee_id' => $employee->id,
            'unit_id' => $employee->unit_id,
            'position_id' => $employee->position_id,
            'contract_type' => 'permanent',
            'contract_number' => 'SK/RESMI/001',
            'start_date' => '2024-01-01',
            'is_active' => true,
            'document_id' => $document->id,
        ]);

        $response = Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('downloadEmploymentDocument', $employment->id);

        $response->assertFileDownloaded('sk_asli.pdf');
    }

    public function test_employee_assignment_auto_pulls_and_inherits_sk_number_and_document_from_master_position(): void
    {
        Storage::fake('local');
        $user = $this->userWithRole('hr_admin');
        $this->actingAs($user);

        $storagePath = 'documents/compliance/position/sk_formasi_kepala_ruang.pdf';
        Storage::disk('local')->put($storagePath, 'PDF_MASTER_FORMASI');

        $doc = Document::create([
            'documentable_type' => Position::class,
            'documentable_id' => 1,
            'uploaded_by' => $user->id,
            'document_type' => 'contract',
            'original_name' => 'sk_formasi_kepala_ruang.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 18,
            'storage_path' => $storagePath,
            'storage_provider' => 'local',
        ]);

        $unit = Unit::factory()->create();
        $position = Position::factory()->create([
            'unit_id' => $unit->id,
            'title' => 'Kepala Ruang Rawat Inap',
            'default_contract_number' => 'SK/DIR/RSJ/2026/099',
            'document_id' => $doc->id,
        ]);

        $employee = Employee::factory()->create();

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('openEmploymentModal')
            ->set('employmentPositionId', $position->id)
            ->assertSet('employmentContractNumber', 'SK/DIR/RSJ/2026/099')
            ->assertSet('employmentExistingDocumentId', $doc->id)
            ->set('employmentUnitId', $unit->id)
            ->set('employmentContractType', 'permanent')
            ->set('employmentStartDate', '2026-03-01')
            ->call('saveEmployment')
            ->assertHasNoErrors()
            ->assertSet('showEmploymentModal', false);

        $employment = EmployeeEmployment::where('employee_id', $employee->id)
            ->where('contract_number', 'SK/DIR/RSJ/2026/099')
            ->firstOrFail();

        $this->assertEquals($doc->id, $employment->document_id);
    }

    public function test_hr_admin_can_manage_position_sk_number_and_upload_document_in_position_index(): void
    {
        Storage::fake('local');
        $user = $this->userWithRole('hr_admin');
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('sk_penetapan_dokter.pdf', 400, 'application/pdf');

        Livewire::test(\App\Livewire\Master\PositionIndex::class)
            ->call('openCreatePosition')
            ->set('positionTitle', 'Dokter Subspesialis Bedah')
            ->set('positionDefaultContractNumber', 'SK/DIR/FORMASI/2026/007')
            ->set('positionSkDocumentFile', $file)
            ->call('savePosition')
            ->assertHasNoErrors()
            ->assertSet('showPositionModal', false);

        $pos = Position::where('title', 'Dokter Subspesialis Bedah')->firstOrFail();
        $this->assertEquals('SK/DIR/FORMASI/2026/007', $pos->default_contract_number);
        $this->assertNotNull($pos->document_id);
        $this->assertSame('sk_penetapan_dokter.pdf', $pos->document->original_name);
        $this->assertTrue(Storage::disk('local')->exists($pos->document->storage_path));

        // Test download
        $response = Livewire::test(\App\Livewire\Master\PositionIndex::class)
            ->call('downloadPositionDocument', $pos->id);

        $response->assertFileDownloaded('sk_penetapan_dokter.pdf');
    }
}



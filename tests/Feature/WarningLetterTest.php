<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeShow;
use App\Livewire\Master\MasterIndex;
use App\Livewire\Master\WarningLetterTypeIndex;
use App\Models\Employee;
use App\Models\EmployeeWarningLetter;
use App\Models\User;
use App\Models\WarningLetterType;
use Carbon\Carbon;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\WarningLetterTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarningLetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            MasterPayrollAndSystemSeeder::class,
            WarningLetterTypeSeeder::class,
            OrganizationSeeder::class,
            EmployeeSeeder::class,
        ]);
    }

    public function test_unauthorized_user_cannot_access_warning_letter_type_master(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');

        $this->actingAs($employeeUser);

        $this->get(route('master.warning-letter-types'))
            ->assertStatus(403);
    }

    public function test_hr_admin_can_manage_warning_letter_type_master(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(WarningLetterTypeIndex::class)
            ->assertStatus(200)
            ->assertSee('Master Tingkat & Jenis Surat Peringatan')
            ->assertSee('SP-1')
            ->assertSee('SP-2')
            ->assertSee('SP-3')
            ->call('openCreate')
            ->set('code', 'SP-KHUSUS')
            ->set('name', 'Surat Peringatan Khusus Pelanggaran Etika')
            ->set('level', 2)
            ->set('validityMonths', 12)
            ->set('description', 'Sanksi pelanggaran etika profesi RS')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('warning_letter_types', [
            'code' => 'SP-KHUSUS',
            'name' => 'Surat Peringatan Khusus Pelanggaran Etika',
            'level' => 2,
            'validity_months' => 12,
        ]);
    }

    public function test_hr_admin_can_issue_warning_letter_to_employee_with_auto_calculated_validity(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $employee = Employee::factory()->create();
        $spType = WarningLetterType::where('code', 'SP-1')->firstOrFail();

        $this->actingAs($admin);

        $today = Carbon::today()->toDateString();
        $expectedValidUntil = Carbon::today()->addMonths($spType->validity_months)->toDateString();

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('activeTab', 'disciplinary')
            ->call('openCreateWarningLetter')
            ->assertSet('spIssuedDate', $today)
            ->assertSet('spValidFrom', $today)
            ->set('spTypeId', $spType->id)
            ->assertSet('spValidUntil', $expectedValidUntil)
            ->set('spLetterNumber', '001/SP-I/SDM-RSJ/10/2026')
            ->set('spViolationReason', 'Keterlambatan masuk dinas lebih dari 5 kali berturut-turut tanpa izin.')
            ->set('spAuthorizedBy', 'Direktur SDM RS Permata Bunda')
            ->call('saveWarningLetter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_warning_letters', [
            'employee_id' => $employee->id,
            'warning_letter_type_id' => $spType->id,
            'letter_number' => '001/SP-I/SDM-RSJ/10/2026',
            'status' => 'active',
        ]);

        $createdSp = EmployeeWarningLetter::where('letter_number', '001/SP-I/SDM-RSJ/10/2026')->firstOrFail();
        $this->assertEquals($today, $createdSp->valid_from->toDateString());
        $this->assertEquals($expectedValidUntil, $createdSp->valid_until->toDateString());

        $employee->refresh();
        $this->assertTrue($employee->hasActiveWarningLetter());
        $this->assertEquals(1, $employee->activeWarningLetters()->count());
    }

    public function test_hr_admin_can_revoke_active_warning_letter(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $employee = Employee::factory()->create();
        $spType = WarningLetterType::where('code', 'SP-1')->firstOrFail();

        $warningLetter = EmployeeWarningLetter::create([
            'employee_id' => $employee->id,
            'warning_letter_type_id' => $spType->id,
            'letter_number' => '002/SP-I/SDM-RSJ/10/2026',
            'issued_date' => now()->toDateString(),
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonths(6)->toDateString(),
            'violation_reason' => 'Pelanggaran atribut dinas',
            'authorized_by' => 'Kepala HRD',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('activeTab', 'disciplinary')
            ->call('openRevokeModal', $warningLetter->id)
            ->set('revocationReason', 'Pegawai telah menunjukkan perbaikan kinerja dan kepatuhan teladan selama 3 bulan.')
            ->call('revokeWarningLetter')
            ->assertHasNoErrors();

        $warningLetter->refresh();
        $this->assertEquals('revoked', $warningLetter->status);
        $this->assertEquals('revoked', $warningLetter->effective_status);
        $this->assertNotNull($warningLetter->revoked_at);
        $this->assertEquals($admin->id, $warningLetter->revoked_by);

        $employee->refresh();
        $this->assertFalse($employee->hasActiveWarningLetter());
    }

    public function test_hr_admin_can_edit_existing_warning_letter(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $employee = Employee::factory()->create();
        $spType = WarningLetterType::where('code', 'SP-1')->firstOrFail();

        $warningLetter = EmployeeWarningLetter::create([
            'employee_id' => $employee->id,
            'warning_letter_type_id' => $spType->id,
            'letter_number' => '003/SP-I/SDM-RSJ/10/2026',
            'issued_date' => now()->toDateString(),
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonths(6)->toDateString(),
            'violation_reason' => 'Pelanggaran awal',
            'authorized_by' => 'Kabag SDM',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('activeTab', 'disciplinary')
            ->call('editWarningLetter', $warningLetter->id)
            ->assertSet('editingWarningLetterId', $warningLetter->id)
            ->assertSet('spLetterNumber', '003/SP-I/SDM-RSJ/10/2026')
            ->set('spLetterNumber', '003/SP-I/REVISI/10/2026')
            ->set('spViolationReason', 'Pelanggaran telah dikoreksi kronologinya')
            ->call('saveWarningLetter')
            ->assertHasNoErrors();

        $warningLetter->refresh();
        $this->assertEquals('003/SP-I/REVISI/10/2026', $warningLetter->letter_number);
        $this->assertEquals('Pelanggaran telah dikoreksi kronologinya', $warningLetter->violation_reason);
    }

    public function test_hr_admin_can_download_warning_letter_document(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $employee = Employee::factory()->create();
        $spType = WarningLetterType::where('code', 'SP-1')->firstOrFail();

        $filePath = 'warning_letters/sample_sp.pdf';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filePath, 'PDF_SAMPLE_CONTENT');

        $warningLetter = EmployeeWarningLetter::create([
            'employee_id' => $employee->id,
            'warning_letter_type_id' => $spType->id,
            'letter_number' => '004/SP-I/SDM-RSJ/10/2026',
            'issued_date' => now()->toDateString(),
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonths(6)->toDateString(),
            'violation_reason' => 'Pelanggaran SOP',
            'authorized_by' => 'Kabag SDM',
            'document_path' => $filePath,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $response = Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('activeTab', 'disciplinary')
            ->call('downloadWarningLetter', $warningLetter->id);

        $response->assertFileDownloaded('sample_sp.pdf');
    }

    public function test_hr_admin_can_manage_warning_letter_types_via_master_index(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(MasterIndex::class)
            ->call('setTab', 'warning_letter_types')
            ->assertSet('activeTab', 'warning_letter_types')
            ->call('openCreateWarningLetterType')
            ->assertSet('showWarningLetterTypeModal', true)
            ->set('wltCode', 'TEGURAN-LISAN')
            ->set('wltName', 'Teguran Lisan Tercatat')
            ->set('wltLevel', 0)
            ->set('wltValidityMonths', 1)
            ->set('wltDescription', 'Catatan teguran lisan resmi untuk evaluasi berkala')
            ->call('saveWarningLetterType')
            ->assertHasNoErrors()
            ->assertSet('showWarningLetterTypeModal', false);

        $this->assertDatabaseHas('warning_letter_types', [
            'code' => 'TEGURAN-LISAN',
            'name' => 'Teguran Lisan Tercatat',
            'level' => 0,
            'validity_months' => 1,
        ]);
    }

    public function test_unauthorized_employee_cannot_issue_warning_letter(): void
    {
        // An employee cannot access employee detail profile
        $employee = Employee::firstOrFail();
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');
        $employee->update(['user_id' => $employeeUser->id]);

        $this->actingAs($employeeUser);

        $this->get("/employees/{$employee->id}")
            ->assertForbidden();
    }

    public function test_employee_report_reflects_active_warning_letters_and_exports_csv(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $employee = Employee::factory()->create(['full_name' => 'Karyawan Uji Disiplin', 'status' => 'active']);
        $spType = WarningLetterType::where('code', 'SP-1')->firstOrFail();

        EmployeeWarningLetter::create([
            'employee_id' => $employee->id,
            'warning_letter_type_id' => $spType->id,
            'letter_number' => '099/SP-I/LAP/10/2026',
            'issued_date' => now()->toDateString(),
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonths(6)->toDateString(),
            'violation_reason' => 'Uji kedisiplinan laporan',
            'authorized_by' => 'Kabag SDM',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        // Test metric card & CSV export
        Livewire::test(\App\Livewire\Report\EmployeeReportIndex::class)
            ->assertStatus(200)
            ->assertSee('Dalam Sanksi (SP Aktif)')
            ->call('exportCsv')
            ->assertFileDownloaded('laporan-kepegawaian-rs-juwita.csv');

        // Test PowerGrid table renders disciplinary column
        Livewire::test(\App\Livewire\Tables\EmployeeReportTable::class)
            ->assertStatus(200)
            ->assertSee('SP Aktif: Surat Peringatan Pertama (SP I)')
            ->assertSee('Tertib');
    }
}


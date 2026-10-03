<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\DocumentTable;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DocumentSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentTableTest extends TestCase
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
            DocumentSeeder::class,
        ]);
    }

    public function test_powergrid_document_table_renders_data(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(DocumentTable::class)
            ->assertStatus(200)
            ->assertSee('KTP')
            ->assertSee('Nama Dokumen');
    }

    public function test_powergrid_document_table_segregates_unauthorized_user(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;

        $otherEmployee = Employee::where('nip', '!=', 'PEG-2025042')->firstOrFail();
        Document::factory()->create([
            'documentable_type' => Employee::class,
            'documentable_id' => $otherEmployee->id,
            'original_name' => 'rahasia_tersembunyi.pdf',
            'uploaded_by' => $otherEmployee->user_id ?? 1,
        ]);

        $this->actingAs($nurseUser);

        Livewire::test(DocumentTable::class)
            ->assertStatus(200)
            ->assertDontSee('rahasia_tersembunyi.pdf');
    }
}

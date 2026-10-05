<?php

namespace Tests\Feature;

use App\Livewire\System\GoogleDriveStorageIndex;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Document\GoogleDriveStorageProvider;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GoogleDriveStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(TestUserSeeder::class);
        $this->seed(MasterPayrollAndSystemSeeder::class);

        $this->superAdmin = User::where('email', 'admin@juwitahospital.com')->first();

        $this->staffUser = User::factory()->create([
            'email' => 'staff.nurse@rsjuwita.co.id',
            'is_active' => true,
        ]);
        $this->staffUser->assignRole('employee');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('system.google-drive-storage'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_superadmin_user_is_forbidden(): void
    {
        $response = $this->actingAs($this->staffUser)->get(route('system.google-drive-storage'));
        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_google_drive_storage_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('system.google-drive-storage'));

        $response->assertStatus(200);
        $response->assertSee('Kapasitas');
        $response->assertSee('Limitasi Google Drive');
        $response->assertSee('JUWITA-STORAGE');
        $response->assertSee('Sisa Kuota Folder');
    }

    public function test_superadmin_sees_cloud_storage_in_sidebar(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Penyimpanan Cloud');
        $response->assertSee(route('system.google-drive-storage'));
    }

    public function test_non_superadmin_does_not_see_cloud_storage_in_sidebar(): void
    {
        $response = $this->actingAs($this->staffUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Penyimpanan Cloud');
    }

    public function test_superadmin_can_save_folder_limit_settings(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(GoogleDriveStorageIndex::class)
            ->set('folderMaxLimitMb', 20480)
            ->set('warningThresholdPercent', 85)
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertSet('feedbackMessage', 'Konfigurasi batasan kuota folder Google Drive berhasil disimpan!');

        $this->assertEquals('20480', SystemSetting::get('gdrive_folder_max_size_mb'));
        $this->assertEquals('85', SystemSetting::get('gdrive_warning_threshold_percent'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system.gdrive_storage_limit_updated',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_folder_limit_validation_requires_minimum_values(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(GoogleDriveStorageIndex::class)
            ->set('folderMaxLimitMb', 50) // Less than 100 MB
            ->set('warningThresholdPercent', 40) // Less than 50%
            ->call('saveSettings')
            ->assertHasErrors(['folderMaxLimitMb', 'warningThresholdPercent']);
    }

    public function test_storage_provider_metrics_with_mocked_http(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
            ], 200),
            'https://www.googleapis.com/drive/v3/about*' => Http::response([
                'user' => [
                    'displayName' => 'RS Juwita Admin',
                    'emailAddress' => 'admin@rsjuwita.co.id',
                ],
                'storageQuota' => [
                    'limit' => '107374182400', // 100 GB
                    'usage' => '21474836480',  // 20 GB
                    'usageInDrive' => '20000000000',
                    'usageInDriveTrash' => '1474836480',
                ],
            ], 200),
            'https://www.googleapis.com/drive/v3/files/12O9c7YilU3hFOGLelYJJEUhp4SGoAF_z?*' => Http::response([
                'id' => '12O9c7YilU3hFOGLelYJJEUhp4SGoAF_z',
                'name' => 'JUWITA-STORAGE',
                'webViewLink' => 'https://drive.google.com/drive/folders/12O9c7YilU3hFOGLelYJJEUhp4SGoAF_z',
            ], 200),
            'https://www.googleapis.com/drive/v3/files?*' => Http::response([
                'files' => [
                    [
                        'id' => 'file_1',
                        'name' => 'clock_in_sample.jpg',
                        'mimeType' => 'image/jpeg',
                        'size' => '1048576', // 1 MB
                        'createdTime' => '2026-10-05T01:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $provider = new GoogleDriveStorageProvider(
            clientId: 'fake-client-id',
            clientSecret: 'fake-secret',
            refreshToken: 'fake-refresh-token'
        );

        $metrics = $provider->getStorageMetrics('12O9c7YilU3hFOGLelYJJEUhp4SGoAF_z', forceRefresh: true);

        $this->assertTrue($metrics['configured']);
        $this->assertEquals('admin@rsjuwita.co.id', $metrics['account']['email']);
        $this->assertEquals(107374182400, $metrics['account']['total_bytes']);
        $this->assertEquals(21474836480, $metrics['account']['used_bytes']);
        $this->assertEquals(85899345920, $metrics['account']['free_bytes']); // 80 GB free
        $this->assertEquals(20.0, $metrics['account']['usage_percentage']);
        $this->assertEquals('JUWITA-STORAGE', $metrics['folder']['name']);
        $this->assertEquals(1, $metrics['folder']['total_files']);
        $this->assertEquals(1048576, $metrics['folder']['total_bytes']);
    }
}

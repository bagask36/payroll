<?php

namespace App\Livewire\System;

use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use App\Services\Document\GoogleDriveStorageProvider;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manajemen Kapasitas Google Drive - RS Juwita')]
class GoogleDriveStorageIndex extends Component
{
    public int $folderMaxLimitMb = 10240; // Default 10 GB

    public int $warningThresholdPercent = 80;

    public string $searchQuery = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Akses ke manajemen penyimpanan Google Drive terbatas hanya untuk Super Admin.');
        }

        $this->folderMaxLimitMb = (int) SystemSetting::get('gdrive_folder_max_size_mb', '10240');
        $this->warningThresholdPercent = (int) SystemSetting::get('gdrive_warning_threshold_percent', '80');
    }

    public function refreshMetrics(GoogleDriveStorageProvider $driveProvider): void
    {
        $this->feedbackMessage = null;
        $this->errorMessage = null;

        try {
            $driveProvider->getStorageMetrics(forceRefresh: true);
            $this->feedbackMessage = 'Data metrik penyimpanan Google Drive berhasil diperbarui langsung dari cloud!';
        } catch (\Throwable $e) {
            $this->errorMessage = 'Gagal memperbarui metrik: '.$e->getMessage();
        }
    }

    public function saveSettings(AuditService $auditService): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $this->validate([
            'folderMaxLimitMb' => 'required|integer|min:100|max:10485760', // Max 10 TB in MB
            'warningThresholdPercent' => 'required|integer|min:50|max:99',
        ], [
            'folderMaxLimitMb.required' => 'Batas maksimal folder harus diisi.',
            'folderMaxLimitMb.min' => 'Batas minimal adalah 100 MB.',
            'warningThresholdPercent.required' => 'Ambang batas peringatan harus diisi.',
            'warningThresholdPercent.min' => 'Ambang batas minimal 50%.',
            'warningThresholdPercent.max' => 'Ambang batas maksimal 99%.',
        ]);

        $oldLimit = SystemSetting::get('gdrive_folder_max_size_mb', '10240');
        $oldThreshold = SystemSetting::get('gdrive_warning_threshold_percent', '80');

        SystemSetting::set('gdrive_folder_max_size_mb', (string) $this->folderMaxLimitMb, 'storage', 'Batas maksimal ukuran folder penyimpanan Google Drive (MB)');
        SystemSetting::set('gdrive_warning_threshold_percent', (string) $this->warningThresholdPercent, 'storage', 'Persentase threshold peringatan storage Google Drive');

        $auditService->log(
            action: 'system.gdrive_storage_limit_updated',
            auditable: $user,
            oldValues: [
                'folder_max_size_mb' => $oldLimit,
                'warning_threshold_percent' => $oldThreshold,
            ],
            newValues: [
                'folder_max_size_mb' => $this->folderMaxLimitMb,
                'warning_threshold_percent' => $this->warningThresholdPercent,
            ]
        );

        $this->feedbackMessage = 'Konfigurasi batasan kuota folder Google Drive berhasil disimpan!';
    }

    public static function formatBytes(int|float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $base = log($bytes, 1024);
        $floor = floor($base);

        if (! isset($units[$floor])) {
            $floor = count($units) - 1;
        }

        return round(pow(1024, $base - $floor), $precision).' '.$units[$floor];
    }

    public function render(GoogleDriveStorageProvider $driveProvider)
    {
        $metrics = $driveProvider->getStorageMetrics();
        $folderId = $metrics['folder']['id'] ?? (string) config('services.google_drive.attendance_folder_id', '');

        $folderFiles = [];
        if (! empty($folderId) && ($metrics['configured'] ?? false)) {
            $filesResult = $driveProvider->getFolderFiles(
                folderId: $folderId,
                pageSize: 20,
                search: trim($this->searchQuery) ?: null
            );
            $folderFiles = $filesResult['files'] ?? [];
        }

        // Folder calculations
        $folderUsedBytes = (int) ($metrics['folder']['total_bytes'] ?? 0);
        $folderMaxBytes = (int) ($this->folderMaxLimitMb * 1024 * 1024);
        $folderRemainingBytes = max(0, $folderMaxBytes - $folderUsedBytes);
        $folderUsagePercent = $folderMaxBytes > 0 ? round(($folderUsedBytes / $folderMaxBytes) * 100, 2) : 0.0;
        $isFolderExceeded = $folderUsedBytes >= $folderMaxBytes;
        $isFolderWarning = $folderUsagePercent >= $this->warningThresholdPercent;

        // Account calculations
        $accountTotalBytes = (int) ($metrics['account']['total_bytes'] ?? 0);
        $accountUsedBytes = (int) ($metrics['account']['used_bytes'] ?? 0);
        $accountFreeBytes = (int) ($metrics['account']['free_bytes'] ?? 0);
        $accountUsagePercent = (float) ($metrics['account']['usage_percentage'] ?? 0.0);

        return view('livewire.system.google-drive-storage-index', [
            'metrics' => $metrics,
            'folderFiles' => $folderFiles,
            'folderUsedBytes' => $folderUsedBytes,
            'folderMaxBytes' => $folderMaxBytes,
            'folderRemainingBytes' => $folderRemainingBytes,
            'folderUsagePercent' => $folderUsagePercent,
            'isFolderExceeded' => $isFolderExceeded,
            'isFolderWarning' => $isFolderWarning,
            'accountTotalBytes' => $accountTotalBytes,
            'accountUsedBytes' => $accountUsedBytes,
            'accountFreeBytes' => $accountFreeBytes,
            'accountUsagePercent' => $accountUsagePercent,
        ]);
    }
}

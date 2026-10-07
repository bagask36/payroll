<?php

namespace App\Livewire\System;

use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use App\Services\Document\GoogleDriveStorageProvider;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manajemen Kapasitas Google Drive - RS Permata Bunda')]
class GoogleDriveStorageIndex extends Component
{
    public int $folderMaxLimitMb = 10240; // Default 10 GB

    public int $warningThresholdPercent = 80;

    public string $searchQuery = '';

    public string $rootFolderId = '';

    /**
     * Set of expanded folder IDs [folderId => bool]
     *
     * @var array<string, bool>
     */
    public array $expandedFolders = [];

    /**
     * Map of child items for each folder [folderId => array<int, array>]
     *
     * @var array<string, array<int, array>>
     */
    public array $folderItemsMap = [];

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

        $this->rootFolderId = (string) config('services.google_drive.attendance_folder_id', '12O9c7YilU3hFOGLelYJJEUhp4SGoAF_z');
    }

    public function toggleFolder(string $folderId, GoogleDriveStorageProvider $driveProvider): void
    {
        $isCurrentlyExpanded = ! empty($this->expandedFolders[$folderId]);

        if ($isCurrentlyExpanded) {
            $this->expandedFolders[$folderId] = false;
        } else {
            $this->expandedFolders[$folderId] = true;

            // Load child items if not loaded yet
            if (! isset($this->folderItemsMap[$folderId])) {
                $result = $driveProvider->getFolderFiles($folderId, pageSize: 100);
                $this->folderItemsMap[$folderId] = $result['files'] ?? [];
            }
        }
    }

    public function expandAll(GoogleDriveStorageProvider $driveProvider): void
    {
        // Expand root folder
        $this->expandedFolders[$this->rootFolderId] = true;
        if (! isset($this->folderItemsMap[$this->rootFolderId])) {
            $result = $driveProvider->getFolderFiles($this->rootFolderId, pageSize: 100);
            $this->folderItemsMap[$this->rootFolderId] = $result['files'] ?? [];
        }

        // Expand any child folders discovered so far
        foreach ($this->folderItemsMap as $parentFolderId => $items) {
            foreach ($items as $item) {
                if (! empty($item['is_folder']) && ! empty($item['id'])) {
                    $childId = $item['id'];
                    $this->expandedFolders[$childId] = true;
                    if (! isset($this->folderItemsMap[$childId])) {
                        $childResult = $driveProvider->getFolderFiles($childId, pageSize: 100);
                        $this->folderItemsMap[$childId] = $childResult['files'] ?? [];
                    }
                }
            }
        }
    }

    public function collapseAll(): void
    {
        $this->expandedFolders = [];
    }

    public function setPreset(int $gb): void
    {
        $this->folderMaxLimitMb = $gb * 1024;
    }

    public function setThresholdPreset(int $percent): void
    {
        $this->warningThresholdPercent = $percent;
    }

    public function refreshMetrics(GoogleDriveStorageProvider $driveProvider): void
    {
        $this->feedbackMessage = null;
        $this->errorMessage = null;

        try {
            $driveProvider->getStorageMetrics($this->rootFolderId, forceRefresh: true);
            // Clear cached tree items to force fresh re-fetch
            $this->folderItemsMap = [];

            $result = $driveProvider->getFolderFiles($this->rootFolderId, pageSize: 100);
            $this->folderItemsMap[$this->rootFolderId] = $result['files'] ?? [];

            $this->feedbackMessage = 'Data metrik & struktur direktori Google Drive berhasil diperbarui langsung dari cloud!';
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
        $metrics = $driveProvider->getStorageMetrics($this->rootFolderId);

        // Fetch root folder items if not yet loaded in folderItemsMap
        if (! isset($this->folderItemsMap[$this->rootFolderId]) && ($metrics['configured'] ?? false)) {
            $result = $driveProvider->getFolderFiles($this->rootFolderId, pageSize: 100);
            $this->folderItemsMap[$this->rootFolderId] = $result['files'] ?? [];
        }

        $rootItems = $this->folderItemsMap[$this->rootFolderId] ?? [];

        // Apply search query filter if typed
        if (! empty(trim($this->searchQuery))) {
            $query = strtolower(trim($this->searchQuery));
            $rootItems = array_values(array_filter($rootItems, function ($item) use ($query) {
                return str_contains(strtolower($item['name'] ?? ''), $query);
            }));
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
            'rootItems' => $rootItems,
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

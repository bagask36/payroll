<?php

namespace App\Services\Document;

use App\Contracts\DocumentStorageProvider;
use App\DTOs\Document\StoredDocumentResult;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleDriveStorageProvider implements DocumentStorageProvider
{
    /**
     * @param  string|null  $sharedDriveId  Google Workspace Shared Drive ID
     * @param  string|null  $serviceAccountKeyPath  Path to Google Service Account JSON
     */
    public function __construct(
        protected ?string $sharedDriveId = null,
        protected ?string $serviceAccountKeyPath = null,
        protected ?string $clientId = null,
        protected ?string $clientSecret = null,
        protected ?string $refreshToken = null
    ) {
        $this->sharedDriveId = $sharedDriveId ?? config('services.google_drive.shared_drive_id');
        $this->serviceAccountKeyPath = $serviceAccountKeyPath ?? config('services.google_drive.service_account_json');
        $this->clientId = $clientId ?? config('services.google_drive.client_id');
        $this->clientSecret = $clientSecret ?? config('services.google_drive.client_secret');
        $this->refreshToken = $refreshToken ?? config('services.google_drive.refresh_token');
    }

    /**
     * Determine if Google Drive credentials (OAuth 2.0 or Service Account) are valid and present.
     */
    public function isConfigured(): bool
    {
        // 1. OAuth 2.0 Refresh Token (for personal @gmail.com accounts)
        if (! empty($this->refreshToken) && ! empty($this->clientId) && ! empty($this->clientSecret)) {
            return true;
        }

        // 2. Service Account (for Google Workspace Shared Drives)
        if (! empty($this->serviceAccountKeyPath)) {
            $fullPath = $this->resolvePath($this->serviceAccountKeyPath);

            return file_exists($fullPath) && is_readable($fullPath);
        }

        return false;
    }

    public function store(
        string $directory,
        UploadedFile|string $file,
        ?string $customFileName = null,
        array $options = []
    ): StoredDocumentResult {
        $fileName = $file instanceof UploadedFile
            ? ($customFileName ?? $file->getClientOriginalName())
            : ($customFileName ?? 'attendance_'.now()->format('YmdHis').'.jpg');

        $mimeType = $file instanceof UploadedFile
            ? ($file->getClientMimeType() ?: 'image/jpeg')
            : 'image/jpeg';

        $fileContent = $file instanceof UploadedFile
            ? $file->get()
            : $file;

        $size = strlen($fileContent);

        // If credentials are configured, execute real Google Drive API upload
        if ($this->isConfigured()) {
            $token = $this->getAccessToken();
            if ($token) {
                $rootFolderId = $options['root_folder_id'] ?? (string) config('services.google_drive.attendance_folder_id', '');
                $targetFolderId = ! empty($directory) && $directory !== $rootFolderId
                    ? $this->resolveFolderHierarchy($token, $rootFolderId, $directory)
                    : $rootFolderId;

                $fileId = $this->uploadToDrive($token, $targetFolderId, $fileName, $mimeType, $fileContent);
                if ($fileId) {
                    return new StoredDocumentResult(
                        fileIdentifier: $fileId,
                        fileName: $fileName,
                        mimeType: $mimeType,
                        sizeBytes: $size,
                        provider: $this->name(),
                        metadata: [
                            'shared_drive_id' => $this->sharedDriveId,
                            'directory' => $directory,
                            'folder_id' => $targetFolderId,
                        ]
                    );
                }
            }
        }

        // Mock / fallback for testing or before service account is configured
        if (! $this->isConfigured() && ! app()->environment('production')) {
            $fakeFileId = 'gdrive_'.md5($directory.'/'.$fileName.microtime());

            return new StoredDocumentResult(
                fileIdentifier: $fakeFileId,
                fileName: $fileName,
                mimeType: $mimeType,
                sizeBytes: $size,
                provider: $this->name(),
                metadata: [
                    'shared_drive_id' => $this->sharedDriveId,
                    'directory' => $directory,
                    'mode' => 'mock_or_unconfigured',
                ]
            );
        }

        throw new RuntimeException('Google Drive service account credentials are not configured or upload failed.');
    }

    public function get(string $fileIdentifier): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->get('https://www.googleapis.com/drive/v3/files/'.urlencode($fileIdentifier).'?alt=media&supportsAllDrives=true');

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('Google Drive download failed: HTTP '.$response->status());

            return null;
        } catch (\Throwable $e) {
            Log::error('Google Drive get error: '.$e->getMessage());

            return null;
        }
    }

    public function getTemporaryUrl(string $fileIdentifier, DateTimeInterface $expiration): string
    {
        return 'https://drive.google.com/file/d/'.urlencode($fileIdentifier).'/view';
    }

    public function delete(string $fileIdentifier): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->delete('https://www.googleapis.com/drive/v3/files/'.urlencode($fileIdentifier).'?supportsAllDrives=true');

            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            Log::error('Google Drive delete error: '.$e->getMessage());

            return false;
        }
    }

    public function exists(string $fileIdentifier): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get('https://www.googleapis.com/drive/v3/files/'.urlencode($fileIdentifier).'?supportsAllDrives=true');

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function name(): string
    {
        return 'google_drive';
    }

    /**
     * Resolve nested folder path into Google Drive folder ID, creating folders on the fly if needed.
     */
    public function resolveFolderHierarchy(string $token, string $rootFolderId, string $subPath): string
    {
        $subPath = trim($subPath, '/');
        if (empty($subPath)) {
            return $rootFolderId;
        }

        $segments = array_filter(explode('/', $subPath));
        $currentParentId = $rootFolderId;

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (empty($segment)) {
                continue;
            }

            $currentParentId = $this->findOrCreateFolder($token, $currentParentId, $segment);
        }

        return $currentParentId;
    }

    /**
     * Search or create a folder under a parent folder in Google Drive.
     */
    public function findOrCreateFolder(string $token, string $parentId, string $folderName): string
    {
        $cacheKey = 'gdrive_fld_'.md5($parentId.'_'.$folderName);

        return Cache::remember($cacheKey, 86400, function () use ($token, $parentId, $folderName) {
            // 1. Search existing folder
            $escapedName = str_replace("'", "\\'", $folderName);
            $query = "name = '{$escapedName}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";

            try {
                $searchResp = Http::withToken($token)
                    ->timeout(10)
                    ->get('https://www.googleapis.com/drive/v3/files', [
                        'q' => $query,
                        'fields' => 'files(id, name)',
                        'supportsAllDrives' => 'true',
                        'includeItemsFromAllDrives' => 'true',
                    ]);

                if ($searchResp->successful()) {
                    $files = $searchResp->json('files');
                    if (! empty($files) && isset($files[0]['id'])) {
                        return $files[0]['id'];
                    }
                }

                // 2. Create new folder
                $createResp = Http::withToken($token)
                    ->timeout(10)
                    ->post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', [
                        'name' => $folderName,
                        'mimeType' => 'application/vnd.google-apps.folder',
                        'parents' => [$parentId],
                    ]);

                if ($createResp->successful()) {
                    return $createResp->json('id') ?? $parentId;
                }

                Log::warning('Google Drive create folder failed: '.$createResp->body());

                return $parentId;
            } catch (\Throwable $e) {
                Log::error('Google Drive findOrCreateFolder exception: '.$e->getMessage());

                return $parentId;
            }
        });
    }

    /**
     * Upload binary content via Google Drive REST API v3 multipart upload.
     */
    protected function uploadToDrive(string $token, string $folderId, string $fileName, string $mimeType, string $fileContent): ?string
    {
        $boundary = '-------'.md5(microtime());
        $delimiter = "\r\n--".$boundary."\r\n";
        $closeDelimiter = "\r\n--".$boundary.'--';

        $metadata = [
            'name' => $fileName,
        ];

        if (! empty($folderId)) {
            $metadata['parents'] = [$folderId];
        }

        $body = $delimiter
            .'Content-Type: application/json; charset=UTF-8'."\r\n\r\n"
            .json_encode($metadata)
            .$delimiter
            .'Content-Type: '.$mimeType."\r\n\r\n"
            .$fileContent
            .$closeDelimiter;

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'multipart/related; boundary='.$boundary,
                    'Content-Length' => (string) strlen($body),
                ])
                ->timeout(30)
                ->send('POST', 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true', [
                    'body' => $body,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['id'] ?? null;
            }

            Log::error('Google Drive upload failed: HTTP '.$response->status().' - '.$response->body());

            return null;
        } catch (\Throwable $e) {
            Log::error('Google Drive upload exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Retrieve or generate OAuth2 access token (via OAuth Refresh Token or Service Account RS256 JWT).
     */
    public function getAccessToken(): ?string
    {
        // 1. If OAuth 2.0 Refresh Token is configured (for personal @gmail.com accounts)
        if (! empty($this->refreshToken) && ! empty($this->clientId) && ! empty($this->clientSecret)) {
            return Cache::remember('gdrive_oauth_access_token', 3300, function () {
                $response = Http::asForm()
                    ->timeout(10)
                    ->post('https://oauth2.googleapis.com/token', [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'refresh_token' => $this->refreshToken,
                        'grant_type' => 'refresh_token',
                    ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('Google OAuth2 refresh token exchange failed: '.$response->body());

                return null;
            });
        }

        // 2. If Service Account is configured (for Google Workspace Shared Drives)
        return Cache::remember('gdrive_service_access_token', 3300, function () {
            if (! $this->isConfigured()) {
                return null;
            }

            $keyData = json_decode((string) file_get_contents($this->resolvePath($this->serviceAccountKeyPath)), true);
            if (! isset($keyData['client_email'], $keyData['private_key'])) {
                Log::error('Invalid Google Service Account JSON structure.');

                return null;
            }

            $now = time();
            $header = base64_encode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = base64_encode((string) json_encode([
                'iss' => $keyData['client_email'],
                'scope' => 'https://www.googleapis.com/auth/drive',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $header = str_replace(['+', '/', '='], ['-', '_', ''], $header);
            $claim = str_replace(['+', '/', '='], ['-', '_', ''], $claim);
            $signData = $header.'.'.$claim;

            $signature = '';
            $binaryKey = openssl_pkey_get_private($keyData['private_key']);
            if (! $binaryKey || ! openssl_sign($signData, $signature, $binaryKey, OPENSSL_ALGO_SHA256)) {
                Log::error('Failed to sign Google Service Account JWT.');

                return null;
            }

            $jwt = $signData.'.'.str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $response = Http::asForm()
                ->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Google OAuth2 token exchange failed: '.$response->body());

            return null;
        });
    }

    /**
     * Retrieve storage metrics from Google Drive API: account quota & folder statistics.
     *
     * @return array{
     *     configured: bool,
     *     error: ?string,
     *     account: array{
     *         email: ?string,
     *         name: ?string,
     *         total_bytes: int,
     *         used_bytes: int,
     *         free_bytes: int,
     *         usage_percentage: float,
     *         drive_bytes: int,
     *         trash_bytes: int
     *     },
     *     folder: array{
     *         id: string,
     *         name: string,
     *         web_view_link: ?string,
     *         total_files: int,
     *         total_bytes: int
     *     },
     *     cached_at: ?string
     * }
     */
    public function getStorageMetrics(?string $folderId = null, bool $forceRefresh = false): array
    {
        $folderId = $folderId ?: (string) config('services.google_drive.attendance_folder_id', '');

        if (! $this->isConfigured()) {
            return [
                'configured' => false,
                'error' => 'Kredensial Google Drive belum dikonfigurasi.',
                'account' => [
                    'email' => null,
                    'name' => null,
                    'total_bytes' => 0,
                    'used_bytes' => 0,
                    'free_bytes' => 0,
                    'usage_percentage' => 0.0,
                    'drive_bytes' => 0,
                    'trash_bytes' => 0,
                ],
                'folder' => [
                    'id' => $folderId,
                    'name' => 'Unknown',
                    'web_view_link' => null,
                    'total_files' => 0,
                    'total_bytes' => 0,
                ],
                'cached_at' => null,
            ];
        }

        $cacheKey = 'gdrive_storage_metrics_'.md5($folderId);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 600, function () use ($folderId) {
            $token = $this->getAccessToken();
            if (! $token) {
                return [
                    'configured' => true,
                    'error' => 'Gagal mendapatkan Google Drive access token.',
                    'account' => [
                        'email' => null,
                        'name' => null,
                        'total_bytes' => 0,
                        'used_bytes' => 0,
                        'free_bytes' => 0,
                        'usage_percentage' => 0.0,
                        'drive_bytes' => 0,
                        'trash_bytes' => 0,
                    ],
                    'folder' => [
                        'id' => $folderId,
                        'name' => 'Unknown',
                        'web_view_link' => null,
                        'total_files' => 0,
                        'total_bytes' => 0,
                    ],
                    'cached_at' => now()->toIso8601String(),
                ];
            }

            // 1. Fetch Account Storage Quota
            $accountData = [
                'email' => null,
                'name' => null,
                'total_bytes' => 0,
                'used_bytes' => 0,
                'free_bytes' => 0,
                'usage_percentage' => 0.0,
                'drive_bytes' => 0,
                'trash_bytes' => 0,
            ];

            try {
                $aboutResp = Http::withToken($token)
                    ->timeout(10)
                    ->get('https://www.googleapis.com/drive/v3/about?fields=user(displayName,emailAddress),storageQuota(limit,usage,usageInDrive,usageInDriveTrash)');

                if ($aboutResp->successful()) {
                    $about = $aboutResp->json();
                    $quota = $about['storageQuota'] ?? [];
                    $user = $about['user'] ?? [];

                    $totalLimit = isset($quota['limit']) ? (int) $quota['limit'] : 0;
                    $totalUsage = isset($quota['usage']) ? (int) $quota['usage'] : 0;
                    $freeBytes = $totalLimit > 0 ? max(0, $totalLimit - $totalUsage) : 0;
                    $usagePercent = $totalLimit > 0 ? round(($totalUsage / $totalLimit) * 100, 2) : 0.0;

                    $accountData = [
                        'email' => $user['emailAddress'] ?? null,
                        'name' => $user['displayName'] ?? null,
                        'total_bytes' => $totalLimit,
                        'used_bytes' => $totalUsage,
                        'free_bytes' => $freeBytes,
                        'usage_percentage' => $usagePercent,
                        'drive_bytes' => (int) ($quota['usageInDrive'] ?? 0),
                        'trash_bytes' => (int) ($quota['usageInDriveTrash'] ?? 0),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Google Drive getStorageMetrics account error: '.$e->getMessage());
            }

            // 2. Fetch Folder Info & calculate folder file sizes (including subfolders)
            $folderData = [
                'id' => $folderId,
                'name' => 'JUWITA-STORAGE',
                'web_view_link' => ! empty($folderId) ? "https://drive.google.com/drive/folders/{$folderId}" : null,
                'total_files' => 0,
                'total_bytes' => 0,
            ];

            if (! empty($folderId)) {
                try {
                    $folderResp = Http::withToken($token)
                        ->timeout(10)
                        ->get("https://www.googleapis.com/drive/v3/files/{$folderId}?fields=id,name,webViewLink&supportsAllDrives=true");

                    if ($folderResp->successful()) {
                        $fInfo = $folderResp->json();
                        $folderData['name'] = $fInfo['name'] ?? 'Folder';
                        $folderData['web_view_link'] = $fInfo['webViewLink'] ?? $folderData['web_view_link'];
                    }

                    $folderStats = $this->calculateFolderStats($token, $folderId);
                    $folderData['total_files'] = $folderStats['total_files'];
                    $folderData['total_bytes'] = $folderStats['total_bytes'];
                } catch (\Throwable $e) {
                    Log::error('Google Drive getStorageMetrics folder error: '.$e->getMessage());
                }
            }

            return [
                'configured' => true,
                'error' => null,
                'account' => $accountData,
                'folder' => $folderData,
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Recursively calculate total files count and bytes in folder.
     */
    protected function calculateFolderStats(string $token, string $rootFolderId): array
    {
        $totalFiles = 0;
        $totalBytes = 0;
        $foldersToScan = [$rootFolderId];
        $scannedFolders = [];

        while (! empty($foldersToScan) && count($scannedFolders) < 100) {
            $currentParent = array_shift($foldersToScan);
            if (isset($scannedFolders[$currentParent])) {
                continue;
            }
            $scannedFolders[$currentParent] = true;

            $pageToken = null;
            do {
                $params = [
                    'q' => "'{$currentParent}' in parents and trashed = false",
                    'fields' => 'nextPageToken,files(id,mimeType,size)',
                    'pageSize' => 1000,
                    'supportsAllDrives' => 'true',
                    'includeItemsFromAllDrives' => 'true',
                ];
                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $resp = Http::withToken($token)->timeout(15)->get('https://www.googleapis.com/drive/v3/files', $params);
                if (! $resp->successful()) {
                    break;
                }

                $data = $resp->json();
                $items = $data['files'] ?? [];

                foreach ($items as $item) {
                    if (($item['mimeType'] ?? '') === 'application/vnd.google-apps.folder') {
                        $foldersToScan[] = $item['id'];
                    } else {
                        $totalFiles++;
                        $totalBytes += (int) ($item['size'] ?? 0);
                    }
                }

                $pageToken = $data['nextPageToken'] ?? null;
            } while ($pageToken && $totalFiles < 50000);
        }

        return [
            'total_files' => $totalFiles,
            'total_bytes' => $totalBytes,
        ];
    }

    /**
     * Get paginated files inside folder for display.
     */
    public function getFolderFiles(string $folderId, int $pageSize = 20, ?string $pageToken = null, ?string $search = null): array
    {
        if (! $this->isConfigured()) {
            return ['files' => [], 'nextPageToken' => null];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['files' => [], 'nextPageToken' => null];
        }

        $query = "'{$folderId}' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed = false";
        if (! empty($search)) {
            $escaped = str_replace("'", "\\'", $search);
            $query .= " and name contains '{$escaped}'";
        }

        $params = [
            'q' => $query,
            'fields' => 'nextPageToken,files(id,name,mimeType,size,createdTime,modifiedTime,webViewLink,thumbnailLink,iconLink)',
            'pageSize' => $pageSize,
            'orderBy' => 'createdTime desc',
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $resp = Http::withToken($token)->timeout(15)->get('https://www.googleapis.com/drive/v3/files', $params);
            if ($resp->successful()) {
                return [
                    'files' => $resp->json('files') ?? [],
                    'nextPageToken' => $resp->json('nextPageToken'),
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Google Drive getFolderFiles error: '.$e->getMessage());
        }

        return ['files' => [], 'nextPageToken' => null];
    }

    protected function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[a-zA-Z]:\\\\/', $path))) {
            return $path;
        }

        return base_path($path);
    }
}

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
                $fileId = $this->uploadToDrive($token, $directory, $fileName, $mimeType, $fileContent);
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

    protected function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[a-zA-Z]:\\\\/', $path))) {
            return $path;
        }

        return base_path($path);
    }
}

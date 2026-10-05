<?php

namespace App\Jobs;

use App\Models\AttendanceEvent;
use App\Services\Document\GoogleDriveStorageProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadAttendancePhotoToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $attendanceEventId,
        public string $tempLocalPath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleDriveStorageProvider $driveProvider): void
    {
        $event = AttendanceEvent::find($this->attendanceEventId);
        if (! $event) {
            Log::info("AttendanceEvent #{$this->attendanceEventId} not found, skipping Google Drive upload.");

            return;
        }

        $disk = Storage::disk('public')->exists($this->tempLocalPath) ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($this->tempLocalPath)) {
            Log::warning("Local photo file not found at [{$this->tempLocalPath}], skipping upload for event #{$this->attendanceEventId}.");

            return;
        }

        $fileContent = Storage::disk($disk)->get($this->tempLocalPath);

        $event->loadMissing(['attendance.employee', 'attendance.doctor']);
        $date = $event->attendance?->date ? $event->attendance->date->format('Y-m-d') : now()->format('Y-m-d');
        $employee = $event->attendance?->employee;
        $doctor = $event->attendance?->doctor;
        $name = $employee?->name ?? $doctor?->name ?? 'Pegawai';
        $nipOrId = $employee?->nip ?: ($doctor?->str_number ?: 'ID_'.$event->attendance_id);
        $personFolder = trim(preg_replace('/[^\w\s\-\.]+/u', '', "{$nipOrId} - {$name}"));

        $subDirectory = "{$date}/{$personFolder}";
        $timeStr = $event->recorded_at ? $event->recorded_at->format('His') : now()->format('His');
        $customFileName = "{$event->event_type}_{$timeStr}.jpg";

        $folderId = (string) config('services.google_drive.attendance_folder_id', '');

        // If Google Drive is configured or running in non-production mock
        if ($driveProvider->isConfigured() || ! app()->environment('production')) {
            try {
                $result = $driveProvider->store(
                    directory: $subDirectory,
                    file: $fileContent,
                    customFileName: $customFileName,
                    options: ['root_folder_id' => $folderId]
                );

                // Update database with Google Drive file identifier
                $event->update([
                    'photo_path' => $result->fileIdentifier,
                    'storage_provider' => 'google_drive',
                ]);

                // Delete temporary local file from server disk (reclaims disk space to 0 MB)
                Storage::disk($disk)->delete($this->tempLocalPath);

                Log::info("Attendance photo #{$this->attendanceEventId} successfully offloaded to Google Drive [ID: {$result->fileIdentifier}] and local file purged.");
            } catch (\Throwable $e) {
                Log::error("Failed to upload attendance photo #{$this->attendanceEventId} to Google Drive: ".$e->getMessage());

                // Release back to queue for retry if retries remaining
                if ($this->attempts() < $this->tries) {
                    $this->release(30);
                }
            }
        } else {
            Log::info("Google Drive is not yet configured. Photo for event #{$this->attendanceEventId} kept temporarily on local disk at [{$this->tempLocalPath}].");
        }
    }
}

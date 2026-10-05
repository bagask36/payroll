<?php

namespace App\Livewire\Attendance;

use App\Contracts\FaceAiService;
use App\Jobs\UploadAttendancePhotoToGoogleDrive;
use App\Models\Attendance;
use App\Models\GeofenceLocation;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Presensi Mandiri - RS Juwita')]
class ClockInOut extends Component
{
    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?int $distanceMeters = null;

    public bool $isWithinGeofence = false;

    public ?string $faceImageBase64 = null;

    public ?Attendance $todayAttendance = null;

    public ?ScheduleAssignment $todaySchedule = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public ?string $challengeType = 'blink'; // blink, turn_left, turn_right

    public ?string $challengeLabel = 'Kedipkan mata Anda 2 kali';

    public ?string $challengeToken = null;

    public bool $livenessPassed = false;

    public function mount(): void
    {
        $this->authorize('checkInOut', Attendance::class);
        $this->loadTodayContext();
        $this->generateChallenge();
    }

    public function generateChallenge(): void
    {
        $challenges = [
            'blink' => 'Kedipkan mata Anda 2 kali perlahan',
            'turn_left' => 'Tengokkan kepala sedikit ke kiri',
            'turn_right' => 'Tengokkan kepala sedikit ke kanan',
            'nod' => 'Anggukkan kepala perlahan',
        ];

        $keys = array_keys($challenges);
        $this->challengeType = $keys[array_rand($keys)];
        $this->challengeLabel = $challenges[$this->challengeType];
        $this->challengeToken = bin2hex(random_bytes(16));
        $this->livenessPassed = false;
        $this->faceImageBase64 = null;
    }

    public function loadTodayContext(): void
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $employee = $user?->employee;
        $doctor = $user?->doctor;

        if ($employee) {
            $this->todaySchedule = ScheduleAssignment::with('shift')
                ->where('employee_id', $employee->id)
                ->whereDate('schedule_date', $today)
                ->first();

            // Auto-fallback default regular schedule for non-shift personnel
            if (! $this->todaySchedule && $employee->isNonShiftWorker()) {
                $regularShift = Shift::where('type', 'non_shift')->orWhere('code', 'NON-SHIFT')->where('is_active', true)->first();
                if ($regularShift) {
                    $this->todaySchedule = ScheduleAssignment::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'schedule_date' => $today,
                        ],
                        [
                            'shift_id' => $regularShift->id,
                            'status' => 'scheduled',
                            'notes' => 'Jadwal Reguler Otomatis (Non-Shift)',
                            'created_by' => $user->id,
                        ]
                    );
                    $this->todaySchedule->load('shift');
                }
            }

            $this->todayAttendance = Attendance::with(['events', 'scheduleAssignment.shift'])
                ->where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();
        } elseif ($doctor) {
            $this->todaySchedule = ScheduleAssignment::with('shift')
                ->where('doctor_id', $doctor->id)
                ->whereDate('schedule_date', $today)
                ->first();

            $this->todayAttendance = Attendance::with(['events', 'scheduleAssignment.shift'])
                ->where('doctor_id', $doctor->id)
                ->whereDate('date', $today)
                ->first();
        }
    }

    public function updateCoordinates(float $lat, float $lon): void
    {
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->resetMessages();

        $activeGeofence = $this->getActiveGeofence();

        if ($activeGeofence) {
            $this->distanceMeters = $activeGeofence->calculateDistanceMeters($lat, $lon);
            $this->isWithinGeofence = $activeGeofence->isWithinGeofence($lat, $lon);
        } else {
            // Default fallback if no geofence defined
            $this->distanceMeters = 0;
            $this->isWithinGeofence = true;
        }
    }

    public function clockIn(FaceAiService $faceAiService, AuditService $auditService): void
    {
        $this->authorize('checkInOut', Attendance::class);
        $this->resetMessages();

        $user = Auth::user();
        $now = Carbon::now();
        $today = Carbon::today()->toDateString();

        if ($this->todayAttendance && $this->todayAttendance->clock_in) {
            $this->errorMessage = 'Anda telah melakukan Clock In untuk hari ini.';

            return;
        }

        // Validate location
        if ($this->latitude === null || $this->longitude === null) {
            $this->errorMessage = 'Lokasi GPS belum terdeteksi. Izinkan akses GPS pada peramban Anda.';

            return;
        }

        $activeGeofence = $this->getActiveGeofence();
        $isWithin = $activeGeofence ? $activeGeofence->isWithinGeofence($this->latitude, $this->longitude) : true;
        $distance = $activeGeofence ? $activeGeofence->calculateDistanceMeters($this->latitude, $this->longitude) : 0;

        if (! $isWithin) {
            $this->errorMessage = "Posisi Anda berada di luar radius rumah sakit ({$distance} meter dari perimeter). Clock In ditolak.";

            return;
        }

        // Face AI verification
        $faceMatched = true;
        $similarity = 0.95;
        $liveness = 0.98;

        if ($this->faceImageBase64) {
            $livenessResult = $faceAiService->checkLiveness($this->faceImageBase64);
            if (! $livenessResult->isLive) {
                $this->errorMessage = 'Verifikasi keaslian wajah gagal: '.$livenessResult->errorMessage;

                return;
            }

            $referenceVector = $user?->employee?->face_biometric_vector ?? [];
            if (! empty($referenceVector)) {
                $verification = $faceAiService->verify($this->faceImageBase64, $referenceVector);
                if (! $verification->isMatched) {
                    $this->errorMessage = 'Wajah tidak cocok dengan data biometrik terdaftar (kemiripan: '.round($verification->similarityScore * 100).'%).';

                    return;
                }
                $faceMatched = $verification->isMatched;
                $similarity = $verification->similarityScore;
            }

            $liveness = $livenessResult->confidenceScore;
        }

        $employeeId = $user?->employee?->id;
        $doctorId = $user?->doctor?->id;

        $attendance = Attendance::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'doctor_id' => $doctorId,
                'date' => $today,
            ],
            [
                'schedule_assignment_id' => $this->todaySchedule?->id,
                'status' => 'present',
            ]
        );

        $photoPath = null;
        if ($this->faceImageBase64) {
            $photoPath = $this->storeCompressedPhoto($this->faceImageBase64, 'clock_in', $attendance->id);
        }

        $clockInEvent = $attendance->recordClockIn($now, [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_meters' => $distance,
            'is_within_geofence' => true,
            'device_info' => request()->userAgent(),
            'photo_path' => $photoPath,
            'storage_provider' => 'local',
            'face_matched' => $faceMatched,
            'face_similarity' => $similarity,
            'liveness_score' => $liveness,
        ]);

        if ($photoPath) {
            UploadAttendancePhotoToGoogleDrive::dispatch($clockInEvent->id, $photoPath);
        }

        $auditService->log(
            action: 'attendance.clock_in',
            auditable: $attendance,
            oldValues: [],
            newValues: [
                'time' => $now->toDateTimeString(),
                'distance_meters' => $distance,
                'status' => $attendance->status,
                'face_matched' => $faceMatched,
                'liveness_score' => $liveness,
                'has_photo' => $photoPath !== null,
            ]
        );

        $this->statusMessage = 'Clock In berhasil dicatat pada '.$now->format('H:i:s').' (Status: '.strtoupper($attendance->status).').';
        $this->generateChallenge();
        $this->loadTodayContext();
    }

    public function clockOut(FaceAiService $faceAiService, AuditService $auditService): void
    {
        $this->authorize('checkInOut', Attendance::class);
        $this->resetMessages();

        $now = Carbon::now();

        if (! $this->todayAttendance || ! $this->todayAttendance->clock_in) {
            $this->errorMessage = 'Anda belum melakukan Clock In hari ini.';

            return;
        }

        if ($this->todayAttendance->clock_out) {
            $this->errorMessage = 'Anda telah menyelesaikan Clock Out untuk hari ini.';

            return;
        }

        $activeGeofence = $this->getActiveGeofence();
        $isWithin = $activeGeofence ? $activeGeofence->isWithinGeofence($this->latitude ?? 0.0, $this->longitude ?? 0.0) : true;
        $distance = $activeGeofence ? $activeGeofence->calculateDistanceMeters($this->latitude ?? 0.0, $this->longitude ?? 0.0) : 0;

        $faceMatched = true;
        $similarity = 0.95;
        $liveness = 0.98;

        if ($this->faceImageBase64) {
            $livenessResult = $faceAiService->checkLiveness($this->faceImageBase64);
            if (! $livenessResult->isLive) {
                $this->errorMessage = 'Verifikasi keaslian wajah gagal: '.$livenessResult->errorMessage;

                return;
            }

            $user = Auth::user();
            $referenceVector = $user?->employee?->face_biometric_vector ?? [];
            if (! empty($referenceVector)) {
                $verification = $faceAiService->verify($this->faceImageBase64, $referenceVector);
                if (! $verification->isMatched) {
                    $this->errorMessage = 'Wajah tidak cocok dengan data biometrik terdaftar (kemiripan: '.round($verification->similarityScore * 100).'%).';

                    return;
                }
                $faceMatched = $verification->isMatched;
                $similarity = $verification->similarityScore;
            }

            $liveness = $livenessResult->confidenceScore;
        }

        $photoPath = null;
        if ($this->faceImageBase64) {
            $photoPath = $this->storeCompressedPhoto($this->faceImageBase64, 'clock_out', $this->todayAttendance->id);
        }

        $clockOutEvent = $this->todayAttendance->recordClockOut($now, [
            'latitude' => $this->latitude ?? 0.0,
            'longitude' => $this->longitude ?? 0.0,
            'distance_meters' => $distance,
            'is_within_geofence' => $isWithin,
            'device_info' => request()->userAgent(),
            'photo_path' => $photoPath,
            'storage_provider' => 'local',
            'face_matched' => $faceMatched,
            'face_similarity' => $similarity,
            'liveness_score' => $liveness,
        ]);

        if ($photoPath) {
            UploadAttendancePhotoToGoogleDrive::dispatch($clockOutEvent->id, $photoPath);
        }

        $auditService->log(
            action: 'attendance.clock_out',
            auditable: $this->todayAttendance,
            oldValues: [],
            newValues: [
                'time' => $now->toDateTimeString(),
                'work_duration_minutes' => $this->todayAttendance->work_duration_minutes,
            ]
        );

        $this->statusMessage = 'Clock Out berhasil dicatat pada '.$now->format('H:i:s').'. Total durasi kerja: '.$this->todayAttendance->work_duration_minutes.' menit.';
        $this->loadTodayContext();
    }

    /**
     * Decode base64 image, resize to max 640px, and compress with JPEG 75% quality.
     */
    protected function storeCompressedPhoto(string $base64Data, string $prefix, int $attendanceId): ?string
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            }

            $decoded = base64_decode($base64Data, true);
            if ($decoded === false) {
                return null;
            }

            $fileName = sprintf('%s_%d_%s_%s.jpg', $prefix, $attendanceId, now()->format('YmdHis'), bin2hex(random_bytes(4)));
            $relativePath = 'attendance-photos/'.now()->format('Y/m').'/'.$fileName;

            $sourceImage = @imagecreatefromstring($decoded);
            if (! $sourceImage) {
                Storage::disk('public')->put($relativePath, $decoded);

                return $relativePath;
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);

            // Maximum dimension 640px for mobile bandwidth efficiency
            $maxDimension = 640;
            if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                if ($origWidth > $origHeight) {
                    $newWidth = $maxDimension;
                    $newHeight = (int) round(($origHeight / $origWidth) * $maxDimension);
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = (int) round(($origWidth / $origHeight) * $maxDimension);
                }

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($sourceImage);
                $finalImage = $resizedImage;
            } else {
                $finalImage = $sourceImage;
            }

            ob_start();
            imagejpeg($finalImage, null, 75);
            $compressedData = ob_get_clean();
            imagedestroy($finalImage);

            if ($compressedData !== false) {
                Storage::disk('public')->put($relativePath, $compressedData);

                return $relativePath;
            }

            Storage::disk('public')->put($relativePath, $decoded);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('Failed to compress and save attendance photo: '.$e->getMessage());

            return null;
        }
    }

    protected ?GeofenceLocation $activeGeofence = null;

    protected bool $hasLoadedActiveGeofence = false;

    protected function getActiveGeofence(): ?GeofenceLocation
    {
        if (! $this->hasLoadedActiveGeofence) {
            // Evict any corrupted or legacy serialized cache entry that produced __PHP_Incomplete_Class
            Cache::forget('active_geofence_location');

            $this->activeGeofence = GeofenceLocation::active()->first();
            $this->hasLoadedActiveGeofence = true;
        }

        return $this->activeGeofence;
    }

    protected function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.attendance.clock-in-out');
    }
}

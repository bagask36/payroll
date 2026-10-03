<?php

namespace App\Models;

use Database\Factories\AttendanceEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEvent extends Model
{
    /** @use HasFactory<AttendanceEventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attendance_id',
        'event_type',
        'recorded_at',
        'latitude',
        'longitude',
        'distance_meters',
        'is_within_geofence',
        'device_info',
        'photo_path',
        'face_matched',
        'face_similarity',
        'liveness_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'distance_meters' => 'integer',
            'is_within_geofence' => 'boolean',
            'face_matched' => 'boolean',
            'face_similarity' => 'float',
            'liveness_score' => 'float',
        ];
    }

    /**
     * Parent daily attendance record.
     *
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get accessible URL for stored attendance photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }
}

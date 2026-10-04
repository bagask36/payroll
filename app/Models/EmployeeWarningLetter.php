<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeWarningLetter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_warning_letters';

    protected $fillable = [
        'employee_id',
        'warning_letter_type_id',
        'letter_number',
        'issued_date',
        'valid_from',
        'valid_until',
        'violation_reason',
        'authorized_by',
        'status',
        'revocation_reason',
        'revoked_at',
        'revoked_by',
        'document_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Relationship to employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship to warning letter type master.
     */
    public function warningLetterType(): BelongsTo
    {
        return $this->belongsTo(WarningLetterType::class, 'warning_letter_type_id');
    }

    /**
     * Relationship to the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to the user who revoked the warning letter.
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Scope for currently active warning letters (active status and current date within validity period).
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where('status', 'active')
            ->whereDate('valid_from', '<=', $today)
            ->whereDate('valid_until', '>=', $today);
    }

    /**
     * Scope for expired warning letters.
     */
    public function scopeExpired(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where('status', 'active')
            ->whereDate('valid_until', '<', $today);
    }

    /**
     * Determine computed active status considering current date.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'revoked') {
            return 'revoked';
        }

        if ($this->valid_until && $this->valid_until->isPast() && ! $this->valid_until->isToday()) {
            return 'expired';
        }

        return 'active';
    }
}

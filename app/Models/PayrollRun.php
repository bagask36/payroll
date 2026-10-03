<?php

namespace App\Models;

use Database\Factories\PayrollRunFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'payroll_period_id',
        'run_date',
        'status',
        'total_gross_pay',
        'total_deductions',
        'total_net_pay',
        'processed_by',
        'finalized_by',
        'finalized_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'total_gross_pay' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net_pay' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PayrollRun $run) {
            if (empty($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (PayrollRun $run) {
            if ($run->getOriginal('status') === 'finalized' && $run->status !== 'paid') {
                throw new DomainException('Immutability violation: Finalized payroll runs are strictly immutable.');
            }
        });

        static::deleting(function (PayrollRun $run) {
            if ($run->isFinalized()) {
                throw new DomainException('Immutability violation: Finalized payroll runs cannot be deleted.');
            }
        });
    }

    /**
     * Associated period.
     *
     * @return BelongsTo<PayrollPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /**
     * Administrator who executed the calculation.
     *
     * @return BelongsTo<User, $this>
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * HR Manager who approved and locked the payroll.
     *
     * @return BelongsTo<User, $this>
     */
    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Itemized employee payslips.
     *
     * @return HasMany<PayrollItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Check if this payroll run is finalized and locked.
     */
    public function isFinalized(): bool
    {
        return in_array($this->status, ['finalized', 'paid'], true);
    }

    /**
     * Finalize and lock this payroll run.
     */
    public function finalize(User $finalizer): void
    {
        $this->update([
            'status' => 'finalized',
            'finalized_by' => $finalizer->id,
            'finalized_at' => Carbon::now(),
        ]);
    }
}

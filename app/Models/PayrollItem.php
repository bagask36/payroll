<?php

namespace App\Models;

use Database\Factories\PayrollItemFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    /** @use HasFactory<PayrollItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_salary',
        'allowances',
        'deductions',
        'bpjs_tk_employee',
        'bpjs_tk_employer',
        'bpjs_kes_employee',
        'bpjs_kes_employer',
        'pph21_tax',
        'net_salary',
        'payment_status',
        'bank_name',
        'bank_account_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
            'bpjs_tk_employee' => 'decimal:2',
            'bpjs_tk_employer' => 'decimal:2',
            'bpjs_kes_employee' => 'decimal:2',
            'bpjs_kes_employer' => 'decimal:2',
            'pph21_tax' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'bank_account_number' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (PayrollItem $item) {
            if ($item->run && $item->run->isFinalized()) {
                throw new DomainException('Immutability violation: Finalized payroll items cannot be modified.');
            }
        });

        static::deleting(function (PayrollItem $item) {
            if ($item->run && $item->run->isFinalized()) {
                throw new DomainException('Immutability violation: Finalized payroll items cannot be deleted.');
            }
        });
    }

    /**
     * Parent batch payroll run.
     *
     * @return BelongsTo<PayrollRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /**
     * Associated employee recipient.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Itemized compensation breakdown components.
     *
     * @return HasMany<PayrollComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(PayrollComponent::class);
    }
}

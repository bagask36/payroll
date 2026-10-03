<?php

namespace App\Models;

use Database\Factories\BpjsRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpjsRule extends Model
{
    /** @use HasFactory<BpjsRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'program_name',
        'employee_rate',
        'employer_rate',
        'wage_ceiling',
        'wage_floor',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employee_rate' => 'float',
            'employer_rate' => 'float',
            'wage_ceiling' => 'decimal:2',
            'wage_floor' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Compute capped wage basis according to regulatory floor and ceiling.
     */
    public function getCalculationBasis(float $salary): float
    {
        $basis = $salary;

        if ($this->wage_floor !== null && $basis < (float) $this->wage_floor) {
            $basis = (float) $this->wage_floor;
        }

        if ($this->wage_ceiling !== null && $basis > (float) $this->wage_ceiling) {
            $basis = (float) $this->wage_ceiling;
        }

        return $basis;
    }

    /**
     * Calculate employee contribution amount.
     */
    public function calculateEmployeeContribution(float $salary): float
    {
        $basis = $this->getCalculationBasis($salary);

        return round($basis * $this->employee_rate, 2);
    }

    /**
     * Calculate employer contribution amount.
     */
    public function calculateEmployerContribution(float $salary): float
    {
        $basis = $this->getCalculationBasis($salary);

        return round($basis * $this->employer_rate, 2);
    }

    /**
     * Scope to active regulatory rules.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

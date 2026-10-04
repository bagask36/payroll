<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type', // allowance, deduction
        'is_taxable',
        'is_fixed',
        'default_amount',
        'description',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_fixed' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_fixed' => 'boolean',
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Assigned employee instances.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EmployeeSalaryComponent, $this>
     */
    public function employeeAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'salary_component_id');
    }
}

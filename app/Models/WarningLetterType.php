<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarningLetterType extends Model
{
    use HasFactory;

    protected $table = 'warning_letter_types';

    protected $fillable = [
        'code',
        'name',
        'level',
        'validity_months',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'validity_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relationship to employee warning letters issued under this type.
     */
    public function employeeWarningLetters(): HasMany
    {
        return $this->hasMany(EmployeeWarningLetter::class, 'warning_letter_type_id');
    }

    /**
     * Scope to active warning letter types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

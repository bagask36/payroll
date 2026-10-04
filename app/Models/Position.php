<?php

namespace App\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'unit_id',
        'reports_to_id',
        'title',
        'grade_level',
        'default_basic_salary',
        'default_positional_allowance',
        'default_contract_number',
        'document_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_basic_salary' => 'decimal:2',
            'default_positional_allowance' => 'decimal:2',
        ];
    }

    /**
     * Department association (for direct departmental positions like Directors, Division Heads).
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Unit association (for unit-level operational positions like Ward In-charge, Nurses).
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Direct supervisor position in the organizational hierarchy.
     *
     * @return BelongsTo<Position, $this>
     */
    public function supervisorPosition(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_id');
    }

    /**
     * Subordinate positions reporting directly to this position.
     *
     * @return HasMany<Position, $this>
     */
    public function subordinatePositions(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to_id');
    }

    /**
     * Master SK reference document establishing this position.
     *
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}

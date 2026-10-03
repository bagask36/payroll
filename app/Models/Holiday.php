<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'holiday_date',
        'name',
        'is_collective_leave',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_collective_leave' => 'boolean',
        ];
    }

    /**
     * Check if a given date is a national holiday or collective leave.
     */
    public static function isHoliday(CarbonInterface|string $date): bool
    {
        $formattedDate = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return static::whereDate('holiday_date', $formattedDate)->exists();
    }

    /**
     * Check if a given date is specifically a collective leave day (cuti bersama).
     */
    public static function isCollectiveLeave(CarbonInterface|string $date): bool
    {
        $formattedDate = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return static::whereDate('holiday_date', $formattedDate)->where('is_collective_leave', true)->exists();
    }
}

<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view_all')
            || $user->hasPermission('attendance.view_team')
            || $user->hasPermission('attendance.view_own');
    }

    /**
     * Determine whether the user can view the specific attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if ($attendance->employee?->user_id === $user->id || $attendance->doctor?->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('attendance.view_all')
            || $user->hasPermission('attendance.view_team');
    }

    /**
     * Determine whether the user can perform check-in and check-out clocking.
     */
    public function checkInOut(User $user): bool
    {
        return $user->hasPermission('attendance.check_in_out');
    }

    /**
     * Determine whether the user can request attendance adjustments.
     */
    public function requestAdjustment(User $user): bool
    {
        return $user->hasPermission('attendance.adjust_request');
    }

    /**
     * Determine whether the user can approve manual attendance adjustments.
     */
    public function approveAdjustment(User $user): bool
    {
        return $user->hasPermission('attendance.adjust_approve');
    }
}

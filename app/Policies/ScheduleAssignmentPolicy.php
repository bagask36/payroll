<?php

namespace App\Policies;

use App\Models\ScheduleAssignment;
use App\Models\User;

class ScheduleAssignmentPolicy
{
    /**
     * Determine whether the user can view duty schedules.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('schedule.view');
    }

    /**
     * Determine whether the user can view the specific duty schedule assignment.
     */
    public function view(User $user, ScheduleAssignment $assignment): bool
    {
        if ($assignment->employee?->user_id === $user->id || $assignment->doctor?->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('schedule.view');
    }

    /**
     * Determine whether the user can create schedule assignments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('schedule.assign_all') || $user->hasPermission('schedule.assign_team');
    }

    /**
     * Determine whether the user can adjust or update schedule assignments.
     */
    public function update(User $user, ScheduleAssignment $assignment): bool
    {
        return $user->hasPermission('schedule.assign_all') || $user->hasPermission('schedule.assign_team');
    }

    /**
     * Determine whether the user can cancel or delete schedule assignments.
     */
    public function delete(User $user, ScheduleAssignment $assignment): bool
    {
        return $user->hasPermission('schedule.assign_all') || $user->hasPermission('schedule.assign_team');
    }
}

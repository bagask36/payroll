<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view leave applications.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leave.view_all')
            || $user->hasPermission('leave.view_team')
            || $user->hasPermission('leave.view_own');
    }

    /**
     * Determine whether the user can view the specific leave request.
     */
    public function view(User $user, LeaveRequest $request): bool
    {
        if ($request->isApplicant($user)) {
            return true;
        }

        return $user->hasPermission('leave.view_all')
            || $user->hasPermission('leave.view_team');
    }

    /**
     * Determine whether the user can submit a leave request.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('leave.create_own');
    }

    /**
     * Determine whether the user can cancel their own leave request.
     */
    public function cancel(User $user, LeaveRequest $request): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $request->isApplicant($user) && $user->hasPermission('leave.cancel_own');
    }

    /**
     * Determine whether the user can grant Tier 1 approval.
     */
    public function approveTier1(User $user, LeaveRequest $request): bool
    {
        if ($request->isApplicant($user) && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('leave.approve_tier1');
    }

    /**
     * Determine whether the user can grant Tier 2 final approval.
     */
    public function approveTier2(User $user, LeaveRequest $request): bool
    {
        if ($request->isApplicant($user) && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('leave.approve_tier2');
    }
}

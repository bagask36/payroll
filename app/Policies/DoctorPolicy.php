<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    /**
     * Determine whether the user can view the doctor directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('doctor.view');
    }

    /**
     * Determine whether the user can view the doctor profile.
     */
    public function view(User $user, Doctor $doctor): bool
    {
        return $user->hasPermission('doctor.view');
    }

    /**
     * Determine whether the user can view sensitive professional license credentials (STR/SIP expiration).
     */
    public function viewCredentials(User $user, Doctor $doctor): bool
    {
        if ($doctor->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('doctor.view_credentials');
    }

    /**
     * Determine whether the user can register a new physician.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('doctor.create');
    }

    /**
     * Determine whether the user can update doctor profile information.
     */
    public function update(User $user, Doctor $doctor): bool
    {
        if ($doctor->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('doctor.update');
    }

    /**
     * Determine whether the user can manage or renew STR/SIP credentials.
     */
    public function manageCredentials(User $user, Doctor $doctor): bool
    {
        if ($doctor->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('doctor.manage_credentials');
    }

    /**
     * Determine whether the user can deactivate a physician.
     */
    public function deactivate(User $user, Doctor $doctor): bool
    {
        return $user->hasPermission('doctor.deactivate');
    }

    /**
     * Determine whether the user can soft-delete a doctor.
     */
    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->hasPermission('doctor.deactivate');
    }

    /**
     * Determine whether the user can upload compliance documents for this doctor.
     */
    public function uploadDocument(User $user, Doctor $doctor): bool
    {
        if ($doctor->user_id === $user->id) {
            return $user->hasPermission('document.upload_own') || $user->hasPermission('doctor.manage_credentials');
        }

        return $user->hasPermission('doctor.manage_credentials') || $user->hasPermission('document.upload_all');
    }

    /**
     * Determine whether the user can associate an organic doctor with an employee record.
     */
    public function linkEmployee(User $user, Doctor $doctor): bool
    {
        return $user->hasPermission('doctor.link_employee') || $user->hasPermission('doctor.update');
    }
}

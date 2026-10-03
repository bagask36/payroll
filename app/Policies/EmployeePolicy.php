<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employee profiles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('employee.view');
    }

    /**
     * Determine whether the user can view the specific employee profile.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($employee->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('employee.view');
    }

    /**
     * Determine whether the user can view sensitive PII (NIK KTP, address, etc.).
     */
    public function viewSensitive(User $user, Employee $employee): bool
    {
        if ($employee->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('employee.view_sensitive');
    }

    /**
     * Determine whether the user can view employee salary records.
     */
    public function viewSalary(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employee.view_salary');
    }

    /**
     * Determine whether the user can view employee bank account information.
     */
    public function viewBank(User $user, Employee $employee): bool
    {
        if ($employee->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('employee.view_bank');
    }

    /**
     * Determine whether the user can register a new employee.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('employee.create');
    }

    /**
     * Determine whether the user can update general bio data.
     */
    public function update(User $user, Employee $employee): bool
    {
        if ($employee->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('employee.update');
    }

    /**
     * Determine whether the user can manage (enroll/remove) biometric face data.
     */
    public function manageBiometrics(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employee.update');
    }

    /**
     * Determine whether the user can update employment contracts or status.
     */
    public function updateEmployment(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employee.update_employment');
    }

    /**
     * Determine whether the user can update base salary.
     */
    public function updateSalary(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employee.update_salary');
    }

    /**
     * Determine whether the user can deactivate or terminate an employee.
     */
    public function deactivate(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employee.deactivate');
    }

    /**
     * Determine whether the user can soft-delete an employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employee.deactivate');
    }
}

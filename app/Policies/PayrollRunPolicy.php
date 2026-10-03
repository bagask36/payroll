<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    /**
     * Determine whether the user can view batch payroll runs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view_all');
    }

    /**
     * Determine whether the user can view the specific payroll run.
     */
    public function view(User $user, PayrollRun $run): bool
    {
        return $user->hasPermission('payroll.view_all');
    }

    /**
     * Determine whether the user can calculate payroll simulations.
     */
    public function calculate(User $user): bool
    {
        return $user->hasPermission('payroll.calculate');
    }

    /**
     * Determine whether the user can finalize and lock payroll disbursements.
     */
    public function finalize(User $user, PayrollRun $run): bool
    {
        return $user->hasPermission('payroll.finalize');
    }

    /**
     * Determine whether the user can export bank disbursement files.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('payroll.export_bank') || $user->hasPermission('payroll.export');
    }
}

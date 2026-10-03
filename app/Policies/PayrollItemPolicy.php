<?php

namespace App\Policies;

use App\Models\PayrollItem;
use App\Models\User;

class PayrollItemPolicy
{
    /**
     * Determine whether the user can view the specific employee payslip item.
     */
    public function view(User $user, PayrollItem $item): bool
    {
        if ($item->employee?->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('payroll.view_all');
    }
}

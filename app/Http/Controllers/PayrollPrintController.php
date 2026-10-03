<?php

namespace App\Http\Controllers;

use App\Models\PayrollItem;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PayrollPrintController extends Controller
{
    public function print(int $id, Request $request, AuditService $auditService)
    {
        $item = PayrollItem::with([
            'run.period',
            'employee.unit',
            'employee.position',
        ])->findOrFail($id);

        // Authorize view via PayrollItemPolicy
        Gate::authorize('view', $item);

        $auditService->log(
            action: 'payroll.payslip_printed',
            auditable: $item,
            newValues: [
                'employee_id' => $item->employee_id,
                'period_name' => $item->run?->period?->name,
                'net_salary' => $item->net_salary,
            ]
        );

        return view('payroll.payslip-print', [
            'item' => $item,
        ]);
    }
}

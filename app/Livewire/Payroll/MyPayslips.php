<?php

namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\PayrollItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Slip Gaji Saya - RS Juwita')]
class MyPayslips extends Component
{
    use WithPagination;

    public bool $showDetailModal = false;

    public ?int $selectedItemId = null;

    public function mount(): void
    {
        // Require authenticated user with linked employee
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            abort(403, 'Akun Anda tidak terhubung dengan data pegawai Rumah Sakit.');
        }
    }

    #[On('view-slip-detail')]
    public function viewSlip(int $itemId): void
    {
        $item = PayrollItem::findOrFail($itemId);
        $this->authorize('view', $item);

        $this->selectedItemId = $itemId;
        $this->showDetailModal = true;
    }

    public function render()
    {
        $employee = Employee::where('user_id', Auth::id())->first();

        $payslips = PayrollItem::with(['run.period'])
            ->where('employee_id', $employee?->id)
            ->whereHas('run', function ($q) {
                $q->whereIn('status', ['calculated', 'finalized', 'paid']);
            })
            ->orderByDesc('id')
            ->paginate(10);

        $selectedItem = $this->selectedItemId
            ? PayrollItem::with(['run.period', 'employee.unit', 'employee.position'])->find($this->selectedItemId)
            : null;

        return view('livewire.payroll.my-payslips', [
            'employee' => $employee,
            'payslips' => $payslips,
            'selectedItem' => $selectedItem,
        ]);
    }
}

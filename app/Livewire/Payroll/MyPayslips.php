<?php

namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\PayrollItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Slip Gaji Saya - RS Permata Bunda')]
class MyPayslips extends Component
{
    use WithPagination;

    public bool $isUnlocked = false;

    public string $password = '';

    public bool $showDetailModal = false;

    public ?int $selectedItemId = null;

    public function mount(): void
    {
        // Require authenticated user with linked employee
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            abort(403, 'Akun Anda tidak terhubung dengan data pegawai Rumah Sakit.');
        }

        if (session()->has('payslip_unlocked_until') && session('payslip_unlocked_until') > now()->timestamp) {
            $this->isUnlocked = true;
        }
    }

    public function unlock(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Kata sandi akun wajib diisi.',
        ]);

        $user = Auth::user();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Kata sandi yang Anda masukkan salah.');
            return;
        }

        session(['payslip_unlocked_until' => now()->addMinutes(15)->timestamp]);
        $this->isUnlocked = true;
        $this->password = '';
        $this->resetErrorBag();
    }

    public function lock(): void
    {
        session()->forget('payslip_unlocked_until');
        $this->isUnlocked = false;
        $this->password = '';
        $this->showDetailModal = false;
        $this->selectedItemId = null;
    }

    #[On('view-slip-detail')]
    public function viewSlip(int $itemId): void
    {
        if (! $this->isUnlocked) {
            abort(403, 'Akses slip gaji terkunci. Silakan masukkan kata sandi terlebih dahulu.');
        }

        $item = PayrollItem::findOrFail($itemId);
        $this->authorize('view', $item);

        $this->selectedItemId = $itemId;
        $this->showDetailModal = true;
    }

    public function render()
    {
        $employee = Employee::where('user_id', Auth::id())->first();

        $payslips = null;
        $selectedItem = null;

        if ($this->isUnlocked) {
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
        }

        return view('livewire.payroll.my-payslips', [
            'employee' => $employee,
            'payslips' => $payslips,
            'selectedItem' => $selectedItem,
        ]);
    }
}

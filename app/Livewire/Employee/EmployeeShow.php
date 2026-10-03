<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil Pegawai - RS Juwita')]
class EmployeeShow extends Component
{
    public Employee $employee;

    public string $activeTab = 'bio';

    public function mount(int $id): void
    {
        $this->employee = Employee::with([
            'unit',
            'position',
            'employments',
            'salaries',
            'bankAccounts',
            'documents',
        ])->findOrFail($id);

        $this->authorize('view', $this->employee);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.employee.employee-show', [
            'canViewSensitive' => $user?->can('viewSensitive', $this->employee),
            'canViewSalary' => $user?->can('viewSalary', $this->employee),
            'canViewBank' => $user?->can('viewBank', $this->employee),
        ]);
    }
}

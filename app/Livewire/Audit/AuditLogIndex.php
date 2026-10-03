<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Audit Trail Kepatuhan Sistem - RS Juwita')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public string $actionFilter = 'all';

    public string $search = '';

    public bool $showDetailModal = false;

    public ?int $selectedLogId = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermission('system.audit_view')) {
            abort(403, 'Akses ke audit trail dibatasi khusus Administrator Keamanan Sistem.');
        }
    }

    public function viewLog(int $logId): void
    {
        $this->selectedLogId = $logId;
        $this->showDetailModal = true;
    }

    public function render()
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($this->actionFilter !== 'all') {
            $query->where('action', 'LIKE', $this->actionFilter.'%');
        }

        if ($this->search !== '') {
            $term = '%'.strtolower($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(action) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(ip_address) LIKE ?', [$term])
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                    });
            });
        }

        $logs = $query->paginate(20);

        $selectedLog = $this->selectedLogId ? AuditLog::with('user')->find($this->selectedLogId) : null;

        return view('livewire.audit.audit-log-index', [
            'logs' => $logs,
            'selectedLog' => $selectedLog,
        ]);
    }
}

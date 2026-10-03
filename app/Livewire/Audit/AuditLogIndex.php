<?php

namespace App\Livewire\Audit;

use App\Livewire\Concerns\WithDataTable;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Audit Trail Kepatuhan Sistem - RS Juwita')]
class AuditLogIndex extends Component
{
    use WithDataTable;

    #[Url(except: 'all')]
    public string $actionFilter = 'all';

    public bool $showDetailModal = false;

    public ?int $selectedLogId = null;

    protected function sortableColumns(): array
    {
        return [
            'created_at' => 'created_at',
            'action' => 'action',
            'ip_address' => 'ip_address',
        ];
    }

    protected function filterProperties(): array
    {
        return [
            'actionFilter' => 'all',
        ];
    }

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
        $query = AuditLog::with('user');

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

        $this->applySorting($query, 'created_at', 'desc');

        $logs = $query->paginate($this->perPageValue());

        $selectedLog = $this->selectedLogId ? AuditLog::with('user')->find($this->selectedLogId) : null;

        return view('livewire.audit.audit-log-index', [
            'logs' => $logs,
            'selectedLog' => $selectedLog,
        ]);
    }
}

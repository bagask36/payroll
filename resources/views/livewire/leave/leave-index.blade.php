<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Manajemen Cuti & Izin Kerja
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Pemantauan kuota saldo cuti, permohonan istirahat tahunan, dan persetujuan dinas berjenjang.
            </flux:subheading>
        </div>

        @can('create', App\Models\LeaveRequest::class)
            <div>
                <a href="{{ route('leaves.apply') }}">
                    <flux:button variant="primary" icon="plus">
                        Ajukan Cuti Baru
                    </flux:button>
                </a>
            </div>
        @endcan
    </div>

    {{-- Feedback & Error Banners --}}
    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('errorMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Personal Quota Summary Cards (if balances exist) --}}
    @if($balances->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($balances as $bal)
                <flux:card class="border-l-4 border-l-emerald-500 space-y-1">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ $bal->leaveType?->name ?? 'Saldo Cuti' }} ({{ $bal->year }})</span>
                    <div class="flex items-baseline justify-between pt-1">
                        <div>
                            <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">{{ $bal->remaining_days }}</span>
                            <span class="text-xs text-emerald-600 font-semibold ml-1">Hari Tersisa</span>
                        </div>
                        <div class="text-xs text-zinc-400">
                            Terpakai: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $bal->used_days }}</span> / {{ $bal->allocated_days }} Hari
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    {{-- Tabs Navigation --}}
    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2">
        <flux:button 
            size="sm" 
            :variant="$activeTab === 'my_requests' ? 'primary' : 'subtle'" 
            wire:click="setTab('my_requests')"
        >
            Pengajuan Saya
        </flux:button>

        @if(auth()->user()->hasPermission('leave.approve_tier1'))
            <flux:button 
                size="sm" 
                :variant="$activeTab === 'tier1_approvals' ? 'primary' : 'subtle'" 
                wire:click="setTab('tier1_approvals')"
            >
                Persetujuan Supervisor (Tier 1)
                @if($tier1Queue->isNotEmpty())
                    <flux:badge size="xs" color="amber" class="ml-1.5">{{ $tier1Queue->count() }}</flux:badge>
                @endif
            </flux:button>
        @endif

        @if(auth()->user()->hasPermission('leave.approve_tier2'))
            <flux:button 
                size="sm" 
                :variant="$activeTab === 'tier2_approvals' ? 'primary' : 'subtle'" 
                wire:click="setTab('tier2_approvals')"
            >
                Persetujuan Final SDM (Tier 2)
                @if($tier2Queue->isNotEmpty())
                    <flux:badge size="xs" color="purple" class="ml-1.5">{{ $tier2Queue->count() }}</flux:badge>
                @endif
            </flux:button>
        @endif
    </div>

    {{-- Tab 1: My Personal Requests --}}
    @if($activeTab === 'my_requests')
        <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
            <livewire:tables.leave-request-table />
        </flux:card>
    @endif

    {{-- Tab 2: Tier 1 Approvals (Supervisor) --}}
    @if($activeTab === 'tier1_approvals')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 text-xs text-amber-900 dark:text-amber-200 flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="size-4 text-amber-600 shrink-0" />
                <span>Permohonan cuti staf berikut membutuhkan verifikasi dan persetujuan tingkat pertama (Kepala Unit / Supervisor).</span>
            </div>

            <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <livewire:tables.leave-tier1-approval-table />
            </flux:card>
        </div>
    @endif

    {{-- Tab 3: Tier 2 Final Approvals (HR Manager) --}}
    @if($activeTab === 'tier2_approvals')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-purple-50/60 dark:bg-purple-950/20 border border-purple-200/60 dark:border-purple-900/40 text-xs text-purple-900 dark:text-purple-200 flex items-center gap-2">
                <flux:icon name="shield-check" class="size-4 text-purple-600 shrink-0" />
                <span>Persetujuan final Manajemen SDM (Tier 2). Penyetujuan akan langsung mengesahkan cuti dan memotong saldo kuota.</span>
            </div>

            <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <livewire:tables.leave-tier2-approval-table />
            </flux:card>
        </div>
    @endif

    {{-- Rejection Modal --}}
    @if($showRejectModal)
        <flux:modal wire:model="showRejectModal" class="md:w-[28rem] space-y-4">
            <div>
                <flux:heading size="lg">Alasan Penolakan Cuti</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Berikan keterangan alasan penolakan kepada pemohon cuti.</flux:subheading>
            </div>

            <form wire:submit="submitRejection" class="space-y-4">
                <flux:textarea 
                    wire:model="rejectionReason" 
                    label="Jelaskan alasan penolakan" 
                    placeholder="Contoh: Jadwal operasional IGD sedang padat dan kuota staf jaga tidak mencukupi..." 
                    rows="3" 
                    required 
                />

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger" wire:loading.attr="disabled">
                        Tolak Permohonan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedRequest)
        <flux:modal wire:model="showDetailModal" class="md:w-[34rem] space-y-5">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg">Detail Permohonan Cuti</flux:heading>
                    @php
                        $statusBadge = match ($selectedRequest->status) {
                            'approved' => ['color' => 'emerald', 'label' => 'Disetujui'],
                            'rejected' => ['color' => 'rose', 'label' => 'Ditolak'],
                            'cancelled' => ['color' => 'zinc', 'label' => 'Dibatalkan'],
                            'pending_tier2' => ['color' => 'blue', 'label' => 'Menunggu SDM (Tier 2)'],
                            default => ['color' => 'amber', 'label' => 'Menunggu Supervisor (Tier 1)'],
                        };
                    @endphp
                    <flux:badge :color="$statusBadge['color']" size="sm">
                        {{ $statusBadge['label'] }}
                    </flux:badge>
                </div>
                <flux:subheading class="text-xs text-zinc-500 mt-0.5">
                    ID Tiket: <span class="font-mono">{{ substr($selectedRequest->uuid ?? (string) $selectedRequest->id, 0, 8) }}</span> • Diajukan: {{ $selectedRequest->created_at->translatedFormat('d M Y H:i') }}
                </flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4 p-3.5 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl text-xs">
                <div>
                    <span class="text-zinc-500 font-medium block">Nama Pemohon:</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->employee?->full_name ?? $selectedRequest->doctor?->name ?? 'Pegawai' }}</span>
                    @if($selectedRequest->employee)
                        <span class="text-zinc-400 block text-[11px] font-mono">{{ $selectedRequest->employee->nip }}</span>
                    @endif
                </div>

                <div>
                    <span class="text-zinc-500 font-medium block">Unit & Jabatan:</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->employee?->unit?->name ?? $selectedRequest->doctor?->specialization ?? '-' }}</span>
                    <span class="text-zinc-400 block text-[11px]">{{ $selectedRequest->employee?->position?->title ?? 'Staf' }}</span>
                </div>

                <div>
                    <span class="text-zinc-500 font-medium block">Jenis Cuti:</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $selectedRequest->leaveType?->name ?? 'Cuti' }}</span>
                </div>

                <div>
                    <span class="text-zinc-500 font-medium block">Durasi Hari:</span>
                    <span class="font-bold text-zinc-900 dark:text-zinc-100 font-mono">{{ $selectedRequest->total_days }} Hari Kerja</span>
                </div>

                <div class="col-span-2 pt-2 border-t border-zinc-200/60 dark:border-zinc-700/60">
                    <span class="text-zinc-500 font-medium block">Rentang Tanggal:</span>
                    <span class="font-mono text-zinc-800 dark:text-zinc-200 font-semibold">
                        {{ $selectedRequest->start_date->translatedFormat('d F Y') }} s/d {{ $selectedRequest->end_date->translatedFormat('d F Y') }}
                    </span>
                </div>
            </div>

            <div>
                <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 block mb-1">Alasan Pengajuan:</span>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg text-xs text-zinc-700 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-800">
                    {{ $selectedRequest->reason ?: 'Tidak ada keterangan tambahan.' }}
                </div>
            </div>

            @if($selectedRequest->status === 'rejected' && $selectedRequest->rejection_reason)
                <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-xl space-y-1">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-rose-700 dark:text-rose-400">
                        <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                        <span>Alasan Penolakan Permohonan:</span>
                    </div>
                    <p class="text-xs text-rose-800 dark:text-rose-300 whitespace-pre-line pl-5">
                        {{ $selectedRequest->rejection_reason }}
                    </p>
                </div>
            @endif

            @if($selectedRequest->approvals && $selectedRequest->approvals->isNotEmpty())
                <div>
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 block mb-1.5">Riwayat Verifikasi:</span>
                    <div class="space-y-2">
                        @foreach($selectedRequest->approvals as $approval)
                            <div class="p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        Tier {{ $approval->tier }}: {{ $approval->approver?->name ?? 'Verifikator' }}
                                    </div>
                                    @if($approval->notes)
                                        <div class="text-[11px] text-zinc-500 italic mt-0.5">"{{ $approval->notes }}"</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <flux:badge :color="$approval->status === 'approved' ? 'emerald' : 'rose'" size="xs">
                                        {{ $approval->status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                                    </flux:badge>
                                    <div class="text-[10px] text-zinc-400 mt-0.5">
                                        {{ $approval->action_at ? $approval->action_at->translatedFormat('d/m/Y H:i') : '' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <div>
                    @if($selectedRequest->status === 'pending_tier1' && ($selectedRequest->isApplicant(auth()->user()) || auth()->user()?->can('cancel', $selectedRequest)))
                        <flux:button 
                            variant="danger" 
                            size="sm"
                            wire:click="cancelRequest({{ $selectedRequest->id }})"
                            wire:confirm="Yakin ingin membatalkan permohonan cuti ini?"
                        >
                            Batalkan Permohonan
                        </flux:button>
                    @endif
                </div>

                <flux:modal.close>
                    <flux:button variant="subtle" size="sm">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>
    @endif
</div>

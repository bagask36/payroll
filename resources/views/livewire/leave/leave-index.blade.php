<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Manajemen Cuti & Izin Kerja" 
        subtitle="Pemantauan kuota saldo cuti, permohonan istirahat tahunan, dan persetujuan dinas berjenjang."
    >
        @can('create', App\Models\LeaveRequest::class)
            <x-slot:actions>
                <a href="{{ route('leaves.apply') }}">
                    <flux:button variant="primary" icon="plus">
                        Ajukan Cuti Baru
                    </flux:button>
                </a>
            </x-slot:actions>
        @endcan
    </x-page-header>

    {{-- Feedback & Error Banners --}}
    <x-flash-message :message="$feedbackMessage" />
    <x-flash-message :message="$errorMessage" type="danger" wire-dismiss="$set('errorMessage', null)" />

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
    <div class="flex items-center gap-1.5 sm:gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2 overflow-x-auto no-scrollbar scroll-smooth -mx-4 px-4 sm:mx-0 sm:px-0 py-0.5">
        <flux:button 
            size="sm" 
            :variant="$activeTab === 'my_requests' ? 'primary' : 'subtle'" 
            wire:click="setTab('my_requests')"
            class="shrink-0 whitespace-nowrap cursor-pointer"
        >
            Pengajuan Saya
        </flux:button>

        @if($canApproveAny)
            <flux:button 
                size="sm" 
                :variant="$activeTab === 'approvals' || $activeTab === 'tier1_approvals' || $activeTab === 'tier2_approvals' ? 'primary' : 'subtle'" 
                wire:click="setTab('approvals')"
                class="shrink-0 whitespace-nowrap cursor-pointer"
            >
                Persetujuan Cuti
                @if($pendingApprovalsCount > 0)
                    <flux:badge size="xs" color="amber" class="ml-1.5">{{ $pendingApprovalsCount }}</flux:badge>
                @endif
            </flux:button>
        @endif
    </div>

    {{-- Tab 1: My Personal Requests --}}
    @if($activeTab === 'my_requests')
        <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800 overflow-x-auto">
            <livewire:tables.leave-request-table />
        </flux:card>
    @endif

    {{-- Tab 2: Hierarchical Approvals (Unified Table) --}}
    @if($activeTab === 'approvals' || $activeTab === 'tier1_approvals' || $activeTab === 'tier2_approvals')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-sky-50/60 dark:bg-sky-950/20 border border-sky-200/60 dark:border-sky-900/40 text-xs text-sky-900 dark:text-sky-200 flex items-center gap-2">
                <flux:icon name="arrows-pointing-in" class="size-4 text-sky-600 shrink-0" />
                <span>Daftar permohonan cuti bawahan dan alur persetujuan hierarkis RS Permata Bunda. Seluruh pengajuan yang telah disetujui tetap tersimpan dan terpantau pada riwayat tabel ini.</span>
            </div>

            <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800 overflow-x-auto">
                <livewire:tables.leave-approval-table />
            </flux:card>
        </div>
    @endif

    {{-- Rejection Modal --}}
    @if($showRejectModal)
        <flux:modal wire:model="showRejectModal" class="w-full max-w-xl space-y-4">
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
        <flux:modal wire:model="showDetailModal" class="w-full max-w-3xl space-y-5">
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

            @php
                $chain = $selectedRequest->getApprovalChain();
                $currentStage = $selectedRequest->getCurrentPendingStage();
            @endphp
            @if(!empty($chain))
                <div>
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 block mb-1.5">Alur Persetujuan Hierarkis:</span>
                    <div class="space-y-1.5">
                        @foreach($chain as $stage)
                            @php
                                $isCompleted = $selectedRequest->approvals->where('tier', $stage['tier'])->where('status', 'approved')->first();
                                $isCurrent = $currentStage && $currentStage['tier'] === $stage['tier'];
                            @endphp
                            <div class="p-2 rounded-lg border text-xs flex items-center justify-between {{ $isCurrent ? 'border-amber-400 bg-amber-50/50 dark:bg-amber-950/20' : ($isCompleted ? 'border-emerald-200 bg-emerald-50/30 dark:bg-emerald-950/10' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/40 text-zinc-400') }}">
                                <div class="flex items-center gap-2">
                                    <span class="size-5 rounded-full flex items-center justify-center font-bold text-[10px] {{ $isCompleted ? 'bg-emerald-600 text-white' : ($isCurrent ? 'bg-amber-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600') }}">
                                        {{ $stage['tier'] }}
                                    </span>
                                    <span class="font-medium {{ $isCurrent ? 'text-amber-900 dark:text-amber-200 font-semibold' : ($isCompleted ? 'text-zinc-800 dark:text-zinc-200' : 'text-zinc-500') }}">
                                        {{ $stage['title'] }}
                                    </span>
                                </div>
                                <div>
                                    @if($isCompleted)
                                        <flux:badge color="emerald" size="xs">Disetujui</flux:badge>
                                    @elseif($isCurrent)
                                        <flux:badge color="amber" size="xs">Tahap Aktif</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="xs">Menunggu Antrian</flux:badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
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

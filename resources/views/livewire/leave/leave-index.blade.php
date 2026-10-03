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

            <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Pemohon</flux:table.column>
                        <flux:table.column>Jenis Cuti</flux:table.column>
                        <flux:table.column>Periode Cuti</flux:table.column>
                        <flux:table.column>Durasi</flux:table.column>
                        <flux:table.column>Alasan Cuti</flux:table.column>
                        <flux:table.column align="end">Aksi Verifikasi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($tier1Queue as $item)
                            <flux:table.row :key="$item->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40 transition-colors">
                                <flux:table.cell>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $item->employee?->full_name ?? $item->doctor?->name ?? 'Staf' }}
                                    </div>
                                    <div class="text-xs text-zinc-400 font-mono">
                                        {{ $item->employee?->nip ?? $item->doctor?->specialization ?? '' }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $item->leaveType?->name }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell class="text-xs font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ $item->start_date->format('d/m/Y') }} - {{ $item->end_date->format('d/m/Y') }}
                                </flux:table.cell>

                                <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $item->total_days }} Hari
                                </flux:table.cell>

                                <flux:table.cell class="text-xs text-zinc-600 dark:text-zinc-400 max-w-xs">
                                    {{ $item->reason }}
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button 
                                            size="xs" 
                                            variant="primary" 
                                            icon="check"
                                            wire:click="approveTier1({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            Setujui (T1)
                                        </flux:button>
                                        <flux:button 
                                            size="xs" 
                                            variant="subtle" 
                                            class="text-red-600 dark:text-red-400"
                                            wire:click="openRejectModal({{ $item->id }}, 1)"
                                        >
                                            Tolak
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="p-0">
                                    <x-datatable.empty 
                                        title="Antrean Tier 1 Bersih" 
                                        description="Tidak ada permohonan cuti staf yang menunggu persetujuan Tier 1 saat ini." 
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
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

            <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Pemohon</flux:table.column>
                        <flux:table.column>Jenis Cuti</flux:table.column>
                        <flux:table.column>Periode Cuti</flux:table.column>
                        <flux:table.column>Durasi</flux:table.column>
                        <flux:table.column>Verifikasi Supervisor</flux:table.column>
                        <flux:table.column align="end">Aksi Final</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($tier2Queue as $item)
                            <flux:table.row :key="$item->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40 transition-colors">
                                <flux:table.cell>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $item->employee?->full_name ?? $item->doctor?->name ?? 'Staf' }}
                                    </div>
                                    <div class="text-xs text-zinc-400 font-mono">
                                        {{ $item->employee?->nip ?? $item->doctor?->specialization ?? '' }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $item->leaveType?->name }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell class="text-xs font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ $item->start_date->format('d/m/Y') }} - {{ $item->end_date->format('d/m/Y') }}
                                </flux:table.cell>

                                <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $item->total_days }} Hari
                                </flux:table.cell>

                                <flux:table.cell class="text-xs text-zinc-600 dark:text-zinc-400">
                                    @php $t1 = $item->approvals->where('tier', 1)->first(); @endphp
                                    @if($t1)
                                        <span class="text-emerald-600 font-medium">✓ Disetujui oleh {{ $t1->approver?->name }}</span>
                                        <span class="text-zinc-400 block text-xs">({{ $t1->action_at?->format('d/m/Y H:i') }})</span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button 
                                            size="xs" 
                                            variant="primary" 
                                            icon="check-badge"
                                            wire:click="approveTier2({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            Setujui Final (T2)
                                        </flux:button>
                                        <flux:button 
                                            size="xs" 
                                            variant="subtle" 
                                            class="text-red-600 dark:text-red-400"
                                            wire:click="openRejectModal({{ $item->id }}, 2)"
                                        >
                                            Tolak
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="p-0">
                                    <x-datatable.empty 
                                        title="Antrean Tier 2 Bersih" 
                                        description="Tidak ada permohonan cuti yang menunggu persetujuan akhir SDM saat ini." 
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
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
</div>

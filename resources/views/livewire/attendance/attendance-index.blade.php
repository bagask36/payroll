<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Riwayat & Log Presensi
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Rekapitulasi jam kerja, ketepatan waktu, dan pengajuan koreksi presensi.
            </flux:subheading>
        </div>

        @can('attendance.check_in_out')
            <div>
                <a href="{{ route('attendance.clock') }}">
                    <flux:button variant="primary" icon="clock">
                        Presensi Mandiri Hari Ini
                    </flux:button>
                </a>
            </div>
        @endcan
    </div>

    {{-- Feedback Message --}}
    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Pending Approvals for Supervisors/HR --}}
    @if($pendingAdjustments->isNotEmpty())
        <flux:card class="border-l-4 border-l-amber-500 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-zinc-100">
                    <flux:icon name="bell" class="size-5 text-amber-500" />
                    <span>Permohonan Koreksi Presensi Membutuhkan Persetujuan ({{ $pendingAdjustments->count() }})</span>
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                @foreach($pendingAdjustments as $adj)
                    <div class="py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $adj->attendance->employee?->name ?? $adj->attendance->doctor?->name ?? 'Staf' }}
                                <span class="font-normal text-xs text-zinc-500">({{ $adj->attendance->date->format('d M Y') }})</span>
                            </div>
                            <div class="text-xs text-zinc-500 mt-1">
                                Asli: {{ $adj->original_clock_in?->format('H:i') ?? '-' }} - {{ $adj->original_clock_out?->format('H:i') ?? '-' }}
                                <span class="mx-1 font-bold text-zinc-400">→</span>
                                Dikoreksi: <span class="font-semibold text-emerald-600">{{ $adj->adjusted_clock_in?->format('H:i') ?? '-' }} - {{ $adj->adjusted_clock_out?->format('H:i') ?? '-' }}</span>
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 italic mt-0.5">
                                Alasan: "{{ $adj->reason }}"
                            </div>
                        </div>

                        <div>
                            <flux:button 
                                size="sm" 
                                variant="primary" 
                                icon="check"
                                wire:click="approveAdjustment({{ $adj->id }})"
                                wire:loading.attr="disabled"
                            >
                                Setujui Koreksi
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            label="Total Presensi" 
            :value="$stats['total']" 
            icon="clipboard-document-list" 
            color="zinc" 
            sublabel="Log tercatat pada periode ini" 
        />
        <x-stat-card 
            label="Tepat Waktu" 
            :value="$stats['present']" 
            icon="check-circle" 
            color="emerald" 
            sublabel="Sesuai jadwal shift kerja" 
        />
        <x-stat-card 
            label="Terlambat" 
            :value="$stats['late']" 
            icon="clock" 
            color="amber" 
            sublabel="Melewati toleransi jam masuk" 
        />
        <x-stat-card 
            label="Tidak Hadir / Absen" 
            :value="$stats['absent']" 
            icon="x-circle" 
            color="red" 
            sublabel="Tanpa keterangan presensi" 
        />
    </div>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.attendance-table />
    </flux:card>

    {{-- Flux Native Modal for Adjustment Request --}}
    @if($showAdjustmentModal)
        <flux:modal wire:model="showAdjustmentModal" class="md:w-[28rem] space-y-6">
            <div>
                <flux:heading size="lg">Form Pengajuan Koreksi Presensi</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Koreksi memerlukan tinjauan dan persetujuan dari atasan / HR.</flux:subheading>
            </div>

            <form wire:submit="submitAdjustment" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="adjustedClockIn" 
                        label="Jam Masuk (JJ:MM)" 
                        type="time" 
                    />

                    <flux:input 
                        wire:model="adjustedClockOut" 
                        label="Jam Pulang (JJ:MM)" 
                        type="time" 
                    />
                </div>

                <flux:textarea 
                    wire:model="adjustmentReason" 
                    label="Alasan Pengajuan Koreksi" 
                    placeholder="Contoh: Bertugas darurat di IGD, alat fingerprint belum sempat discan..." 
                    rows="3"
                    required 
                />

                <div class="flex items-center justify-end gap-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Kirim Pengajuan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

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

    {{-- DataTable Toolbar with Search, Filters, and Per-Page Selector --}}
    <x-datatable.toolbar 
        :search="$search" 
        search-placeholder="Ketik Nama atau NIP pegawai..."
        :per-page="$perPage"
        :active-filters-count="$this->activeFiltersCount"
    >
        <flux:input 
            wire:model.live="startDate" 
            label="Dari Tanggal" 
            type="date" 
        />

        <flux:input 
            wire:model.live="endDate" 
            label="Sampai Tanggal" 
            type="date" 
        />

        <flux:select wire:model.live="statusFilter" label="Status Kehadiran">
            <flux:select.option value="all">Semua Status</flux:select.option>
            <flux:select.option value="present">Tepat Waktu (Present)</flux:select.option>
            <flux:select.option value="late">Terlambat (Late)</flux:select.option>
            <flux:select.option value="absent">Absen (Absent)</flux:select.option>
        </flux:select>
    </x-datatable.toolbar>

    {{-- Native Flux Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table :paginate="$attendances">
            <flux:table.columns>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'date'" 
                    :direction="$sortDirection" 
                    wire:click="sort('date')"
                >
                    Tanggal
                </flux:table.column>
                @if(auth()->user()->hasPermission('attendance.view_all') || auth()->user()->hasPermission('attendance.view_team'))
                    <flux:table.column>Pegawai / Dokter</flux:table.column>
                @endif
                <flux:table.column>Shift</flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'clock_in'" 
                    :direction="$sortDirection" 
                    wire:click="sort('clock_in')"
                >
                    Masuk
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'clock_out'" 
                    :direction="$sortDirection" 
                    wire:click="sort('clock_out')"
                >
                    Pulang
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'work_duration_minutes'" 
                    :direction="$sortDirection" 
                    wire:click="sort('work_duration_minutes')"
                >
                    Durasi
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'status'" 
                    :direction="$sortDirection" 
                    wire:click="sort('status')"
                >
                    Status
                </flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($attendances as $row)
                    <flux:table.row :key="$row->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40 transition-colors">
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $row->date->format('d/m/Y') }}
                        </flux:table.cell>

                        @if(auth()->user()->hasPermission('attendance.view_all') || auth()->user()->hasPermission('attendance.view_team'))
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $row->employee?->full_name ?? $row->employee?->name ?? $row->doctor?->name ?? '-' }}
                                </div>
                                <div class="text-xs text-zinc-400">
                                    {{ $row->employee?->nip ?? $row->doctor?->specialization ?? '' }}
                                </div>
                            </flux:table.cell>
                        @endif

                        <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                            {{ $row->scheduleAssignment?->shift?->name ?? 'Reguler' }}
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-zinc-800 dark:text-zinc-200">
                            {{ $row->clock_in ? $row->clock_in->format('H:i') : '-' }}
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-zinc-800 dark:text-zinc-200">
                            {{ $row->clock_out ? $row->clock_out->format('H:i') : '-' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                            {{ $row->work_duration_minutes ? intdiv($row->work_duration_minutes, 60).'j '.($row->work_duration_minutes % 60).'m' : '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($row->status === 'present')
                                <flux:badge size="sm" color="emerald" inset="top bottom">Tepat Waktu</flux:badge>
                            @elseif($row->status === 'late')
                                <flux:badge size="sm" color="amber" inset="top bottom">Telat {{ $row->late_duration_minutes }}m</flux:badge>
                            @else
                                <flux:badge size="sm" color="red" inset="top bottom">Absen</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            @can('attendance.adjust_request')
                                <flux:button 
                                    size="xs" 
                                    variant="subtle" 
                                    wire:click="openAdjustmentModal({{ $row->id }})"
                                >
                                    Koreksi
                                </flux:button>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="p-0">
                            <x-datatable.empty 
                                title="Catatan Presensi Tidak Ditemukan" 
                                description="Tidak ada catatan presensi pada rentang tanggal atau kata kunci yang dipilih." 
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
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

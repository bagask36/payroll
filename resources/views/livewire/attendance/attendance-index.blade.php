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
                <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
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
                    <flux:icon name="bell" class="w-5 h-5 text-amber-500" />
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

    {{-- Filter Bar --}}
    <flux:card class="flex flex-col md:flex-row md:items-center gap-4">
        <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                <option value="all">Semua Status</option>
                <option value="present">Tepat Waktu (Present)</option>
                <option value="late">Terlambat (Late)</option>
                <option value="absent">Absen (Absent)</option>
            </flux:select>
        </div>
    </flux:card>

    {{-- Table of Attendance Records --}}
    <flux:card class="overflow-x-auto p-0">
        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3.5">Tanggal</th>
                    @if(auth()->user()->hasPermission('attendance.view_all') || auth()->user()->hasPermission('attendance.view_team'))
                        <th class="px-4 py-3.5">Pegawai / Dokter</th>
                    @endif
                    <th class="px-4 py-3.5">Shift</th>
                    <th class="px-4 py-3.5">Masuk</th>
                    <th class="px-4 py-3.5">Pulang</th>
                    <th class="px-4 py-3.5">Durasi</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($attendances as $row)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3 font-medium whitespace-nowrap text-zinc-900 dark:text-zinc-100">
                            {{ $row->date->format('d/m/Y') }}
                        </td>
                        @if(auth()->user()->hasPermission('attendance.view_all') || auth()->user()->hasPermission('attendance.view_team'))
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $row->employee?->name ?? $row->doctor?->name ?? '-' }}
                                </div>
                                <div class="text-xs text-zinc-400">
                                    {{ $row->employee?->nip ?? $row->doctor?->specialization ?? '' }}
                                </div>
                            </td>
                        @endif
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $row->scheduleAssignment?->shift?->name ?? 'Reguler' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-zinc-800 dark:text-zinc-200">
                            {{ $row->clock_in ? $row->clock_in->format('H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-zinc-800 dark:text-zinc-200">
                            {{ $row->clock_out ? $row->clock_out->format('H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $row->work_duration_minutes ? intdiv($row->work_duration_minutes, 60).'j '.($row->work_duration_minutes % 60).'m' : '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($row->status === 'present')
                                <flux:badge size="sm" color="emerald">Tepat Waktu</flux:badge>
                            @elseif($row->status === 'late')
                                <flux:badge size="sm" color="amber">Telat {{ $row->late_duration_minutes }}m</flux:badge>
                            @else
                                <flux:badge size="sm" color="red">Absen</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            @can('attendance.adjust_request')
                                <flux:button 
                                    size="xs" 
                                    variant="subtle" 
                                    wire:click="openAdjustmentModal({{ $row->id }})"
                                >
                                    Koreksi
                                </flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-zinc-400 italic">
                            Tidak ada catatan presensi pada rentang tanggal yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $attendances->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Adjustment Request Modal --}}
    @if($showAdjustmentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
            <flux:card class="w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:heading size="lg">Form Pengajuan Koreksi Presensi</flux:heading>
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="$set('showAdjustmentModal', false)" />
                </div>

                <form wire:submit="submitAdjustment" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input 
                            wire:model="adjustedClockIn" 
                            label="Jam Masuk Baru (JJ:MM)" 
                            type="time" 
                        />

                        <flux:input 
                            wire:model="adjustedClockOut" 
                            label="Jam Pulang Baru (JJ:MM)" 
                            type="time" 
                        />
                    </div>

                    <flux:textarea 
                        wire:model="adjustmentReason" 
                        label="Alasan Pengajuan Koreksi" 
                        placeholder="Contoh: Tugas operasi darurat di IGD, sistem presensi tidak sempat discan..." 
                        rows="3"
                        required 
                    />

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button type="button" variant="subtle" wire:click="$set('showAdjustmentModal', false)">
                            Batal
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            Kirim Pengajuan
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    @endif
</div>

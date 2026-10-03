<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Laporan Eksekutif</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Presensi & Kehadiran</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="clock" class="size-7 text-emerald-600" />
                Laporan Kehadiran & Presensi Pegawai
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Analisis jam kerja aktual, kepatuhan jadwal shift, rekap keterlambatan, dan ketidakhadiran pegawai RS Juwita.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="exportCsv" variant="primary" icon="arrow-down-tray" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                Ekspor CSV
            </flux:button>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Hadir</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $totalPresent }}</div>
            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">Presensi masuk terverifikasi</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Terlambat</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $totalLate }}</div>
            <div class="text-xs text-amber-600 mt-1 font-medium">Melebihi toleransi jadwal</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Tanpa Keterangan / Alpha</div>
            <div class="text-2xl font-bold text-rose-600 mt-1">{{ $totalAbsent }}</div>
            <div class="text-xs text-rose-600 mt-1 font-medium">Tidak hadir tanpa izin sah</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <flux:input wire:model.live="startDate" label="Mulai Tanggal" type="date" />
        <flux:input wire:model.live="endDate" label="Sampai Tanggal" type="date" />

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status Kehadiran</label>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status</flux:select.option>
                <flux:select.option value="present">Hadir Tepat Waktu</flux:select.option>
                <flux:select.option value="late">Terlambat</flux:select.option>
                <flux:select.option value="absent">Alpha / Tidak Hadir</flux:select.option>
            </flux:select>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Filter Unit Kerja</label>
            <flux:select wire:model.live="unitFilter">
                <flux:select.option value="">Semua Unit Kerja</flux:select.option>
                @foreach($units as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Tanggal</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Pegawai & Unit</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Jam Masuk - Pulang</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Status & Keterlambatan</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 uppercase">Total Jam Kerja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($attendances as $att)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-3.5 whitespace-nowrap text-xs font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $att->work_date->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $att->employee->name ?? '-' }}</div>
                            <div class="text-xs text-zinc-500 font-mono">{{ $att->employee->nip ?? '-' }} &bull; {{ $att->employee->unit?->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap text-xs font-mono text-zinc-800 dark:text-zinc-200">
                            {{ $att->clock_in_time ? \Carbon\Carbon::parse($att->clock_in_time)->format('H:i') : '--:--' }}
                            -
                            {{ $att->clock_out_time ? \Carbon\Carbon::parse($att->clock_out_time)->format('H:i') : '--:--' }}
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <flux:badge size="sm" color="{{ $att->status === 'present' ? 'emerald' : ($att->status === 'late' ? 'amber' : 'rose') }}">
                                {{ $att->status }}
                            </flux:badge>
                            @if($att->late_minutes > 0)
                                <span class="text-2xs text-rose-600 font-bold block mt-0.5">+{{ $att->late_minutes }} mnt</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap text-xs font-mono text-zinc-800 dark:text-zinc-200 font-semibold">
                            {{ $att->work_hours ? number_format($att->work_hours, 1) . ' jam' : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-zinc-500">
                            Tidak ditemukan rekaman presensi pada rentang tanggal tersebut.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $attendances->links() }}
    </div>
</div>

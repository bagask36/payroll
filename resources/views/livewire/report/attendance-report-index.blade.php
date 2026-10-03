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

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.attendance-report-table />
    </flux:card>
</div>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Laporan Eksekutif</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Kepegawaian</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="chart-pie" class="size-7 text-emerald-600" />
                Laporan Data Kepegawaian & SDM
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Rekapitulasi demografi staf, status kontrak kerja, penempatan unit, dan masa kerja pegawai RS Juwita.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="exportCsv" variant="primary" icon="arrow-down-tray" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                Ekspor CSV
            </flux:button>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pegawai Aktif</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $totalActive }}</div>
            <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">Status operasional aktif</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pegawai Tetap (PKWTT)</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $totalPermanent }}</div>
            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-medium">Staf faskes definitif</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pegawai Kontrak (PKWT)</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $totalContract }}</div>
            <div class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">Kontrak berkala</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Dalam Sanksi (SP Aktif)</div>
            <div class="text-2xl font-bold {{ $totalWithActiveSp > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-zinc-100' }} mt-1">
                {{ $totalWithActiveSp }}
            </div>
            <div class="text-xs {{ $totalWithActiveSp > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-400' }} mt-1 font-medium">
                {{ $totalWithActiveSp > 0 ? 'Perlu evaluasi kontrak' : 'Tidak ada sanksi aktif' }}
            </div>
        </div>
    </div>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.employee-report-table />
    </flux:card>
</div>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Laporan Eksekutif</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Tenaga Medis & Dokter</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="user-group" class="size-7 text-emerald-600" />
                Laporan Kredensial & Praktik Dokter
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Monitoring masa berlaku Surat Tanda Registrasi (STR), Surat Izin Praktik (SIP), dan distribusi keahlian spesialisasi medis RS Juwita.
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
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Dokter Aktif Praktik</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $totalActive }}</div>
            <div class="text-xs text-emerald-600 font-medium">Praktik aktif di RS Juwita</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Dokter Spesialis & Subspesialis</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ $totalSpecialists }}</div>
            <div class="text-xs text-blue-500 font-medium">Kompetensi klinis lanjut</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Dokter Umum / Residen</div>
            <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-300 mt-1">{{ $totalGeneral }}</div>
            <div class="text-xs text-zinc-500 font-medium">Layanan primer & IGD</div>
        </div>
    </div>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.doctor-report-table />
    </flux:card>
</div>

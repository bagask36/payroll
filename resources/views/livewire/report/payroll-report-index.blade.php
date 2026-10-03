<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Laporan Eksekutif</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Payroll & Remunerasi</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="banknotes" class="size-7 text-emerald-600" />
                Laporan Penggajian & Beban SDM
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Ringkasan alokasi biaya gaji, total tunjangan, potongan BPJS, PPh 21, dan take home pay rumah sakit.
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
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Gaji Bruto Disalurkan</div>
            <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                Rp {{ number_format((float) $totalGrossAll, 0, ',', '.') }}
            </div>
            <div class="text-xs text-zinc-500 mt-1">Akumulasi seluruh batch payroll</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Potongan (BPJS + Pajak)</div>
            <div class="text-xl font-bold text-rose-600 mt-1 font-mono">
                -Rp {{ number_format((float) $totalDeductionsAll, 0, ',', '.') }}
            </div>
            <div class="text-xs text-rose-500 mt-1">Iuran kepatuhan regulasi & potongan</div>
        </div>
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Gaji Bersih Final (Net Pay)</div>
            <div class="text-xl font-bold text-emerald-600 mt-1 font-mono">
                Rp {{ number_format((float) $totalDisbursed, 0, ',', '.') }}
            </div>
            <div class="text-xs text-emerald-600 mt-1">Batch dengan status Finalized</div>
        </div>
    </div>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.payroll-report-table />
    </flux:card>
</div>

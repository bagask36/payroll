<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Laporan Eksekutif</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Perpajakan (PPh 21)</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="calculator" class="size-7 text-emerald-600" />
                Laporan Pemotongan PPh 21 Pegawai
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Rekapitulasi perhitungan pajak penghasilan Pasal 21 per peserta dan akumulasi total kewajiban setor pajak rumah sakit.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button wire:click="exportCsv" variant="primary" icon="arrow-down-tray" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                Ekspor CSV Pajak
            </flux:button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800">
        <div>
            <flux:select wire:model.live="periodFilter" label="Periode Penggajian" placeholder="Semua Periode">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div>
            <flux:select wire:model.live="statusFilter" label="Status Batch">
                <option value="all">Semua Status</option>
                <option value="calculated">Terkalkulasi (Draft)</option>
                <option value="finalized">Final (Terkunci)</option>
                <option value="paid">Dibayarkan</option>
            </flux:select>
        </div>
        <div>
            <flux:select wire:model.live="taxStatusFilter" label="Kategori Pajak">
                <option value="all">Semua Pegawai</option>
                <option value="taxable">Hanya Kena Pajak (PPh 21 > 0)</option>
                <option value="non_taxable">Bebas Pajak (PPh 21 = 0)</option>
            </flux:select>
        </div>
    </div>

    {{-- Metrics Cards (Totals) --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total PPh 21 Terutang</div>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1 font-mono">
                Rp {{ number_format((float) $totalPph21, 0, ',', '.') }}
            </div>
            <div class="text-xs text-zinc-500 mt-1">Akumulasi potongan PPh 21 periode terpilih</div>
        </div>

        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Penghasilan Bruto</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                Rp {{ number_format((float) $totalGross, 0, ',', '.') }}
            </div>
            <div class="text-xs text-zinc-500 mt-1">Gaji pokok + seluruh tunjangan kotor</div>
        </div>

        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Peserta Terdaftar</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                {{ number_format($totalParticipants, 0, ',', '.') }} <span class="text-sm font-normal text-zinc-500">orang</span>
            </div>
            <div class="text-xs text-zinc-500 mt-1">Staf dalam perhitungan payroll</div>
        </div>

        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xs">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Peserta Kena Pajak</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                {{ number_format($taxableParticipants, 0, ',', '.') }} <span class="text-sm font-normal text-zinc-500">orang</span>
            </div>
            <div class="text-xs text-zinc-500 mt-1">Pegawai dengan penghasilan > PTKP</div>
        </div>
    </div>

    {{-- PowerGrid Datatable Per Peserta --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.pph21-report-table />
    </flux:card>
</div>

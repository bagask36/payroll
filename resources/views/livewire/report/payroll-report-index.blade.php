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

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Pilih Periode Penggajian</label>
            <flux:select wire:model.live="periodFilter">
                <flux:select.option value="">Semua Periode</flux:select.option>
                @foreach($periods as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status Batch Payroll</label>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status</flux:select.option>
                <flux:select.option value="draft">Draft Perhitungan</flux:select.option>
                <flux:select.option value="finalized">Finalized (Sah / Terkunci)</flux:select.option>
                <flux:select.option value="paid">Telah Dicairkan (Paid)</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Batch & Periode</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Status & Diproses Oleh</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 uppercase">Total Bruto</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 uppercase">Total Potongan</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 uppercase">Total Netto (Take Home)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($runs as $r)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $r->period->name ?? '-' }}</div>
                            <div class="text-xs font-mono text-zinc-500">Run: {{ $r->run_date->format('d M Y') }} &bull; Batch #{{ substr($r->uuid, 0, 8) }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <flux:badge size="sm" color="{{ $r->status === 'finalized' ? 'emerald' : ($r->status === 'paid' ? 'blue' : 'amber') }}">
                                {{ $r->status }}
                            </flux:badge>
                            <div class="text-2xs text-zinc-500 mt-1">{{ $r->processor->name ?? 'Admin' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap text-xs font-mono font-medium text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format((float) $r->total_gross_pay, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap text-xs font-mono text-rose-600">
                            -Rp {{ number_format((float) $r->total_deductions, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap text-sm font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format((float) $r->total_net_pay, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-zinc-500">
                            Belum ada riwayat batch penggajian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $runs->links() }}
    </div>
</div>

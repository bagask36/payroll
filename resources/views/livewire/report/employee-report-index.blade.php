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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari NIP atau nama staf..." icon="magnifying-glass" clearable />

        <flux:select wire:model.live="statusFilter">
            <flux:select.option value="all">Semua Status</flux:select.option>
            <flux:select.option value="active">Aktif Saja</flux:select.option>
            <flux:select.option value="inactive">Nonaktif</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="contractTypeFilter">
            <flux:select.option value="all">Semua Tipe Kontrak</flux:select.option>
            <flux:select.option value="permanent">Tetap (PKWTT)</flux:select.option>
            <flux:select.option value="contract">Kontrak (PKWT)</flux:select.option>
            <flux:select.option value="intern">Magang / Residen</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="unitFilter">
            <flux:select.option value="">Semua Unit Kerja</flux:select.option>
            @foreach($units as $u)
                <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">NIP & Nama</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Unit & Posisi</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Status & Kontrak</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Masa Kerja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($employees as $emp)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $emp->name }}</div>
                            <div class="text-xs font-mono text-zinc-500">NIP: {{ $emp->nip }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-sm text-zinc-800 dark:text-zinc-200">{{ $emp->position?->title ?? '-' }}</div>
                            <div class="text-xs text-zinc-500">{{ $emp->unit?->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <flux:badge size="sm" color="{{ $emp->status === 'active' ? 'emerald' : 'zinc' }}">
                                    {{ $emp->status }}
                                </flux:badge>
                                <flux:badge size="sm" color="blue">
                                    {{ $emp->currentEmployment?->contract_type ?? '-' }}
                                </flux:badge>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-zinc-600 dark:text-zinc-400">
                            {{ $emp->currentEmployment?->join_date ? $emp->currentEmployment->join_date->format('d M Y') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-zinc-500">
                            Tidak ditemukan data pegawai sesuai filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $employees->links() }}
    </div>
</div>

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

    {{-- Filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama dokter, spesialisasi, atau nomor STR..." icon="magnifying-glass" clearable />
        </div>

        <div>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status Praktik</flux:select.option>
                <flux:select.option value="active">Aktif Praktik</flux:select.option>
                <flux:select.option value="inactive">Nonaktif</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Dokter</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">Spesialisasi</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">STR & Masa Berlaku</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 uppercase">SIP & Masa Berlaku</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($doctors as $doc)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $doc->name }}</div>
                            @if($doc->employee)
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 font-mono">Pegawai Tetap (NIP: {{ $doc->employee->nip }})</div>
                            @else
                                <div class="text-xs text-zinc-400">Dokter Tamu / Mitra Klinis</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-sm text-zinc-800 dark:text-zinc-200 font-medium">
                            {{ $doc->specialization ?? 'Dokter Umum' }}
                        </td>
                        <td class="px-5 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                            <div class="font-mono font-medium">{{ $doc->str_number ?? '-' }}</div>
                            <div class="text-2xs text-zinc-500">Exp: {{ $doc->str_valid_until ? $doc->str_valid_until->format('d/m/Y') : 'Seumur Hidup' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                            <div class="font-mono font-medium">{{ $doc->sip_number ?? '-' }}</div>
                            <div class="text-2xs text-zinc-500">Exp: {{ $doc->sip_valid_until ? $doc->sip_valid_until->format('d/m/Y') : '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <flux:badge size="sm" color="{{ $doc->status === 'active' ? 'emerald' : 'zinc' }}">
                                {{ $doc->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-zinc-500">
                            Tidak ditemukan data dokter sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $doctors->links() }}
    </div>
</div>

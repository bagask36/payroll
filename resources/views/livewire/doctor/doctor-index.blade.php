<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Direktori Tenaga Medis & Dokter Spesialis
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Pemantauan kredensial praktik medis, Surat Tanda Registrasi (STR), dan Surat Izin Praktik (SIP) RS Juwita.
            </flux:subheading>
        </div>
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

    {{-- Filter Bar --}}
    <flux:card class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <flux:input 
            wire:model.live.debounce.300ms="search" 
            label="Cari Dokter" 
            placeholder="Ketik Nama, Spesialisasi, No. STR/SIP..."
            icon="magnifying-glass"
        />

        <flux:select wire:model.live="specializationFilter" label="Spesialisasi Medis">
            <option value="all">Semua Spesialisasi</option>
            @foreach($specializations as $spec)
                <option value="{{ $spec }}">{{ $spec }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="licenseStatusFilter" label="Status Kepatuhan Lisensi">
            <option value="all">Semua Kepatuhan</option>
            <option value="expiring_soon">Segera Berakhir (&lt; 60 Hari)</option>
            <option value="expired">Telah Kedaluwarsa</option>
            <option value="valid">Masa Berlaku Aman</option>
        </flux:select>

        <flux:select wire:model.live="doctorTypeFilter" label="Kategori Dokter">
            <option value="all">Semua Kategori</option>
            <option value="organic">Dokter Organik (Pegawai RS)</option>
            <option value="partner">Dokter Mitra / Tamu</option>
        </flux:select>
    </flux:card>

    {{-- Table of Doctors --}}
    <flux:card class="overflow-x-auto p-0">
        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3.5">Dokter & Spesialisasi</th>
                    <th class="px-4 py-3.5">Kategori</th>
                    <th class="px-4 py-3.5">Lisensi STR (Kemenkes)</th>
                    <th class="px-4 py-3.5">Izin Praktik SIP (Dinkes)</th>
                    <th class="px-4 py-3.5">Kontak</th>
                    <th class="px-4 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($doctors as $doc)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $doc->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $doc->specialization }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($doc->employee_id)
                                <flux:badge size="sm" color="emerald">Dokter Organik</flux:badge>
                            @else
                                <flux:badge size="sm" color="blue">Dokter Mitra</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->str_number }}</div>
                            <div class="mt-1 flex items-center gap-1.5">
                                @if($doc->isStrExpired())
                                    <flux:badge size="sm" color="red">Kedaluwarsa ({{ $doc->str_valid_until?->format('d/m/Y') }})</flux:badge>
                                @elseif($doc->str_valid_until?->isBetween(now(), now()->addDays(60)))
                                    <flux:badge size="sm" color="amber">{{ $doc->str_valid_until?->diffInDays(now()) }} Hari Lagi</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">s/d {{ $doc->str_valid_until?->format('d/m/Y') }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->sip_number }}</div>
                            <div class="mt-1 flex items-center gap-1.5">
                                @if($doc->isSipExpired())
                                    <flux:badge size="sm" color="red">Kedaluwarsa ({{ $doc->sip_valid_until?->format('d/m/Y') }})</flux:badge>
                                @elseif($doc->sip_valid_until?->isBetween(now(), now()->addDays(60)))
                                    <flux:badge size="sm" color="amber">{{ $doc->sip_valid_until?->diffInDays(now()) }} Hari Lagi</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">s/d {{ $doc->sip_valid_until?->format('d/m/Y') }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-500 font-mono">
                            {{ $doc->phone_number }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            @can('manageCredentials', $doc)
                                <flux:button 
                                    size="xs" 
                                    variant="subtle" 
                                    icon="pencil-square"
                                    wire:click="openRenewalModal({{ $doc->id }})"
                                >
                                    Perbarui Izin
                                </flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-400 italic">
                            Tidak ditemukan dokter sesuai kriteria filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($doctors->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $doctors->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Credential Renewal Modal --}}
    @if($showRenewalModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
            <flux:card class="w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <flux:heading size="lg">Perbarui Kredensial Praktik Medis</flux:heading>
                        <flux:subheading class="text-xs text-zinc-500">Dokter: {{ $doctorName }}</flux:subheading>
                    </div>
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="$set('showRenewalModal', false)" />
                </div>

                <form wire:submit="updateCredentials" class="space-y-4">
                    <div class="space-y-3">
                        <flux:input 
                            wire:model="strNumber" 
                            label="Nomor STR Kemenkes" 
                            required 
                        />

                        <flux:input 
                            wire:model="strValidUntil" 
                            label="Masa Berlaku STR" 
                            type="date" 
                            required 
                        />
                    </div>

                    <div class="space-y-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:input 
                            wire:model="sipNumber" 
                            label="Nomor SIP Dinkes" 
                            required 
                        />

                        <flux:input 
                            wire:model="sipValidUntil" 
                            label="Masa Berlaku SIP" 
                            type="date" 
                            required 
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button type="button" variant="subtle" wire:click="$set('showRenewalModal', false)">
                            Batal
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            Simpan Kredensial Baru
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    @endif
</div>

<div class="space-y-6">
    {{-- Page Header --}}
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
                <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- KPI Metric Cards (like Table / Sales Demo) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Total Dokter Terdaftar</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $stats['total'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Spesialis & Dokter Jaga</flux:text>
        </flux:card>

        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Dokter Organik RS</flux:text>
            <flux:heading size="xl" class="mt-2 text-emerald-600 dark:text-emerald-400">{{ $stats['organic'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Status Pegawai RS Juwita</flux:text>
        </flux:card>

        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Dokter Mitra / Tamu</flux:text>
            <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400">{{ $stats['partner'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Kemitraan Klinis</flux:text>
        </flux:card>

        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Perlu Perhatian Lisensi</flux:text>
            <flux:heading size="xl" class="mt-2 {{ $stats['attention'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-600' }}">
                {{ $stats['attention'] }}
            </flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">&lt; 60 Hari / Kedaluwarsa</flux:text>
        </flux:card>
    </div>

    {{-- Search & Filters --}}
    <flux:card class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                label="Cari Dokter" 
                placeholder="Ketik Nama, Spesialisasi, No. STR/SIP..."
                icon="magnifying-glass"
                clearable
            />

            <flux:select wire:model.live="specializationFilter" label="Spesialisasi Medis">
                <flux:select.option value="all">Semua Spesialisasi</flux:select.option>
                @foreach($specializations as $spec)
                    <flux:select.option value="{{ $spec }}">{{ $spec }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="licenseStatusFilter" label="Status Kepatuhan Lisensi">
                <flux:select.option value="all">Semua Kepatuhan</flux:select.option>
                <flux:select.option value="expiring_soon">Segera Berakhir (&lt; 60 Hari)</flux:select.option>
                <flux:select.option value="expired">Telah Kedaluwarsa</flux:select.option>
                <flux:select.option value="valid">Masa Berlaku Aman</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="doctorTypeFilter" label="Kategori Dokter">
                <flux:select.option value="all">Semua Kategori</flux:select.option>
                <flux:select.option value="organic">Dokter Organik (Pegawai RS)</flux:select.option>
                <flux:select.option value="partner">Dokter Mitra / Tamu</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    {{-- Native Flux Table --}}
    <flux:card class="p-0 overflow-hidden">
        <flux:table :paginate="$doctors">
            <flux:table.columns>
                <flux:table.column>Dokter & Spesialisasi</flux:table.column>
                <flux:table.column>Kategori</flux:table.column>
                <flux:table.column>Lisensi STR (Kemenkes)</flux:table.column>
                <flux:table.column>Izin Praktik SIP (Dinkes)</flux:table.column>
                <flux:table.column>Kontak</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($doctors as $doc)
                    <flux:table.row :key="$doc->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar initials="{{ strtoupper(substr(str_replace(['dr. ', 'drg. ', 'Sp.'], '', $doc->name), 0, 2)) }}" size="sm" color="zinc" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $doc->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $doc->specialization }}</div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($doc->employee_id)
                                <flux:badge size="sm" color="emerald" inset="top bottom">Dokter Organik</flux:badge>
                            @else
                                <flux:badge size="sm" color="blue" inset="top bottom">Dokter Mitra</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->str_number }}</div>
                            <div class="mt-1">
                                @if($doc->isStrExpired())
                                    <flux:badge size="sm" color="red" inset="top bottom">Kedaluwarsa ({{ $doc->str_valid_until?->format('d/m/Y') }})</flux:badge>
                                @elseif($doc->str_valid_until?->isBetween(now(), now()->addDays(60)))
                                    <flux:badge size="sm" color="amber" inset="top bottom">{{ $doc->str_valid_until?->diffInDays(now()) }} Hari Lagi</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">s/d {{ $doc->str_valid_until?->format('d/m/Y') }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $doc->sip_number }}</div>
                            <div class="mt-1">
                                @if($doc->isSipExpired())
                                    <flux:badge size="sm" color="red" inset="top bottom">Kedaluwarsa ({{ $doc->sip_valid_until?->format('d/m/Y') }})</flux:badge>
                                @elseif($doc->sip_valid_until?->isBetween(now(), now()->addDays(60)))
                                    <flux:badge size="sm" color="amber" inset="top bottom">{{ $doc->sip_valid_until?->diffInDays(now()) }} Hari Lagi</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">s/d {{ $doc->sip_valid_until?->format('d/m/Y') }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-xs text-zinc-500">
                            {{ $doc->phone_number }}
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            @can('manageCredentials', $doc)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" aria-label="Aksi" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="openRenewalModal({{ $doc->id }})">
                                            Perbarui Kredensial STR/SIP
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8 text-zinc-400 italic">
                            Tidak ditemukan dokter sesuai kriteria filter yang dipilih.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Flux Native Modal for Credential Renewal --}}
    @if($showRenewalModal)
        <flux:modal wire:model="showRenewalModal" class="md:w-[28rem] space-y-6">
            <div>
                <flux:heading size="lg">Perbarui Kredensial Praktik Medis</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Dokter: {{ $doctorName }}</flux:subheading>
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

                <flux:separator />

                <div class="space-y-3">
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

                <div class="flex items-center justify-end gap-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Simpan Kredensial Baru
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

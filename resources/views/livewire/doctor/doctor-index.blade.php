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

    {{-- KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            label="Total Dokter Terdaftar" 
            :value="$stats['total']" 
            icon="users" 
            color="zinc" 
            sublabel="Spesialis & dokter jaga" 
        />
        <x-stat-card 
            label="Dokter Organik RS" 
            :value="$stats['organic']" 
            icon="check-circle" 
            color="emerald" 
            sublabel="Staf medis tetap RS Juwita" 
        />
        <x-stat-card 
            label="Dokter Mitra / Tamu" 
            :value="$stats['partner']" 
            icon="user-group" 
            color="blue" 
            sublabel="Kerjasama kemitraan klinis" 
        />
        <x-stat-card 
            label="Perlu Perhatian Lisensi" 
            :value="$stats['attention']" 
            icon="exclamation-triangle" 
            color="amber" 
            sublabel="< 60 Hari / Kedaluwarsa" 
        />
    </div>

    {{-- PowerGrid Table Component --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.doctor-table />
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

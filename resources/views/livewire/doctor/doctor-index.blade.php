<div class="app-page-container">
    {{-- Page Header --}}
    <x-page-header 
        title="Direktori Tenaga Medis" 
        subtitle="Pemantauan kredensial praktik medis, Surat Tanda Registrasi (STR), dan Surat Izin Praktik (SIP) RS Permata Bunda."
    >
        @can('create', App\Models\Doctor::class)
            <x-slot:actions>
                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Tambah Dokter Baru
                </flux:button>
            </x-slot:actions>
        @endcan
    </x-page-header>

    {{-- Feedback Message --}}
    <x-flash-message :message="$feedbackMessage" />

    {{-- KPI Metric Cards --}}
    <div class="app-stat-grid">
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
            sublabel="Staf medis tetap RS Permata Bunda" 
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

    {{-- Flux Native Modal for Credential Renewal & Contact Edit --}}
    @if($showRenewalModal)
        <flux:modal wire:model="showRenewalModal" class="w-full max-w-2xl space-y-6">
            <div>
                <flux:heading size="lg">Perbarui Kredensial & Kontak Dokter</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Dokter: {{ $doctorName }}</flux:subheading>
            </div>

            <form wire:submit="updateCredentials" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="doctorEmail" 
                        label="Alamat Email" 
                        type="email"
                        placeholder="dokter@rsjuwita.co.id" 
                    />

                    <flux:input 
                        wire:model="doctorPhone" 
                        label="Nomor Telepon / WhatsApp" 
                        placeholder="081234567890" 
                    />
                </div>

                <flux:separator />

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
                        Simpan Perubahan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Flux Native Modal for New Doctor Registration --}}
    @if($showCreateModal)
        <flux:modal wire:model="showCreateModal" class="w-full max-w-4xl space-y-6">
            <div>
                <flux:heading size="lg">Pendaftaran Tenaga Medis / Dokter Baru</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Lengkapi data pokok, kontak email, dan legalitas STR & SIP.</flux:subheading>
            </div>

            <form wire:submit="createDoctor" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <flux:input 
                            wire:model="newName" 
                            label="Nama Dokter & Gelar Lengkap" 
                            placeholder="Contoh: dr. Ahmad Fauzi, Sp.A" 
                            required 
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <flux:input 
                            wire:model="newSpecialization" 
                            label="Spesialisasi / Bidang Keahlian" 
                            placeholder="Contoh: Spesialis Anak" 
                            required 
                        />
                    </div>

                    <flux:input 
                        wire:model="newEmail" 
                        label="Alamat Email (Opsional)" 
                        type="email" 
                        placeholder="dokter@rsjuwita.co.id" 
                    />

                    <flux:input 
                        wire:model="newPhone" 
                        label="Nomor Telepon / WhatsApp" 
                        placeholder="081234567890" 
                    />

                    <div class="sm:col-span-2">
                        <flux:select wire:model="newEmployeeId" label="Tautkan Akun Pegawai Organik (Opsional)">
                            <flux:select.option value="">Bukan Pegawai Organik (Dokter Tamu / Mitra)</flux:select.option>
                            @foreach($organicEmployees as $emp)
                                <flux:select.option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->nip }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:input 
                        wire:model="newStrNumber" 
                        label="Nomor STR Kemenkes" 
                        placeholder="STR-XXXX-XXXX" 
                        required 
                    />

                    <flux:input 
                        wire:model="newStrValidUntil" 
                        label="Masa Berlaku STR" 
                        type="date" 
                        required 
                    />

                    <flux:input 
                        wire:model="newSipNumber" 
                        label="Nomor SIP Dinkes" 
                        placeholder="SIP-XXXX-XXXX" 
                        required 
                    />

                    <flux:input 
                        wire:model="newSipValidUntil" 
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
                        Daftarkan Dokter
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

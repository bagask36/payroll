<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Direktori Pegawai & Staf
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Kelola data induk SDM, unit kerja, jabatan, dan riwayat penugasan di RS Juwita.
            </flux:subheading>
        </div>

        @can('create', App\Models\Employee::class)
            <div>
                <flux:button variant="primary" icon="user-plus" wire:click="openCreateModal">
                    Tambah Pegawai Baru
                </flux:button>
            </div>
        @endcan
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
            label="Total Pegawai" 
            :value="$stats['total']" 
            icon="users" 
            color="zinc" 
            sublabel="Seluruh staf terdaftar" 
        />
        <x-stat-card 
            label="Pegawai Aktif" 
            :value="$stats['active']" 
            icon="check-circle" 
            color="emerald" 
            sublabel="Status operasional aktif" 
        />
        <x-stat-card 
            label="Pegawai Tetap (PKWTT)" 
            :value="$stats['permanent']" 
            icon="building-office-2" 
            color="blue" 
            sublabel="Ikatan kerja permanen" 
        />
        <x-stat-card 
            label="Pegawai Kontrak (PKWT)" 
            :value="$stats['contract']" 
            icon="document-text" 
            color="amber" 
            sublabel="Perjanjian waktu tertentu" 
        />
    </div>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.employee-table />
    </flux:card>

    {{-- Flux Native Modal for Create Employee --}}
    @if($showCreateModal)
        <flux:modal wire:model="showCreateModal" class="md:w-[36rem] space-y-6">
            <div>
                <flux:heading size="lg">Pendaftaran Pegawai Baru RS Juwita</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Lengkapi data pokok kepegawaian dan penempatan.</flux:subheading>
            </div>

            <form wire:submit="createEmployee" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:input 
                        wire:model="nip" 
                        label="Nomor Induk Pegawai (NIP)" 
                        required 
                    />

                    <flux:input 
                        wire:model="name" 
                        label="Nama Lengkap & Gelar" 
                        placeholder="Contoh: Siti Rahma, S.Kep"
                        required 
                    />

                    <flux:input 
                        wire:model="phone" 
                        label="Nomor Telepon / WhatsApp" 
                        placeholder="081234567890"
                        required 
                    />

                    <flux:select wire:model="gender" label="Jenis Kelamin">
                        <flux:select.option value="male">Laki-laki</flux:select.option>
                        <flux:select.option value="female">Perempuan</flux:select.option>
                    </flux:select>

                    <flux:input 
                        wire:model="birthDate" 
                        label="Tanggal Lahir" 
                        type="date" 
                        required 
                    />

                    <flux:input 
                        wire:model="ktpNumber" 
                        label="Nomor Induk Kependudukan (NIK KTP)" 
                        placeholder="16 digit NIK"
                        maxlength="16"
                        required 
                    />

                    <flux:select wire:model="unitId" label="Unit / Departemen" required>
                        <flux:select.option value="">Pilih Unit</flux:select.option>
                        @foreach($units as $u)
                            <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="positionId" label="Jabatan" required>
                        <flux:select.option value="">Pilih Jabatan</flux:select.option>
                        @foreach($positions as $p)
                            <flux:select.option value="{{ $p->id }}">{{ $p->title }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="contractType" label="Status Ikatan Kerja">
                        <flux:select.option value="permanent">Tetap (PKWTT)</flux:select.option>
                        <flux:select.option value="contract">Kontrak (PKWT)</flux:select.option>
                        <flux:select.option value="probation">Masa Percobaan</flux:select.option>
                    </flux:select>

                    <flux:input 
                        wire:model="joinDate" 
                        label="Tanggal Mulai Bekerja" 
                        type="date" 
                        required 
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Simpan & Daftarkan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

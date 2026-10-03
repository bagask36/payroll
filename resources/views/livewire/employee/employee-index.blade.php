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
        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Total Seluruh Pegawai</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $stats['total'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Staf Medis & Non-Medis</flux:text>
        </flux:card>

        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Pegawai Aktif Bekerja</flux:text>
            <flux:heading size="xl" class="mt-2 text-emerald-600 dark:text-emerald-400">{{ $stats['active'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Status Keaktifan Aktif</flux:text>
        </flux:card>

        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Pegawai Tetap (PKWTT)</flux:text>
            <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400">{{ $stats['permanent'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Ikatan Kerja Permanen</flux:text>
        </flux:card>

        <flux:card variant="soft" class="relative">
            <flux:text class="truncate font-medium">Pegawai Kontrak (PKWT)</flux:text>
            <flux:heading size="xl" class="mt-2 text-indigo-600 dark:text-indigo-400">{{ $stats['contract'] }}</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-1">Perjanjian Waktu Tertentu</flux:text>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                label="Cari Pegawai" 
                placeholder="Ketik Nama atau NIP..."
                icon="magnifying-glass"
                clearable
            />

            <flux:select wire:model.live="unitFilter" label="Unit / Bagian">
                <flux:select.option value="all">Semua Unit</flux:select.option>
                @foreach($units as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="contractFilter" label="Ikatan Kerja">
                <flux:select.option value="all">Semua Ikatan Kerja</flux:select.option>
                <flux:select.option value="permanent">Tetap (PKWTT)</flux:select.option>
                <flux:select.option value="contract">Kontrak (PKWT)</flux:select.option>
                <flux:select.option value="probation">Masa Percobaan</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="statusFilter" label="Status Keaktifan">
                <flux:select.option value="all">Semua Status</flux:select.option>
                <flux:select.option value="active">Aktif</flux:select.option>
                <flux:select.option value="inactive">Nonaktif</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    {{-- Native Flux Table --}}
    <flux:card class="p-0 overflow-hidden">
        <flux:table :paginate="$employees">
            <flux:table.columns>
                <flux:table.column>Pegawai</flux:table.column>
                <flux:table.column>Unit Kerja</flux:table.column>
                <flux:table.column>Jabatan</flux:table.column>
                <flux:table.column>Ikatan Kerja</flux:table.column>
                <flux:table.column>Kontak</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($employees as $emp)
                    <flux:table.row :key="$emp->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar initials="{{ strtoupper(substr($emp->full_name, 0, 2)) }}" size="sm" color="zinc" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $emp->full_name }}</div>
                                    <div class="text-xs font-mono text-zinc-400">{{ $emp->nip }}</div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="text-zinc-700 dark:text-zinc-300">
                            {{ $emp->currentEmployment?->unit?->name ?? $emp->unit?->name ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-zinc-700 dark:text-zinc-300">
                            {{ $emp->currentEmployment?->position?->title ?? $emp->position?->title ?? '-' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @php $contract = $emp->currentEmployment?->contract_type; @endphp
                            @if($contract === 'permanent')
                                <flux:badge size="sm" color="emerald" inset="top bottom">Tetap</flux:badge>
                            @elseif($contract === 'contract')
                                <flux:badge size="sm" color="blue" inset="top bottom">Kontrak</flux:badge>
                            @elseif($contract === 'probation')
                                <flux:badge size="sm" color="amber" inset="top bottom">Percobaan</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-xs text-zinc-500">
                            {{ $emp->phone_number }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if(strtolower($emp->status) === 'active')
                                <flux:badge size="sm" color="emerald" inset="top bottom">Aktif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" inset="top bottom">Non-aktif</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" aria-label="Aksi" />

                                <flux:menu>
                                    <flux:menu.item icon="arrow-top-right-on-square" href="{{ route('employees.show', $emp->id) }}">
                                        Lihat Profil Lengkap
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8 text-zinc-400 italic">
                            Tidak ditemukan data pegawai yang sesuai dengan kriteria pencarian.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
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

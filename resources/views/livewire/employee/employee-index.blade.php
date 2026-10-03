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
            label="Cari Pegawai" 
            placeholder="Ketik Nama atau NIP..."
            icon="magnifying-glass"
        />

        <flux:select wire:model.live="unitFilter" label="Unit / Bagian">
            <option value="all">Semua Unit</option>
            @foreach($units as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="contractFilter" label="Ikatan Kerja">
            <option value="all">Semua Ikatan Kerja</option>
            <option value="permanent">Tetap (PKWTT)</option>
            <option value="contract">Kontrak (PKWT)</option>
            <option value="probation">Masa Percobaan</option>
        </flux:select>

        <flux:select wire:model.live="statusFilter" label="Status Keaktifan">
            <option value="all">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </flux:select>
    </flux:card>

    {{-- Table of Employees --}}
    <flux:card class="overflow-x-auto p-0">
        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3.5">Pegawai</th>
                    <th class="px-4 py-3.5">Unit Kerja</th>
                    <th class="px-4 py-3.5">Jabatan</th>
                    <th class="px-4 py-3.5">Ikatan Kerja</th>
                    <th class="px-4 py-3.5">Kontak</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($employees as $emp)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $emp->full_name }}</div>
                            <div class="text-xs font-mono text-zinc-400">{{ $emp->nip }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                            {{ $emp->currentEmployment?->unit?->name ?? $emp->unit?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                            {{ $emp->currentEmployment?->position?->title ?? $emp->position?->title ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php $contract = $emp->currentEmployment?->contract_type; @endphp
                            @if($contract === 'permanent')
                                <flux:badge size="sm" color="emerald">Tetap</flux:badge>
                            @elseif($contract === 'contract')
                                <flux:badge size="sm" color="blue">Kontrak</flux:badge>
                            @elseif($contract === 'probation')
                                <flux:badge size="sm" color="amber">Percobaan</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-500 font-mono">
                            {{ $emp->phone_number }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if(strtolower($emp->status) === 'active')
                                <flux:badge size="sm" color="emerald">Aktif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Non-aktif</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <a href="{{ route('employees.show', $emp->id) }}">
                                <flux:button size="xs" variant="subtle" icon="arrow-top-right-on-square">
                                    Detail
                                </flux:button>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-400 italic">
                            Tidak ditemukan data pegawai yang sesuai dengan kriteria pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($employees->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $employees->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Create Employee Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm overflow-y-auto">
            <flux:card class="w-full max-w-2xl space-y-4 shadow-2xl my-8">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:heading size="lg">Pendaftaran Pegawai Baru RS Juwita</flux:heading>
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="$set('showCreateModal', false)" />
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
                            <option value="male">Laki-laki</option>
                            <option value="female">Perempuan</option>
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

                        <flux:select wire:model="unitId" label="Unit / Departemen Penempatan" required>
                            <option value="">Pilih Unit</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="positionId" label="Jabatan Fungsional/Struktural" required>
                            <option value="">Pilih Jabatan</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="contractType" label="Status Ikatan Kerja">
                            <option value="permanent">Tetap (PKWTT)</option>
                            <option value="contract">Kontrak (PKWT)</option>
                            <option value="probation">Masa Percobaan</option>
                        </flux:select>

                        <flux:input 
                            wire:model="joinDate" 
                            label="Tanggal Mulai Bekerja" 
                            type="date" 
                            required 
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button type="button" variant="subtle" wire:click="$set('showCreateModal', false)">
                            Batal
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            Simpan & Daftarkan
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    @endif
</div>

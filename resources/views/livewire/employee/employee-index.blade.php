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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
            label="Non-Shift (Kantor)" 
            :value="$stats['non_shift']" 
            icon="clock" 
            color="teal" 
            sublabel="Jadwal reguler standar" 
        />
        <x-stat-card 
            label="Shift (Operasional)" 
            :value="$stats['shift']" 
            icon="calendar-days" 
            color="indigo" 
            sublabel="Pola dinas bergilir" 
        />
        <x-stat-card 
            label="Pegawai Tetap" 
            :value="$stats['permanent']" 
            icon="building-office-2" 
            color="blue" 
            sublabel="Ikatan kerja PKWTT" 
        />
    </div>

    {{-- Filter Quick Tabs & PowerGrid Datatable --}}
    <flux:card class="p-3 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200/80 dark:border-zinc-800 pb-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mr-1">Pola Kerja:</span>
                <button 
                    type="button" 
                    wire:click="$set('scheduleTypeFilter', 'all')" 
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer {{ $scheduleTypeFilter === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                >
                    Semua ({{ $stats['total'] }})
                </button>
                <button 
                    type="button" 
                    wire:click="$set('scheduleTypeFilter', 'non_shift')" 
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer {{ $scheduleTypeFilter === 'non_shift' ? 'bg-teal-600 text-white shadow-xs' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                >
                    Non-Shift / Kantor ({{ $stats['non_shift'] }})
                </button>
                <button 
                    type="button" 
                    wire:click="$set('scheduleTypeFilter', 'shift')" 
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer {{ $scheduleTypeFilter === 'shift' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                >
                    Shift Operasional ({{ $stats['shift'] }})
                </button>
            </div>
            
            <div class="text-xs text-zinc-400 italic">
                *Pegawai Non-Shift otomatis mengikuti jam reguler tanpa perlu diplot mingguan.
            </div>
        </div>

        <livewire:tables.employee-table :schedule-type-filter="$scheduleTypeFilter" :key="$scheduleTypeFilter" />
    </flux:card>

    {{-- Flux Native Modal for Create Employee --}}
    @if($showCreateModal)
        <flux:modal wire:model="showCreateModal" class="md:w-3xl space-y-6">
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

                    <flux:input 
                        wire:model="email" 
                        label="Alamat Email (Opsional)" 
                        type="email" 
                        placeholder="pegawai@rsjuwita.co.id" 
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

                    {{-- Work Pattern: Non-Shift vs Shift --}}
                    <div class="sm:col-span-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider block mb-2">Pola Jam Kerja</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/60 {{ $scheduleType === 'non_shift' ? 'ring-2 ring-teal-500 bg-teal-50/50 dark:bg-teal-950/20' : '' }}">
                                <input type="radio" wire:model.live="scheduleType" value="non_shift" class="mt-1 text-teal-600 focus:ring-teal-500">
                                <div>
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                        <span>Non-Shift (Reguler)</span>
                                        <flux:badge size="xs" color="teal">Otomatis / Default</flux:badge>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-0.5">Staf kantor / manajemen dengan jam kerja tetap. Tidak perlu di-roster mingguan.</div>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/60 {{ $scheduleType === 'shift' ? 'ring-2 ring-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : '' }}">
                                <input type="radio" wire:model.live="scheduleType" value="shift" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                        <span>Shift Operasional</span>
                                        <flux:badge size="xs" color="indigo">Roster Mingguan</flux:badge>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-0.5">Perawat & staf medis 24 jam yang jadwal dinasnya di-plot per minggu di menu Roster.</div>
                                </div>
                            </label>
                        </div>
                    </div>
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

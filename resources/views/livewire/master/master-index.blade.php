<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Pusat Pengaturan Master Data Rumah Sakit
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Konfigurasi unit kerja, standar jabatan, shift dinas, kebijakan cuti, koordinat geofence, dan regulasi BPJS.
            </flux:subheading>
        </div>

        <div>
            @if($activeTab === 'units')
                <flux:button variant="primary" icon="plus" wire:click="openCreateUnit">Tambah Unit Kerja</flux:button>
            @elseif($activeTab === 'positions')
                <flux:button variant="primary" icon="plus" wire:click="openCreatePosition">Tambah Jabatan</flux:button>
            @elseif($activeTab === 'shifts')
                <flux:button variant="primary" icon="plus" wire:click="openCreateShift">Tambah Shift Dinas</flux:button>
            @elseif($activeTab === 'leave_types')
                <flux:button variant="primary" icon="plus" wire:click="openCreateLeaveType">Tambah Jenis Cuti</flux:button>
            @elseif($activeTab === 'geofences')
                <flux:button variant="primary" icon="plus" wire:click="openCreateGeofence">Tambah Titik Geofence</flux:button>
            @elseif($activeTab === 'payroll_periods')
                <flux:button variant="primary" icon="plus" wire:click="openCreatePeriod">Buka Periode Payroll</flux:button>
            @endif
        </div>
    </div>

    {{-- Feedback & Error Banners --}}
    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400 shrink-0" />
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('errorMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Navigation Tabs --}}
    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2 overflow-x-auto">
        <flux:button size="sm" :variant="$activeTab === 'units' ? 'primary' : 'subtle'" wire:click="setTab('units')">
            Unit & Ruangan
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'positions' ? 'primary' : 'subtle'" wire:click="setTab('positions')">
            Standar Jabatan
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'shifts' ? 'primary' : 'subtle'" wire:click="setTab('shifts')">
            Shift & Jam Dinas
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'leave_types' ? 'primary' : 'subtle'" wire:click="setTab('leave_types')">
            Kebijakan Cuti
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'geofences' ? 'primary' : 'subtle'" wire:click="setTab('geofences')">
            Titik Geofence
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'payroll_periods' ? 'primary' : 'subtle'" wire:click="setTab('payroll_periods')">
            Kalender Payroll
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'bpjs_rules' ? 'primary' : 'subtle'" wire:click="setTab('bpjs_rules')">
            Regulasi Tarif BPJS
        </flux:button>
    </div>

    {{-- Search Filter for table tabs --}}
    @if($activeTab !== 'bpjs_rules')
        <div class="w-full sm:w-80">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari data master..." 
                icon="magnifying-glass"
                size="sm"
            />
        </div>
    @endif

    {{-- TAB 1: UNITS --}}
    @if($activeTab === 'units')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Kode Unit</flux:table.column>
                    <flux:table.column>Nama Ruangan / Unit Kerja</flux:table.column>
                    <flux:table.column>Departemen Induk</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($units as $u)
                        <flux:table.row :key="$u->id">
                            <flux:table.cell class="font-mono text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                {{ $u->code }}
                            </flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $u->name }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">
                                {{ $u->department?->name ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="xs" :color="$u->is_active ? 'emerald' : 'zinc'">
                                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editUnit({{ $u->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-zinc-400 italic">
                                Belum ada unit kerja yang terdaftar.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($units->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $units->links() }}
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 2: POSITIONS --}}
    @if($activeTab === 'positions')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nama Jabatan</flux:table.column>
                    <flux:table.column>Unit Penempatan</flux:table.column>
                    <flux:table.column>Tingkat / Grade Golongan</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($positions as $pos)
                        <flux:table.row :key="$pos->id">
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $pos->title }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">
                                {{ $pos->unit?->name ?? 'Semua Unit / Lintas Unit' }}
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                <flux:badge size="xs" color="purple">{{ $pos->grade_level ?? '-' }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editPosition({{ $pos->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-8 text-zinc-400 italic">
                                Belum ada jabatan fungsional yang terdaftar.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($positions->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $positions->links() }}
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 3: SHIFTS --}}
    @if($activeTab === 'shifts')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Kode & Nama Shift</flux:table.column>
                    <flux:table.column>Jam Mulai — Selesai</flux:table.column>
                    <flux:table.column>Karakteristik</flux:table.column>
                    <flux:table.column>Toleransi Terlambat</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($shifts as $s)
                        <flux:table.row :key="$s->id">
                            <flux:table.cell>
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $s->name }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">{{ $s->code }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                {{ substr($s->start_time, 0, 5) }} — {{ substr($s->end_time, 0, 5) }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($s->is_overnight)
                                    <flux:badge size="xs" color="purple">Overnight (Lintas Hari)</flux:badge>
                                @else
                                    <flux:badge size="xs" color="zinc">Reguler (Satu Hari)</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500 font-mono">
                                {{ $s->late_tolerance_minutes }} Menit
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="xs" :color="$s->is_active ? 'emerald' : 'zinc'">
                                    {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editShift({{ $s->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8 text-zinc-400 italic">
                                Belum ada shift kerja terdaftar.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($shifts->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $shifts->links() }}
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 4: LEAVE TYPES --}}
    @if($activeTab === 'leave_types')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Kode & Nama Cuti</flux:table.column>
                    <flux:table.column>Kuota Bawaan Tahunan</flux:table.column>
                    <flux:table.column>Aturan Potong Cuti</flux:table.column>
                    <flux:table.column>Wajib Lampiran</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($leaveTypes as $lt)
                        <flux:table.row :key="$lt->id">
                            <flux:table.cell>
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $lt->name }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">{{ $lt->code }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                {{ $lt->default_days_per_year }} Hari
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($lt->deducts_balance)
                                    <flux:badge size="xs" color="rose">Memotong Saldo Tahunan</flux:badge>
                                @else
                                    <flux:badge size="xs" color="emerald">Khusus (Tidak Potong Saldo)</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">
                                {{ $lt->requires_attachment ? 'Ya (Wajib)' : 'Opsional' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="xs" :color="$lt->is_active ? 'emerald' : 'zinc'">
                                    {{ $lt->is_active ? 'Aktif' : 'Nonaktif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editLeaveType({{ $lt->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8 text-zinc-400 italic">
                                Belum ada jenis cuti terdaftar.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($leaveTypes->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $leaveTypes->links() }}
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 5: GEOFENCES --}}
    @if($activeTab === 'geofences')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nama Titik Lokasi</flux:table.column>
                    <flux:table.column>Koordinat GPS (Latitude, Longitude)</flux:table.column>
                    <flux:table.column>Radius Geofence</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($geofences as $geo)
                        <flux:table.row :key="$geo->id">
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $geo->name }}
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ number_format($geo->latitude, 6) }}, {{ number_format($geo->longitude, 6) }}
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $geo->radius_meters }} Meter
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="xs" :color="$geo->is_active ? 'emerald' : 'zinc'">
                                    {{ $geo->is_active ? 'Aktif' : 'Nonaktif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editGeofence({{ $geo->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-zinc-400 italic">
                                Belum ada titik geofence presensi.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($geofences->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $geofences->links() }}
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 6: PAYROLL PERIODS --}}
    @if($activeTab === 'payroll_periods')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nama Siklus Periode</flux:table.column>
                    <flux:table.column>Rentang Tanggal</flux:table.column>
                    <flux:table.column>Tanggal Cut-Off Presensi</flux:table.column>
                    <flux:table.column>Status Buku</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($periods as $p)
                        <flux:table.row :key="$p->id">
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $p->name }}
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $p->start_date->format('d/m/Y') }} — {{ $p->end_date->format('d/m/Y') }}
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-500">
                                {{ $p->cutoff_date ? $p->cutoff_date->format('d/m/Y') : '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($p->is_closed)
                                    <flux:badge size="xs" color="rose">Tutup Buku (Closed)</flux:badge>
                                @else
                                    <flux:badge size="xs" color="emerald">Terbuka (Open)</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editPeriod({{ $p->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-zinc-400 italic">
                                Belum ada periode payroll.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if($periods->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $periods->links() }}
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 7: BPJS RULES --}}
    @if($activeTab === 'bpjs_rules')
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Program Jaminan</flux:table.column>
                    <flux:table.column align="end">Tarif Pegawai (%)</flux:table.column>
                    <flux:table.column align="end">Tarif Rumah Sakit (%)</flux:table.column>
                    <flux:table.column align="end">Plafon Upah Maksimal</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($bpjsRules as $rule)
                        <flux:table.row :key="$rule->id">
                            <flux:table.cell>
                                <div class="font-bold uppercase text-zinc-900 dark:text-zinc-100">{{ $rule->program_name }}</div>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="font-mono text-xs text-rose-600 dark:text-rose-400 font-bold">
                                {{ number_format($rule->employee_rate * 100, 2) }}%
                            </flux:table.cell>
                            <flux:table.cell align="end" class="font-mono text-xs text-emerald-600 dark:text-emerald-400 font-bold">
                                {{ number_format($rule->employer_rate * 100, 2) }}%
                            </flux:table.cell>
                            <flux:table.cell align="end" class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                @if($rule->wage_ceiling)
                                    Rp {{ number_format((float) $rule->wage_ceiling, 0, ',', '.') }}
                                @else
                                    <span class="text-zinc-400">Tanpa Plafon</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="xs" :color="$rule->is_active ? 'emerald' : 'zinc'">
                                    {{ $rule->is_active ? 'Berlaku' : 'Nonaktif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editBpjs({{ $rule->id }})">
                                    Edit Tarif
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8 text-zinc-400 italic">
                                Belum ada aturan BPJS terdaftar.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- MODAL UNIT --}}
    <flux:modal wire:model="showUnitModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingUnitId ? 'Edit Unit Kerja' : 'Tambah Unit Kerja Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Definisikan ruangan atau instalasi operasional RS.</flux:subheading>
        </div>

        <form wire:submit="saveUnit" class="space-y-4">
            <flux:input wire:model="unitCode" label="Kode Unit (Misal: IGD, ICU, POLI-INT)" required />
            <flux:input wire:model="unitName" label="Nama Unit / Ruangan" required />
            <flux:select wire:model="unitDepartmentId" label="Departemen Induk">
                <flux:select.option value="">Tanpa Departemen Induk</flux:select.option>
                @foreach($departments as $d)
                    <flux:select.option value="{{ $d->id }}">{{ $d->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" wire:model="unitIsActive" id="unitActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="unitActive" class="text-sm font-medium">Status Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Unit</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL POSITION --}}
    <flux:modal wire:model="showPositionModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingPositionId ? 'Edit Standar Jabatan' : 'Tambah Jabatan Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Standar formasi jabatan fungsional atau struktural.</flux:subheading>
        </div>

        <form wire:submit="savePosition" class="space-y-4">
            <flux:input wire:model="positionTitle" label="Nama / Judul Jabatan" required />
            <flux:select wire:model="positionUnitId" label="Unit Terkait (Opsional)">
                <flux:select.option value="">Lintas Seluruh Unit</flux:select.option>
                @foreach($units as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="positionGradeLevel" label="Grade / Golongan (Misal: II/c, III/a, IV/a)" />

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Jabatan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL SHIFT --}}
    <flux:modal wire:model="showShiftModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingShiftId ? 'Edit Shift Dinas' : 'Tambah Shift Dinas Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Atur jam operasional dinas dan toleransi keterlambatan.</flux:subheading>
        </div>

        <form wire:submit="saveShift" class="space-y-4">
            <flux:input wire:model="shiftCode" label="Kode Shift (Misal: PAGI-1, SORE-1, ML-1)" required />
            <flux:input wire:model="shiftName" label="Nama Shift Dinas" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="shiftStartTime" type="time" label="Jam Masuk" required />
                <flux:input wire:model="shiftEndTime" type="time" label="Jam Pulang" required />
            </div>
            <flux:input wire:model="shiftLateTolerance" type="number" label="Toleransi Terlambat (Menit)" required />

            <div class="space-y-2 pt-1">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="shiftIsOvernight" id="shiftOvernight" class="rounded text-purple-600 focus:ring-purple-500">
                    <label for="shiftOvernight" class="text-sm font-medium">Shift Overnight (Lintas Tengah Malam)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="shiftIsActive" id="shiftActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="shiftActive" class="text-sm font-medium">Status Aktif</label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Shift</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL LEAVE TYPE --}}
    <flux:modal wire:model="showLeaveTypeModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingLeaveTypeId ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Aturan kuota dan pemotongan jatah cuti tahunan.</flux:subheading>
        </div>

        <form wire:submit="saveLeaveType" class="space-y-4">
            <flux:input wire:model="leaveTypeCode" label="Kode Cuti (Misal: ANNUAL, MATERNITY, SICK)" required />
            <flux:input wire:model="leaveTypeName" label="Nama Jenis Cuti" required />
            <flux:input wire:model="leaveTypeDefaultDays" type="number" label="Bawaan Kuota Hari / Tahun" required />

            <div class="space-y-2 pt-1">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="leaveTypeDeductsBalance" id="ltDeduct" class="rounded text-rose-600 focus:ring-rose-500">
                    <label for="ltDeduct" class="text-sm font-medium">Memotong Kuota Cuti Tahunan</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="leaveTypeRequiresAttachment" id="ltAttach" class="rounded text-blue-600 focus:ring-blue-500">
                    <label for="ltAttach" class="text-sm font-medium">Wajib Melampirkan Bukti Berkas</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="leaveTypeIsActive" id="ltActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="ltActive" class="text-sm font-medium">Status Aktif</label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Cuti</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL GEOFENCE --}}
    <flux:modal wire:model="showGeofenceModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingGeofenceId ? 'Edit Titik Geofence' : 'Tambah Titik Geofence' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Koordinat GPS dan batas toleransi jarak meter presensi.</flux:subheading>
        </div>

        <form wire:submit="saveGeofence" class="space-y-4">
            <flux:input wire:model="geofenceName" label="Nama Lokasi / Gedung RS" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="geofenceLat" type="number" step="any" label="Latitude" required />
                <flux:input wire:model="geofenceLng" type="number" step="any" label="Longitude" required />
            </div>
            <flux:input wire:model="geofenceRadius" type="number" label="Radius Toleransi (Meter)" required />
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="geofenceIsActive" id="geoActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="geoActive" class="text-sm font-medium">Status Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Lokasi</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL PAYROLL PERIOD --}}
    <flux:modal wire:model="showPeriodModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingPeriodId ? 'Edit Periode Payroll' : 'Buka Periode Payroll Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Tentukan kalender penggajian dan cut-off presensi bulanan.</flux:subheading>
        </div>

        <form wire:submit="savePeriod" class="space-y-4">
            <flux:input wire:model="periodName" label="Nama Periode (Contoh: Payroll Periode November 2026)" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="periodStartDate" type="date" label="Tanggal Mulai" required />
                <flux:input wire:model="periodEndDate" type="date" label="Tanggal Selesai" required />
            </div>
            <flux:input wire:model="periodCutoffDate" type="date" label="Tanggal Cut-Off Presensi" />
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="periodIsClosed" id="periodClosed" class="rounded text-rose-600 focus:ring-rose-500">
                <label for="periodClosed" class="text-sm font-medium">Tutup Buku (Closed)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Periode</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL BPJS RULE --}}
    <flux:modal wire:model="showBpjsModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">Edit Tarif Regulasi BPJS</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">{{ strtoupper($bpjsProgramName) }}</flux:subheading>
        </div>

        <form wire:submit="saveBpjs" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="bpjsEmployeeRate" type="number" step="0.0001" label="Tarif Pegawai (Desimal, misal 0.01)" required />
                <flux:input wire:model="bpjsEmployerRate" type="number" step="0.0001" label="Tarif RS (Desimal, misal 0.04)" required />
            </div>
            <flux:input wire:model="bpjsWageCeiling" type="number" step="1000" label="Plafon Maksimal Upah (Kosongkan jika tanpa plafon)" />
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="bpjsIsActive" id="bpjsActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="bpjsActive" class="text-sm font-medium">Status Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Tarif</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

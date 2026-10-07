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
            @elseif($activeTab === 'warning_letter_types')
                <flux:button variant="primary" icon="plus" wire:click="openCreateWarningLetterType">Tambah Jenis SP</flux:button>
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
    <div class="flex items-center gap-1.5 sm:gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2 overflow-x-auto no-scrollbar scroll-smooth -mx-4 px-4 sm:mx-0 sm:px-0 py-0.5">
        <flux:button size="sm" :variant="$activeTab === 'units' ? 'primary' : 'subtle'" wire:click="setTab('units')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Unit & Ruangan
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'positions' ? 'primary' : 'subtle'" wire:click="setTab('positions')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Standar Jabatan
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'shifts' ? 'primary' : 'subtle'" wire:click="setTab('shifts')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Shift & Jam Dinas
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'leave_types' ? 'primary' : 'subtle'" wire:click="setTab('leave_types')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Kebijakan Cuti
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'geofences' ? 'primary' : 'subtle'" wire:click="setTab('geofences')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Titik Geofence
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'payroll_periods' ? 'primary' : 'subtle'" wire:click="setTab('payroll_periods')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Kalender Payroll
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'bpjs_rules' ? 'primary' : 'subtle'" wire:click="setTab('bpjs_rules')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Regulasi Tarif BPJS
        </flux:button>
        <flux:button size="sm" :variant="$activeTab === 'warning_letter_types' ? 'primary' : 'subtle'" wire:click="setTab('warning_letter_types')" class="shrink-0 whitespace-nowrap cursor-pointer">
            Tingkat &amp; Jenis SP
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
                    <flux:table.column align="end">Opsi</flux:table.column>
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
                    <flux:table.column align="end">Opsi</flux:table.column>
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
                    <flux:table.column>Tipe Jam Kerja</flux:table.column>
                    <flux:table.column>Jam Mulai — Selesai</flux:table.column>
                    <flux:table.column>Karakteristik</flux:table.column>
                    <flux:table.column>Toleransi Terlambat</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Opsi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($shifts as $s)
                        <flux:table.row :key="$s->id">
                            <flux:table.cell>
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $s->name }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">{{ $s->code }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if(($s->type ?? 'shift') === 'non_shift')
                                    <flux:badge size="xs" color="teal">Reguler (Non-Shift)</flux:badge>
                                @else
                                    <flux:badge size="xs" color="indigo">Shift Operasional</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                {{ substr($s->start_time, 0, 5) }} — {{ substr($s->end_time, 0, 5) }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($s->is_overnight)
                                    <flux:badge size="xs" color="purple">Overnight (Lintas Hari)</flux:badge>
                                @else
                                    <flux:badge size="xs" color="zinc">Satu Hari</flux:badge>
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
                            <flux:table.cell colspan="7" class="text-center py-8 text-zinc-400 italic">
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
                    <flux:table.column align="end">Opsi</flux:table.column>
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
        <div class="space-y-6">
            {{-- Interactive Map Overview Card --}}
            <flux:card class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <flux:heading size="md" class="font-semibold text-zinc-900 dark:text-zinc-100">
                            Peta Sebaran Titik Geofence Presensi RS Permata Bunda
                        </flux:heading>
                        <flux:subheading class="text-xs text-zinc-500">
                            Visualisasi lokasi titik presensi aktif dan perimeter radius toleransi pegawai/dokter.
                        </flux:subheading>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="emerald">
                            {{ $allActiveGeofences->count() }} Lokasi Aktif
                        </flux:badge>
                    </div>
                </div>

                {{-- Map Container for Overview --}}
                <div 
                    x-data="geofenceOverviewMap(@js($allActiveGeofences))" 
                    x-init="initOverviewMap()"
                    class="relative w-full h-72 sm:h-80 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800"
                >
                    <div wire:ignore x-ref="overviewMapContainer" class="w-full h-full z-0"></div>
                </div>
            </flux:card>

            <flux:card class="p-0 overflow-hidden">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nama Titik Lokasi</flux:table.column>
                        <flux:table.column>Koordinat GPS (Latitude, Longitude)</flux:table.column>
                        <flux:table.column>Radius Geofence</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Opsi</flux:table.column>
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
        </div>
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
                    <flux:table.column align="end">Opsi</flux:table.column>
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
                    <flux:table.column align="end">Opsi</flux:table.column>
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

    {{-- Tab 8: Master Tingkat & Jenis Surat Peringatan (SP) --}}
    @if($activeTab === 'warning_letter_types')
        <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Tingkat (Level)</flux:table.column>
                    <flux:table.column>Kode Sanksi</flux:table.column>
                    <flux:table.column>Nama Jenis SP</flux:table.column>
                    <flux:table.column>Masa Berlaku Default</flux:table.column>
                    <flux:table.column>Deskripsi / Konsekuensi</flux:table.column>
                    <flux:table.column align="center">Status</flux:table.column>
                    <flux:table.column align="end">Opsi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($warningLetterTypes as $wlt)
                        <flux:table.row :key="$wlt->id">
                            <flux:table.cell>
                                @if($wlt->level === 0)
                                    <flux:badge size="sm" color="zinc">Level 0 (Teguran)</flux:badge>
                                @elseif($wlt->level === 1)
                                    <flux:badge size="sm" color="amber">Level 1 (SP I)</flux:badge>
                                @elseif($wlt->level === 2)
                                    <flux:badge size="sm" color="orange">Level 2 (SP II)</flux:badge>
                                @elseif($wlt->level === 3)
                                    <flux:badge size="sm" color="rose">Level 3 (SP III/Terakhir)</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">Level {{ $wlt->level }} (Berat)</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-mono font-semibold text-xs">
                                {{ $wlt->code }}
                            </flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $wlt->name }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="font-semibold">{{ $wlt->validity_months }}</span> Bulan
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500 max-w-xs truncate" title="{{ $wlt->description }}">
                                {{ $wlt->description ?: '-' }}
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                <button 
                                    wire:click="toggleWarningLetterTypeStatus({{ $wlt->id }})" 
                                    class="cursor-pointer inline-flex items-center"
                                    title="Klik untuk ubah status"
                                >
                                    <flux:badge size="sm" :color="$wlt->is_active ? 'emerald' : 'zinc'">
                                        {{ $wlt->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </flux:badge>
                                </button>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editWarningLetterType({{ $wlt->id }})">
                                    Edit
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-8 text-zinc-400 italic">
                                Belum ada jenis surat peringatan terdaftar.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- MODAL WARNING LETTER TYPE --}}
    <flux:modal wire:model="showWarningLetterTypeModal" class="w-full max-w-2xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingWarningLetterTypeId ? 'Edit Jenis SP' : 'Tambah Jenis SP Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Konfigurasi standar tingkatan sanksi disiplin dan masa berlaku.</flux:subheading>
        </div>

        <form wire:submit="saveWarningLetterType" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="wltCode" label="Kode Sanksi" placeholder="Misal: SP-1, TEGURAN" required />
                <flux:input wire:model="wltLevel" type="number" min="0" max="10" label="Tingkat (Level)" placeholder="0 - 4" required />
            </div>

            <flux:input wire:model="wltName" label="Nama Jenis Surat Peringatan" placeholder="Misal: Surat Peringatan Pertama (SP I)" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="wltValidityMonths" type="number" min="1" max="60" label="Masa Berlaku Standar (Bulan)" placeholder="6" required />
                <div class="flex items-center pt-7">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="wltIsActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Status Aktif</span>
                    </label>
                </div>
            </div>

            <flux:textarea wire:model="wltDescription" label="Deskripsi / Konsekuensi Sanksi" placeholder="Keterangan pelanggaran atau dampak administratif sanksi ini..." rows="3" />

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Master SP</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL UNIT --}}
    <flux:modal wire:model="showUnitModal" class="w-full max-w-2xl space-y-4">
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
    <flux:modal wire:model="showPositionModal" class="w-full max-w-3xl space-y-5">
        <div>
            <flux:heading size="lg">{{ $editingPositionId ? 'Edit Standar Jabatan' : 'Tambah Jabatan Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Standar formasi jabatan fungsional atau struktural dan remunerasi.</flux:subheading>
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

            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-3">
                <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                    Standar Remunerasi Jabatan
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="positionDefaultBasicSalary" 
                        label="Standar Gaji Pokok (Rp)" 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        placeholder="misal: 6500000" 
                    />
                    <flux:input 
                        wire:model="positionDefaultPositionalAllowance" 
                        label="Standar Tunjangan Jabatan (Rp)" 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        placeholder="misal: 1500000" 
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Jabatan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL SHIFT --}}
    <flux:modal wire:model="showShiftModal" class="w-full max-w-2xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingShiftId ? 'Edit Shift Dinas' : 'Tambah Shift Dinas Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Atur jam operasional dinas dan toleransi keterlambatan.</flux:subheading>
        </div>

        <form wire:submit="saveShift" class="space-y-4">
            <flux:input wire:model="shiftCode" label="Kode Shift (Misal: PAGI-1, SORE-1, ML-1)" required />
            <flux:input wire:model="shiftName" label="Nama Shift Dinas" required />

            <div>
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider block mb-2">Tipe Jam Kerja</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-medium">
                        <input type="radio" wire:model="shiftType" value="shift" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Shift Operasional</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 text-xs font-medium">
                        <input type="radio" wire:model="shiftType" value="non_shift" class="text-teal-600 focus:ring-teal-500">
                        <span>Reguler (Non-Shift)</span>
                    </label>
                </div>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">Pilih Reguler untuk staf kantor non-shift yang jam kerjanya tetap setiap hari kerja.</p>
            </div>

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
    <flux:modal wire:model="showLeaveTypeModal" class="w-full max-w-2xl space-y-4">
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

    {{-- MODAL GEOFENCE DENGAN MAP PINPOINT & RADIUS SETUP --}}
    <flux:modal wire:model="showGeofenceModal" class="w-full max-w-4xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingGeofenceId ? 'Edit Titik Geofence' : 'Tambah Titik Geofence' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Tentukan koordinat titik pusat menggunakan pin peta dan atur radius toleransi presensi.</flux:subheading>
        </div>

        <form wire:submit="saveGeofence" class="space-y-4" x-data="geofencePicker()" x-init="initPicker()">
            <flux:input wire:model="geofenceName" label="Nama Lokasi / Gedung RS" placeholder="Contoh: Gedung Rawat Inap Teratai" required />

            {{-- Map Picker Section --}}
            <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Pilih Titik Pinpoint &amp; Pantau Radius pada Peta:
                    </span>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="useCurrentLocation()" 
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 transition"
                        >
                            <flux:icon name="map-pin" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                            Lokasi Saya Saat Ini
                        </button>
                        <button 
                            type="button" 
                            @click="resetToHospital()" 
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 transition"
                        >
                            <flux:icon name="building-office-2" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                            RS Permata Bunda Utama
                        </button>
                    </div>
                </div>

                {{-- Interactive Map Viewport --}}
                <div class="relative w-full rounded-xl overflow-hidden border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900 shadow-inner" style="min-height: 300px;">
                    <div wire:ignore x-ref="pickerMapContainer" class="w-full z-0" style="height: 300px; min-height: 300px; width: 100%;"></div>
                    <div class="absolute bottom-2 left-2 z-10 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xs px-2.5 py-1 rounded-md text-[11px] font-mono text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 shadow-xs pointer-events-none">
                        Lat: <span x-text="lat?.toFixed(6) ?? '-'"></span> | Lng: <span x-text="lng?.toFixed(6) ?? '-'"></span> | Radius: <span x-text="radius"></span>m
                    </div>
                </div>
                <p class="text-[11px] text-zinc-500">
                    💡 Klik pada peta atau geser (drag) pin penanda untuk menentukan titik pusat. Lingkaran hijau menunjukkan jangkauan toleransi presensi pegawai.
                </p>
            </div>

            {{-- Coordinates and Radius Inputs --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <flux:input 
                        wire:model="geofenceLat" 
                        type="number" 
                        step="any" 
                        label="Latitude" 
                        @input="onManualCoordChange()" 
                        required 
                    />
                </div>
                <div>
                    <flux:input 
                        wire:model="geofenceLng" 
                        type="number" 
                        step="any" 
                        label="Longitude" 
                        @input="onManualCoordChange()" 
                        required 
                    />
                </div>
                <div>
                    <flux:input 
                        wire:model="geofenceRadius" 
                        type="number" 
                        min="10" 
                        max="5000" 
                        label="Radius Toleransi (Meter)" 
                        @input="onRadiusChange($event.target.value)" 
                        required 
                    />
                </div>
            </div>

            {{-- Interactive Radius Slider & Presets --}}
            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Pengaturan Cepat Radius Presensi:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono"><span x-text="radius"></span> Meter</span>
                </div>
                <input 
                    type="range" 
                    min="20" 
                    max="1000" 
                    step="5" 
                    x-model="radius" 
                    @input="onRadiusChange(radius)" 
                    class="w-full h-2 bg-zinc-200 rounded-lg appearance-none cursor-pointer dark:bg-zinc-700 accent-emerald-600"
                />
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="text-[11px] text-zinc-400">Preset:</span>
                    <button type="button" @click="setPresetRadius(50)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">50m (Poli / Klinik)</button>
                    <button type="button" @click="setPresetRadius(100)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">100m (Gedung Standar)</button>
                    <button type="button" @click="setPresetRadius(150)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">150m (Kompleks Gedung)</button>
                    <button type="button" @click="setPresetRadius(300)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">300m (Kawasan Luar RS)</button>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="geofenceIsActive" id="geoActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="geoActive" class="text-sm font-medium">Status Titik Aktif (Digunakan untuk validasi presensi)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Lokasi Geofence</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL PAYROLL PERIOD --}}
    <flux:modal wire:model="showPeriodModal" class="w-full max-w-2xl space-y-4">
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
    <flux:modal wire:model="showBpjsModal" class="w-full max-w-2xl space-y-4">
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

    {{-- Leaflet Map Scripts for Geofence Pinpoint & Overview --}}
    <script>
        function geofenceOverviewMap(geofences) {
            return {
                map: null,
                resizeObserver: null,
                initOverviewMap() {
                    const container = this.$refs.overviewMapContainer;
                    if (!container) return;

                    const renderMap = () => {
                        if (!window.L) {
                            setTimeout(renderMap, 50);
                            return;
                        }

                        if (this.map && this.map._container !== container) {
                            try { this.map.remove(); } catch (e) {}
                            this.map = null;
                        }

                        if (container._leaflet_id && !this.map) {
                            delete container._leaflet_id;
                            container.innerHTML = '';
                        }

                        if (this.map) {
                            try { this.map.remove(); } catch (e) {}
                            this.map = null;
                        }

                        this.map = L.map(container).setView([-6.2088, 106.8456], 15);

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(this.map);

                        if (!geofences || geofences.length === 0) {
                            return;
                        }

                        const bounds = L.latLngBounds();

                        geofences.forEach(g => {
                            const lat = parseFloat(g.latitude);
                            const lng = parseFloat(g.longitude);
                            const radius = parseInt(g.radius_meters) || 100;

                            const marker = L.marker([lat, lng]).addTo(this.map);
                            const circle = L.circle([lat, lng], {
                                radius: radius,
                                color: g.is_active ? '#059669' : '#71717a',
                                fillColor: g.is_active ? '#10b981' : '#a1a1aa',
                                fillOpacity: 0.2,
                                weight: 2
                            }).addTo(this.map);

                            marker.bindPopup(`
                                <div class="text-xs space-y-1">
                                    <strong class="text-sm font-semibold">${g.name}</strong><br/>
                                    <span class="text-zinc-500 font-mono">${lat.toFixed(6)}, ${lng.toFixed(6)}</span><br/>
                                    <span class="text-emerald-700 font-semibold font-mono">Radius: ${radius} meter</span><br/>
                                    <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold ${g.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600'}">
                                        ${g.is_active ? 'Aktif' : 'Nonaktif'}
                                    </span>
                                </div>
                            `);

                            bounds.extend([lat, lng]);
                        });

                        if (geofences.length > 0) {
                            this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 17 });
                        }

                        if (window.ResizeObserver && !this.resizeObserver) {
                            this.resizeObserver = new ResizeObserver(() => {
                                if (this.map && container.clientHeight > 0) {
                                    this.map.invalidateSize();
                                }
                            });
                            this.resizeObserver.observe(container);
                        }

                        setTimeout(() => this.map && this.map.invalidateSize(), 300);
                    };

                    setTimeout(renderMap, 100);
                }
            };
        }

        function geofencePicker() {
            return {
                lat: -6.2088,
                lng: 106.8456,
                radius: 150,
                map: null,
                marker: null,
                circle: null,
                resizeObserver: null,
                renderTimeout: null,

                initPicker() {
                    window.addEventListener('init-geofence-map', (e) => {
                        const detail = Array.isArray(e.detail) ? e.detail[0] : e.detail;
                        if (detail) {
                            this.lat = parseFloat(detail.lat) || -6.2088;
                            this.lng = parseFloat(detail.lng) || 106.8456;
                            this.radius = parseInt(detail.radius) || 150;
                        }
                        this.scheduleRender();
                    });

                    this.$watch('$wire.showGeofenceModal', (val) => {
                        if (val) {
                            const wireLat = parseFloat(this.$wire.get('geofenceLat'));
                            const wireLng = parseFloat(this.$wire.get('geofenceLng'));
                            const wireRadius = parseInt(this.$wire.get('geofenceRadius'));
                            if (!isNaN(wireLat)) this.lat = wireLat;
                            if (!isNaN(wireLng)) this.lng = wireLng;
                            if (!isNaN(wireRadius) && wireRadius > 0) this.radius = wireRadius;
                            this.scheduleRender();
                        } else {
                            this.destroyMap();
                        }
                    });
                },

                scheduleRender() {
                    if (this.renderTimeout) {
                        clearTimeout(this.renderTimeout);
                    }
                    this.renderTimeout = setTimeout(() => {
                        this.setupOrUpdateMap();
                    }, 50);
                },

                destroyMap() {
                    if (this.map) {
                        try { this.map.remove(); } catch (e) {}
                        this.map = null;
                        this.marker = null;
                        this.circle = null;
                    }
                    const container = this.$refs.pickerMapContainer;
                    if (container) {
                        delete container._leaflet_id;
                        container.innerHTML = '';
                    }
                },

                setupOrUpdateMap() {
                    const container = this.$refs.pickerMapContainer;
                    if (!container) return;

                    let attempts = 0;
                    const maxAttempts = 30;

                    const tryInit = () => {
                        attempts++;

                        if (!window.L) {
                            if (attempts < maxAttempts) setTimeout(tryInit, 50);
                            return;
                        }

                        // Tunggu hingga container memiliki dimensi width & height valid (> 0)
                        if (container.offsetWidth === 0 || container.offsetHeight === 0) {
                            if (attempts < maxAttempts) {
                                setTimeout(tryInit, 50);
                            }
                            return;
                        }

                        // Bersihkan map lama agar tidak ada memory leak / state rusak
                        if (this.map) {
                            try { this.map.remove(); } catch (e) {}
                            this.map = null;
                        }
                        if (container._leaflet_id) {
                            delete container._leaflet_id;
                            container.innerHTML = '';
                        }

                        // Inisialisasi peta Leaflet fresh
                        this.map = L.map(container, {
                            zoomControl: true,
                            scrollWheelZoom: true
                        }).setView([this.lat, this.lng], 16);

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(this.map);

                        this.marker = L.marker([this.lat, this.lng], {
                            draggable: true,
                            title: 'Titik Pusat Geofence'
                        }).addTo(this.map);

                        this.circle = L.circle([this.lat, this.lng], {
                            radius: this.radius,
                            color: '#059669',
                            fillColor: '#10b981',
                            fillOpacity: 0.25,
                            weight: 2
                        }).addTo(this.map);

                        // Klik pada peta untuk pindahkan pin
                        this.map.on('click', (e) => {
                            this.setPosition(e.latlng.lat, e.latlng.lng);
                        });

                        // Geser pin (drag) untuk perbarui lingkaran dan koordinat
                        this.marker.on('drag', (e) => {
                            const pos = e.target.getLatLng();
                            if (this.circle) {
                                this.circle.setLatLng(pos);
                            }
                        });

                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.setPosition(pos.lat, pos.lng);
                        });

                        // ResizeObserver untuk menjaga ukuran jika window/dialog berubah
                        if (window.ResizeObserver && !this.resizeObserver) {
                            this.resizeObserver = new ResizeObserver(() => {
                                if (this.map && container.clientHeight > 0) {
                                    this.map.invalidateSize();
                                }
                            });
                            this.resizeObserver.observe(container);
                        }

                        // Invalidate size beberapa kali untuk memastikan animasi transisi modal selesai
                        const invalidate = () => {
                            if (this.map && container.clientHeight > 0) {
                                this.map.invalidateSize();
                            }
                        };
                        requestAnimationFrame(invalidate);
                        setTimeout(invalidate, 100);
                        setTimeout(invalidate, 250);
                        setTimeout(invalidate, 500);
                    };

                    tryInit();
                },

                setPosition(newLat, newLng) {
                    this.lat = parseFloat(newLat.toFixed(6));
                    this.lng = parseFloat(newLng.toFixed(6));

                    if (this.marker) this.marker.setLatLng([this.lat, this.lng]);
                    if (this.circle) this.circle.setLatLng([this.lat, this.lng]);

                    this.$wire.set('geofenceLat', this.lat);
                    this.$wire.set('geofenceLng', this.lng);
                },

                onManualCoordChange() {
                    setTimeout(() => {
                        const manualLat = parseFloat(this.$wire.get('geofenceLat'));
                        const manualLng = parseFloat(this.$wire.get('geofenceLng'));

                        if (!isNaN(manualLat) && !isNaN(manualLng)) {
                            this.lat = manualLat;
                            this.lng = manualLng;
                            if (this.marker) this.marker.setLatLng([this.lat, this.lng]);
                            if (this.circle) this.circle.setLatLng([this.lat, this.lng]);
                            if (this.map) this.map.panTo([this.lat, this.lng]);
                        }
                    }, 50);
                },

                onRadiusChange(val) {
                    const r = parseInt(val) || 100;
                    this.radius = r;
                    if (this.circle) this.circle.setRadius(r);
                    this.$wire.set('geofenceRadius', r);
                },

                setPresetRadius(r) {
                    this.onRadiusChange(r);
                },

                useCurrentLocation() {
                    if ("geolocation" in navigator) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const userLat = pos.coords.latitude;
                                const userLng = pos.coords.longitude;
                                if (this.map) this.map.setView([userLat, userLng], 17);
                                this.setPosition(userLat, userLng);
                            },
                            (err) => {
                                alert("Gagal membaca GPS browser: " + err.message);
                            },
                            { enableHighAccuracy: true, timeout: 8000 }
                        );
                    } else {
                        alert("Peramban Anda tidak mendukung sensor Geolocation.");
                    }
                },

                resetToHospital() {
                    const hospLat = -6.208800;
                    const hospLng = 106.845600;
                    if (this.map) this.map.setView([hospLat, hospLng], 16);
                    this.setPosition(hospLat, hospLng);
                }
            };
        }
    </script>
</div>

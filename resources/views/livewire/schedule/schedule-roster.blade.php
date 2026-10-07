<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Roster & Jadwal Dinas Rumah Sakit
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Perencanaan dan pemantauan jadwal shift kerja perawat, dokter spesialis, dan staf operasional.
            </flux:subheading>
        </div>

        @can('create', App\Models\ScheduleAssignment::class)
            <div>
                <flux:button variant="primary" icon="calendar-days" wire:click="openAssignModal">
                    Tetapkan Jadwal Shift
                </flux:button>
            </div>
        @endcan
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

    {{-- Navigation & Filter Card --}}
    <flux:card class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        {{-- Week Navigator --}}
        <div class="flex flex-wrap items-center gap-2">
            <flux:button size="sm" variant="subtle" icon="chevron-left" wire:click="previousWeek" />
            <div class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-xs font-semibold text-zinc-900 dark:text-zinc-100 font-mono">
                {{ $startDate->locale('id')->translatedFormat('d M Y') }} — {{ $endDate->locale('id')->translatedFormat('d M Y') }}
            </div>
            <flux:button size="sm" variant="subtle" icon="chevron-right" wire:click="nextWeek" />
            <flux:button size="sm" variant="subtle" wire:click="currentWeek">Minggu Ini</flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <flux:select wire:model.live="personTypeFilter" class="w-full sm:w-48">
                <flux:select.option value="all">Semua Personil</flux:select.option>
                <flux:select.option value="shift_employee">Pegawai Shift Saja</flux:select.option>
                <flux:select.option value="employee">Semua Staf & Perawat</flux:select.option>
                <flux:select.option value="doctor">Dokter & Spesialis</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="shiftFilter" class="w-full sm:w-48">
                <flux:select.option value="all">Semua Shift</flux:select.option>
                @foreach($shifts as $s)
                    <flux:select.option value="{{ $s->id }}">{{ $s->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    {{-- PowerGrid Schedule Table --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800 overflow-x-auto">
        <livewire:tables.schedule-table 
            :week-start-date="$weekStartDate" 
            :person-type-filter="$personTypeFilter" 
            :shift-filter="$shiftFilter" 
        />
    </flux:card>

    {{-- Flux Native Modal for Assign Shift --}}
    @if($showAssignModal)
        <flux:modal wire:model="showAssignModal" class="w-full max-w-3xl space-y-5">
            <div>
                <flux:heading size="lg">Tetapkan Penugasan Shift Baru</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">
                    Pilih satu atau beberapa personil dan tanggal dinas. Sistem akan menyimpan jadwal secara atomik per personil dan per tanggal di basis data.
                </flux:subheading>
            </div>

            @if($errorMessage)
                <div class="p-3.5 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 text-xs flex items-center gap-2">
                    <flux:icon name="exclamation-circle" class="size-4 shrink-0 text-rose-600" />
                    <span>{{ $errorMessage }}</span>
                </div>
            @endif

            <form wire:submit="assignShift" class="space-y-4">
                {{-- 1. Shift Selection --}}
                <div class="space-y-1">
                    <flux:select wire:model="selectedShiftId" label="Pilih Shift Kerja" required>
                        <flux:select.option value="">-- Pilih Shift Kerja --</flux:select.option>
                        @foreach($shifts as $s)
                            <flux:select.option value="{{ $s->id }}">
                                {{ $s->name }} ({{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- 2. Date Selection (Single, Range, or Multiple) --}}
                <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                            <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <span>Pilihan Tanggal Penugasan</span>
                        </label>
                        <div class="inline-flex rounded-lg p-0.5 bg-zinc-200/70 dark:bg-zinc-800 text-[11px] font-medium">
                            <button 
                                type="button" 
                                wire:click="$set('dateSelectionMode', 'single')"
                                class="px-2.5 py-1 rounded-md transition {{ $dateSelectionMode === 'single' ? 'bg-white dark:bg-zinc-700 font-semibold shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                            >
                                Satu Tanggal
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('dateSelectionMode', 'range')"
                                class="px-2.5 py-1 rounded-md transition {{ $dateSelectionMode === 'range' ? 'bg-white dark:bg-zinc-700 font-semibold shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                            >
                                Rentang Periode
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('dateSelectionMode', 'multiple')"
                                class="px-2.5 py-1 rounded-md transition {{ $dateSelectionMode === 'multiple' ? 'bg-white dark:bg-zinc-700 font-semibold shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                            >
                                Pilih Tanggal
                            </button>
                        </div>
                    </div>

                    {{-- Mode: Single Date --}}
                    @if($dateSelectionMode === 'single')
                        <div>
                            <flux:input 
                                wire:model.live="scheduleDate" 
                                label="Tanggal Dinas" 
                                type="date" 
                                required 
                            />
                            @if($scheduleDate)
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Hari: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ \Carbon\Carbon::parse($scheduleDate)->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Mode: Date Range --}}
                    @if($dateSelectionMode === 'range')
                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <flux:input 
                                        wire:model.live="rangeStartDate" 
                                        label="Dari Tanggal" 
                                        type="date" 
                                        required 
                                    />
                                    @if($rangeStartDate)
                                        <div class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($rangeStartDate)->locale('id')->isoFormat('dddd, D MMM Y') }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <flux:input 
                                        wire:model.live="rangeEndDate" 
                                        label="Sampai Tanggal" 
                                        type="date" 
                                        required 
                                    />
                                    @if($rangeEndDate)
                                        <div class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($rangeEndDate)->locale('id')->isoFormat('dddd, D MMM Y') }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block mb-1.5">
                                    Hari Kerja Aktif:
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    @php
                                        $dayNames = [
                                            1 => 'Senin',
                                            2 => 'Selasa',
                                            3 => 'Rabu',
                                            4 => 'Kamis',
                                            5 => 'Jumat',
                                            6 => 'Sabtu',
                                            7 => 'Minggu',
                                        ];
                                    @endphp
                                    @foreach($dayNames as $dayNum => $dayName)
                                        <button 
                                            type="button" 
                                            wire:click="toggleDayOfWeek({{ $dayNum }})"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition cursor-pointer {{ in_array($dayNum, $rangeDaysOfWeek) ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-200' }}"
                                        >
                                            {{ $dayName }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Mode: Multiple Specific Dates --}}
                    @if($dateSelectionMode === 'multiple')
                        <div class="space-y-2.5">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <flux:input 
                                        wire:model="newDateToAdd" 
                                        type="date" 
                                        label="Tambah Tanggal Dinas" 
                                    />
                                </div>
                                <flux:button type="button" variant="primary" wire:click="addSpecificDate" icon="plus">
                                    Tambah
                                </flux:button>
                            </div>

                            @if(! empty($scheduleDates))
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    @foreach($scheduleDates as $d)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span>{{ \Carbon\Carbon::parse($d)->locale('id')->isoFormat('dddd, D MMM Y') }}</span>
                                            <button 
                                                type="button" 
                                                wire:click="removeSpecificDate('{{ $d }}')" 
                                                class="text-emerald-600 hover:text-emerald-900 dark:hover:text-emerald-100 cursor-pointer"
                                                title="Hapus"
                                            >
                                                &times;
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-[11px] text-zinc-400 italic">Belum ada tanggal dipilih. Pilih tanggal di atas lalu klik Tambah.</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- 3. Personnel Multi-Selection --}}
                <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-200/70 dark:border-zinc-800 pb-2.5">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <flux:icon name="users" class="size-4 text-purple-600 dark:text-purple-400" />
                                <span>Pilih Personil Perawat / Pegawai</span>
                            </label>
                            <flux:badge size="sm" :color="count($selectedEmployeeIds) > 0 ? 'emerald' : 'zinc'">
                                {{ count($selectedEmployeeIds) }} Dipilih
                            </flux:badge>
                        </div>

                        <div class="flex items-center gap-2 text-xs">
                            <button 
                                type="button" 
                                wire:click="selectAllFilteredEmployees({{ json_encode($assignableEmployees->pluck('id')->toArray()) }})"
                                class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium text-[11px]"
                            >
                                Pilih Semua ({{ $assignableEmployees->count() }})
                            </button>
                            <span class="text-zinc-300 dark:text-zinc-700">&bull;</span>
                            <button 
                                type="button" 
                                wire:click="deselectAllEmployees"
                                class="text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 text-[11px]"
                            >
                                Bersihkan
                            </button>
                        </div>
                    </div>

                    {{-- Search & Unit Filters --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <flux:input 
                            wire:model.live.debounce.250ms="employeeSearch" 
                            placeholder="Cari nama atau NIP..." 
                            icon="magnifying-glass" 
                            size="sm" 
                        />

                        <flux:select wire:model.live="filterUnitId" size="sm">
                            <flux:select.option value="">-- Semua Unit / Ruangan --</flux:select.option>
                            @foreach($units as $u)
                                <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-zinc-500">
                        <span>Menampilkan {{ $assignableEmployees->count() }} personil</span>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" wire:model.live="showAllEmployeesInAssign" class="rounded text-emerald-600 text-[10px]">
                            <span>Tampilkan personil non-shift</span>
                        </label>
                    </div>

                    {{-- Scrollable Checklist --}}
                    <div class="max-h-52 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/60 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-1">
                        @forelse($assignableEmployees as $emp)
                            <label class="flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-900 cursor-pointer transition">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="selectedEmployeeIds" 
                                        value="{{ $emp->id }}" 
                                        class="rounded text-emerald-600 focus:ring-emerald-500 size-4"
                                    />
                                    <div class="min-w-0">
                                        <div class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate">
                                            {{ $emp->full_name }}
                                        </div>
                                        <div class="text-[10px] text-zinc-400 font-mono">
                                            NIP: {{ $emp->nip }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 text-right">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                        {{ $emp->unit?->name ?? 'RS Permata Bunda' }}
                                    </span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded {{ $emp->schedule_type === 'shift' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-zinc-100 text-zinc-500' }}">
                                        {{ $emp->schedule_type === 'shift' ? 'Shift' : 'Non-Shift' }}
                                    </span>
                                </div>
                            </label>
                        @empty
                            <div class="py-6 text-center text-xs text-zinc-400">
                                Tidak ditemukan personil yang sesuai dengan pencarian/filter.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- 4. Optional Notes --}}
                <flux:input 
                    wire:model="notes" 
                    label="Catatan Khusus (Opsional)" 
                    placeholder="Contoh: Tugas jaga ruang rawat inap VIP..." 
                />

                {{-- Modal Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="assignShift">
                            Simpan & Terbitkan Jadwal
                        </span>
                        <span wire:loading wire:target="assignShift">
                            Menerbitkan Jadwal...
                        </span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

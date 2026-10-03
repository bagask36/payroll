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
        <div class="flex items-center gap-2">
            <flux:button size="sm" variant="subtle" icon="chevron-left" wire:click="previousWeek" />
            <div class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-xs font-semibold text-zinc-900 dark:text-zinc-100 font-mono">
                {{ $startDate->translatedFormat('d M Y') }} — {{ $endDate->translatedFormat('d M Y') }}
            </div>
            <flux:button size="sm" variant="subtle" icon="chevron-right" wire:click="nextWeek" />
            <flux:button size="sm" variant="subtle" wire:click="currentWeek">Minggu Ini</flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="personTypeFilter" class="w-44">
                <flux:select.option value="all">Semua Personil</flux:select.option>
                <flux:select.option value="employee">Perawat & Staf</flux:select.option>
                <flux:select.option value="doctor">Dokter & Spesialis</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="shiftFilter" class="w-48">
                <flux:select.option value="all">Semua Shift</flux:select.option>
                @foreach($shifts as $s)
                    <flux:select.option value="{{ $s->id }}">{{ $s->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    {{-- PowerGrid Schedule Table --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.schedule-table 
            :week-start-date="$weekStartDate" 
            :person-type-filter="$personTypeFilter" 
            :shift-filter="$shiftFilter" 
        />
    </flux:card>

    {{-- Flux Native Modal for Assign Shift --}}
    @if($showAssignModal)
        <flux:modal wire:model="showAssignModal" class="md:w-[28rem] space-y-6">
            <div>
                <flux:heading size="lg">Tetapkan Penugasan Shift Baru</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Pilih personil medis atau staf beserta jadwal shift kerjanya.</flux:subheading>
            </div>

            <form wire:submit="assignShift" class="space-y-4">
                {{-- Target Type: Employee vs Doctor --}}
                <div>
                    <label class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Tipe Personil</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <input type="radio" wire:model.live="assignTargetType" value="employee" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium">Pegawai / Perawat</span>
                        </label>

                        <label class="flex items-center gap-2 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <input type="radio" wire:model.live="assignTargetType" value="doctor" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium">Dokter Spesialis</span>
                        </label>
                    </div>
                </div>

                {{-- Target Person Selection --}}
                @if($assignTargetType === 'employee')
                    <flux:select wire:model="selectedEmployeeId" label="Pilih Pegawai / Perawat" required>
                        <flux:select.option value="">Pilih Pegawai</flux:select.option>
                        @foreach($employees as $emp)
                            <flux:select.option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->nip }})</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:select wire:model="selectedDoctorId" label="Pilih Dokter Spesialis" required>
                        <flux:select.option value="">Pilih Dokter</flux:select.option>
                        @foreach($doctors as $doc)
                            <flux:select.option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->specialization }})</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                {{-- Shift Selection --}}
                <flux:select wire:model="selectedShiftId" label="Pilih Shift Kerja" required>
                    <flux:select.option value="">Pilih Shift</flux:select.option>
                    @foreach($shifts as $s)
                        <flux:select.option value="{{ $s->id }}">
                            {{ $s->name }} ({{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Date Selection --}}
                <flux:input 
                    wire:model="scheduleDate" 
                    label="Tanggal Penugasan Dinas" 
                    type="date" 
                    required 
                />

                {{-- Optional Notes --}}
                <flux:input 
                    wire:model="notes" 
                    label="Catatan Khusus (Opsional)" 
                    placeholder="Contoh: Tugas jaga ruang ICU..." 
                />

                <div class="flex items-center justify-end gap-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Simpan & Terbitkan Jadwal
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

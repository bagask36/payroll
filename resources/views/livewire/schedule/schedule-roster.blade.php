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
                <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
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
            <flux:select wire:model.live="personTypeFilter" class="w-40">
                <option value="all">Semua Personil</option>
                <option value="employee">Perawat & Staf</option>
                <option value="doctor">Dokter & Spesialis</option>
            </flux:select>

            <flux:select wire:model.live="shiftFilter" class="w-44">
                <option value="all">Semua Shift</option>
                @foreach($shifts as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    {{-- Schedule Roster List Table --}}
    <flux:card class="overflow-x-auto p-0">
        <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3.5">Tanggal Tugas</th>
                    <th class="px-4 py-3.5">Personil Medis / Staf</th>
                    <th class="px-4 py-3.5">Peran / Unit</th>
                    <th class="px-4 py-3.5">Shift & Jam Kerja</th>
                    <th class="px-4 py-3.5">Toleransi</th>
                    <th class="px-4 py-3.5">Catatan</th>
                    <th class="px-4 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($assignments as $item)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item->schedule_date->translatedFormat('l') }}
                            </div>
                            <div class="text-xs text-zinc-400 font-mono">
                                {{ $item->schedule_date->format('d/m/Y') }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($item->doctor)
                                <div class="font-semibold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                                    <flux:icon name="user-group" class="w-4 h-4" />
                                    <span>{{ $item->doctor->name }}</span>
                                </div>
                                <div class="text-xs text-zinc-400">{{ $item->doctor->specialization }}</div>
                            @elseif($item->employee)
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="user" class="w-4 h-4" />
                                    <span>{{ $item->employee->full_name }}</span>
                                </div>
                                <div class="text-xs text-zinc-400 font-mono">{{ $item->employee->nip }}</div>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300 text-xs">
                            {{ $item->employee?->unit?->name ?? 'Pelayanan Medis' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->shift?->name }}</div>
                            <div class="text-xs font-mono text-zinc-500">
                                {{ substr($item->shift?->start_time ?? '', 0, 5) }} - {{ substr($item->shift?->end_time ?? '', 0, 5) }}
                                @if($item->shift?->is_overnight)
                                    <flux:badge size="xs" color="purple" class="ml-1">Overnight</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-500">
                            {{ $item->shift?->late_tolerance_minutes ?? 0 }} Menit
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-500 max-w-xs truncate">
                            {{ $item->notes ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            @can('delete', $item)
                                <flux:button 
                                    size="xs" 
                                    variant="subtle" 
                                    icon="x-mark"
                                    class="text-red-600 dark:text-red-400"
                                    wire:click="cancelAssignment({{ $item->id }})"
                                    wire:confirm="Yakin ingin membatalkan jadwal shift ini?"
                                >
                                    Batalkan
                                </flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-zinc-400 italic">
                            Tidak ada penugasan shift pada rentang minggu ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </flux:card>

    {{-- Assign Shift Modal --}}
    @if($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
            <flux:card class="w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:heading size="lg">Tetapkan Penugasan Shift Baru</flux:heading>
                    <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="$set('showAssignModal', false)" />
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
                            <option value="">Pilih Pegawai</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->nip }})</option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:select wire:model="selectedDoctorId" label="Pilih Dokter Spesialis" required>
                            <option value="">Pilih Dokter</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->specialization }})</option>
                            @endforeach
                        </flux:select>
                    @endif

                    {{-- Shift Selection --}}
                    <flux:select wire:model="selectedShiftId" label="Pilih Shift Kerja" required>
                        <option value="">Pilih Shift</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->name }} ({{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }})
                            </option>
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

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button type="button" variant="subtle" wire:click="$set('showAssignModal', false)">
                            Batal
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            Simpan & Terbitkan Jadwal
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    @endif
</div>

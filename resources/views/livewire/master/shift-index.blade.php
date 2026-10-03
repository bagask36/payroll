<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Shift Kerja &amp; Jam Dinas
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Konfigurasi jadwal rotasi shift pagi, siang, malam, dan toleransi keterlambatan presensi.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreateShift">
                Tambah Shift Baru
            </flux:button>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <flux:card class="p-4">
        <div class="max-w-md">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari kode atau nama shift..." 
                icon="magnifying-glass"
                clearable
            />
        </div>
    </flux:card>

    {{-- Data Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Kode Shift</flux:table.column>
                <flux:table.column>Nama Shift</flux:table.column>
                <flux:table.column>Jam Kerja (Mulai - Selesai)</flux:table.column>
                <flux:table.column>Tipe Shift</flux:table.column>
                <flux:table.column>Toleransi Terlambat</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($shifts as $s)
                    <flux:table.row :key="$s->id">
                        <flux:table.cell class="font-mono font-semibold text-xs text-zinc-800 dark:text-zinc-200">
                            {{ $s->code }}
                        </flux:table.cell>
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $s->name }}
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            {{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($s->is_overnight)
                                <flux:badge size="xs" color="purple">Lintas Hari (Malam)</flux:badge>
                            @else
                                <flux:badge size="xs" color="zinc">Reguler Siang</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
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
                            Belum ada shift kerja yang terdaftar.
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

    {{-- MODAL SHIFT --}}
    <flux:modal wire:model="showShiftModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingShiftId ? 'Edit Shift Kerja' : 'Tambah Shift Kerja Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Tentukan jam masuk, jam pulang, dan batas toleransi keterlambatan.</flux:subheading>
        </div>

        <form wire:submit="saveShift" class="space-y-4">
            <flux:input wire:model="shiftCode" label="Kode Shift" placeholder="Misal: PAGI, SIANG, MALAM" required />
            <flux:input wire:model="shiftName" label="Nama Shift Dinas" placeholder="Misal: Shift Pagi Bangsal" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="shiftStartTime" type="time" label="Jam Masuk" required />
                <flux:input wire:model="shiftEndTime" type="time" label="Jam Pulang" required />
            </div>
            <flux:input wire:model="shiftLateTolerance" type="number" label="Toleransi Terlambat (Menit)" required />
            <div class="space-y-2 pt-1">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="shiftIsOvernight" id="shiftOvernight" class="rounded text-purple-600 focus:ring-purple-500">
                    <label for="shiftOvernight" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Shift Malam / Lintas Hari (Overnight)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="shiftIsActive" id="shiftActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="shiftActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Shift Berstatus Aktif</label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Shift</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

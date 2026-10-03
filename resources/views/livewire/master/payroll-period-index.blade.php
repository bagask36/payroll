<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Periode Payroll &amp; Cut-off
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Jadwal siklus penggajian bulanan, tanggal mulai/selesai perhitungan, dan cut-off kehadiran.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreatePeriod">
                Buka Periode Baru
            </flux:button>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
        </div>
    @endif

    {{-- Data Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nama Periode</flux:table.column>
                <flux:table.column>Rentang Tanggal Penggajian</flux:table.column>
                <flux:table.column>Tanggal Cut-Off Presensi</flux:table.column>
                <flux:table.column>Status Buku</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($periods as $per)
                    <flux:table.row :key="$per->id">
                        <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $per->name }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-600 dark:text-zinc-400 font-mono">
                            {{ $per->start_date?->format('d/m/Y') }} - {{ $per->end_date?->format('d/m/Y') }}
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $per->cutoff_date ? $per->cutoff_date->format('d/m/Y') : '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($per->is_closed)
                                <flux:badge size="xs" color="rose">Tutup Buku (Closed)</flux:badge>
                            @else
                                <flux:badge size="xs" color="emerald">Terbuka (Open)</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editPeriod({{ $per->id }})">
                                Edit
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8 text-zinc-400 italic">
                            Belum ada periode payroll yang terdaftar.
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

    {{-- MODAL PAYROLL PERIOD --}}
    <flux:modal wire:model="showPeriodModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingPeriodId ? 'Edit Periode Payroll' : 'Buka Periode Payroll Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Tentukan kalender penggajian dan cut-off presensi bulanan.</flux:subheading>
        </div>

        <form wire:submit="savePeriod" class="space-y-4">
            <flux:input wire:model="periodName" label="Nama Periode" placeholder="Misal: Payroll Periode November 2026" required />
            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="periodStartDate" type="date" label="Tanggal Mulai" required />
                <flux:input wire:model="periodEndDate" type="date" label="Tanggal Selesai" required />
            </div>
            <flux:input wire:model="periodCutoffDate" type="date" label="Tanggal Cut-Off Presensi" />
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="periodIsClosed" id="periodClosed" class="rounded text-rose-600 focus:ring-rose-500">
                <label for="periodClosed" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Tutup Buku (Closed)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Periode</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

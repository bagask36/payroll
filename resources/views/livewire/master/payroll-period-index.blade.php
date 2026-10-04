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

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.payroll-period-table />
    </flux:card>

    {{-- MODAL PAYROLL PERIOD --}}
    <flux:modal wire:model="showPeriodModal" class="md:w-xl space-y-4">
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

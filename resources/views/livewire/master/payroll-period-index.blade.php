<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Master Data: Periode Payroll &amp; Cut-off" 
        subtitle="Jadwal siklus penggajian bulanan, tanggal mulai/selesai perhitungan, dan cut-off kehadiran."
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="openCreatePeriod">
                Buka Periode Baru
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-flash-message :message="$feedbackMessage" />

    {{-- PowerGrid Datatable --}}
    <x-table-card>
        <livewire:tables.payroll-period-table />
    </x-table-card>

    {{-- MODAL PAYROLL PERIOD --}}
    <flux:modal wire:model="showPeriodModal" class="w-full max-w-2xl space-y-4">
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

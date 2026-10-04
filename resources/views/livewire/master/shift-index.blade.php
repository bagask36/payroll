<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Master Data: Shift Kerja &amp; Jam Dinas" 
        subtitle="Konfigurasi jadwal rotasi shift pagi, siang, malam, dan toleransi keterlambatan presensi."
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="openCreateShift">
                Tambah Shift Baru
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-flash-message :message="$feedbackMessage" />

    {{-- PowerGrid Datatable --}}
    <x-table-card>
        <livewire:tables.shift-table />
    </x-table-card>

    {{-- MODAL SHIFT --}}
    <flux:modal wire:model="showShiftModal" class="w-full max-w-2xl space-y-4">
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

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Jenis Cuti &amp; Kebijakan
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Aturan kuota tahunan cuti tahunan, cuti sakit, melahirkan, serta syarat lampiran dokumen medis.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreateLeaveType">
                Tambah Jenis Cuti
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
        <livewire:tables.leave-type-table />
    </flux:card>

    {{-- MODAL LEAVE TYPE --}}
    <flux:modal wire:model="showLeaveTypeModal" class="md:w-xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingLeaveTypeId ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Konfigurasi kuota tahunan dan aturan pemotongan saldo.</flux:subheading>
        </div>

        <form wire:submit="saveLeaveType" class="space-y-4">
            <flux:input wire:model="leaveTypeCode" label="Kode Cuti" placeholder="Misal: TAHUNAN, SAKIT, HAJI" required />
            <flux:input wire:model="leaveTypeName" label="Nama Cuti" placeholder="Misal: Cuti Ibadah Keagamaan" required />
            <flux:input wire:model="leaveTypeDefaultDays" type="number" label="Alokasi Hari per Tahun" required />
            <div class="space-y-2 pt-1">
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="leaveTypeDeductsBalance" id="ltDeduct" class="rounded text-amber-600 focus:ring-amber-500">
                    <label for="ltDeduct" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Memotong Saldo Cuti Tahunan Pegawai</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="leaveTypeRequiresAttachment" id="ltAttach" class="rounded text-rose-600 focus:ring-rose-500">
                    <label for="ltAttach" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Wajib Melampirkan Dokumen Bukti (Surat Sakit dsb)</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="leaveTypeIsActive" id="ltActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="ltActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Jenis Cuti Berstatus Aktif</label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Cuti</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

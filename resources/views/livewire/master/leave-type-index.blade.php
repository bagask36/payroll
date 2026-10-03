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

    {{-- Filter Toolbar --}}
    <flux:card class="p-4">
        <div class="max-w-md">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari kode atau nama jenis cuti..." 
                icon="magnifying-glass"
                clearable
            />
        </div>
    </flux:card>

    {{-- Data Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Kode Cuti</flux:table.column>
                <flux:table.column>Nama Jenis Cuti</flux:table.column>
                <flux:table.column>Alokasi Dasar (Hari/Tahun)</flux:table.column>
                <flux:table.column>Potong Saldo Tahunan?</flux:table.column>
                <flux:table.column>Wajib Lampiran Berkas?</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($leaveTypes as $lt)
                    <flux:table.row :key="$lt->id">
                        <flux:table.cell class="font-mono font-semibold text-xs text-zinc-800 dark:text-zinc-200">
                            {{ $lt->code }}
                        </flux:table.cell>
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $lt->name }}
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $lt->default_days_per_year }} Hari
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($lt->deducts_balance)
                                <flux:badge size="xs" color="amber">Memotong Kuota</flux:badge>
                            @else
                                <flux:badge size="xs" color="blue">Non-Kuota</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($lt->requires_attachment)
                                <flux:badge size="xs" color="rose">Wajib Surat Dokter/Lampiran</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">Tidak Wajib</span>
                            @endif
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
                        <flux:table.cell colspan="7" class="text-center py-8 text-zinc-400 italic">
                            Belum ada jenis cuti yang terdaftar.
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

    {{-- MODAL LEAVE TYPE --}}
    <flux:modal wire:model="showLeaveTypeModal" class="md:w-96 space-y-4">
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

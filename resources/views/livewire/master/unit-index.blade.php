<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Unit Kerja &amp; Ruangan
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Kelola daftar unit operasional, instalasi medis, dan ruangan kerja di lingkungan RS Juwita.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreateUnit">
                Tambah Unit Kerja
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
        <livewire:tables.unit-table />
    </flux:card>

    {{-- MODAL UNIT --}}
    <flux:modal wire:model="showUnitModal" class="md:w-xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingUnitId ? 'Edit Unit Kerja' : 'Tambah Unit Kerja Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Penamaan dan penempatan departemen rumah sakit.</flux:subheading>
        </div>

        <form wire:submit="saveUnit" class="space-y-4">
            <flux:input wire:model="unitCode" label="Kode Unit" placeholder="Misal: ICU, IGD, RAWAT-1" required />
            <flux:input wire:model="unitName" label="Nama Unit / Ruangan" placeholder="Misal: Instalasi Gawat Darurat" required />
            <flux:select wire:model="unitDepartmentId" label="Departemen Induk" required>
                <flux:select.option value="">-- Pilih Departemen --</flux:select.option>
                @foreach($departments as $dept)
                    <flux:select.option value="{{ $dept->id }}">{{ $dept->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="unitIsActive" id="unitActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="unitActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Unit Berstatus Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Unit</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

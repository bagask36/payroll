<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Master Data: Unit Kerja &amp; Ruangan" 
        subtitle="Kelola daftar unit operasional, instalasi medis, dan ruangan kerja di lingkungan RS Juwita."
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="openCreateUnit">
                Tambah Unit Kerja
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-flash-message :message="$feedbackMessage" />

    {{-- PowerGrid Datatable --}}
    <x-table-card>
        <livewire:tables.unit-table />
    </x-table-card>

    {{-- MODAL UNIT --}}
    <flux:modal wire:model="showUnitModal" class="w-full max-w-2xl space-y-4">
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

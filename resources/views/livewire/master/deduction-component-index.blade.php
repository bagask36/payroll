<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Komponen Potongan (Deductions)" 
        subtitle="Kelola master potongan kasbon, koperasi, denda keterlambatan, dan iuran profesi pegawai."
    >
        <x-slot:actions>
            <flux:button wire:click="openCreate" variant="primary" icon="plus" class="bg-rose-600 hover:bg-rose-700 text-white">
                Tambah Potongan
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    {{-- Feedback Alert --}}
    <x-flash-message :message="$feedbackMessage" type="danger" />

    {{-- PowerGrid Datatable --}}
    <x-table-card>
        <livewire:tables.deduction-component-table />
    </x-table-card>

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="w-full max-w-2xl space-y-4">
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Komponen Potongan' : 'Tambah Komponen Potongan Baru' }}
                </flux:heading>
                <flux:subheading>
                    Atur nama potongan, sifat potongan rutin atau kondisional, dan nominal acuan.
                </flux:subheading>
            </div>

            <div class="space-y-3">
                <flux:input wire:model="code" label="Kode Potongan" placeholder="Contoh: KOPERASI_RS" required />
                <flux:input wire:model="name" label="Nama Potongan" placeholder="Contoh: Iuran Koperasi Karyawan Sejahtera" required />
                <flux:input wire:model="defaultAmount" label="Nominal Standar (Rp)" type="number" step="1000" placeholder="100000" required />
                <flux:textarea wire:model="description" label="Keterangan / Dasar Pemotongan" placeholder="Deskripsi peruntukan atau syarat potongan..." rows="2" />

                <div class="pt-2">
                    <flux:checkbox wire:model="isFixed" label="Potongan Rutin Bulanan" description="Dipotong otomatis setiap payroll bulanan tanpa input manual" />
                </div>

                <div class="pt-1">
                    <flux:checkbox wire:model="isActive" label="Status Komponen Aktif" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="$set('showModal', false)" variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" class="bg-rose-600 hover:bg-rose-700 text-white">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('master.index') }}" class="text-xs text-zinc-500 hover:text-rose-600 dark:text-zinc-400">Data Master</a>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-rose-600 dark:text-rose-400">Komponen Potongan</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="receipt-refund" class="size-7 text-rose-600" />
                Komponen Potongan (Deductions)
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola master potongan kasbon, koperasi, denda keterlambatan, dan iuran profesi pegawai.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button wire:click="openCreate" variant="primary" icon="plus" class="bg-rose-600 hover:bg-rose-700 text-white">
                Tambah Potongan
            </flux:button>
        </div>
    </div>

    {{-- Feedback Alert --}}
    @if ($feedbackMessage)
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <flux:icon name="check-circle" class="size-5 text-rose-600 shrink-0" />
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-rose-500 hover:text-rose-700">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.deduction-component-table />
    </flux:card>

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

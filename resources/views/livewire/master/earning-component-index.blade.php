<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('master.index') }}" class="text-xs text-zinc-500 hover:text-emerald-600 dark:text-zinc-400">Data Master</a>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Komponen Penghasilan</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="currency-dollar" class="size-7 text-emerald-600" />
                Komponen Penghasilan (Allowances)
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola master tunjangan, premi shift, uang saku, dan penambah gaji pegawai RS Juwita.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button wire:click="openCreate" variant="primary" icon="plus" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                Tambah Komponen
            </flux:button>
        </div>
    </div>

    {{-- Feedback Alert --}}
    @if ($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 shrink-0" />
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-500 hover:text-emerald-700">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.earning-component-table />
    </flux:card>

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="w-full max-w-2xl space-y-4">
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Komponen Penghasilan' : 'Tambah Komponen Penghasilan Baru' }}
                </flux:heading>
                <flux:subheading>
                    Atur nama komponen, perlakuan pajak PPh 21, dan nominal standar.
                </flux:subheading>
            </div>

            <div class="space-y-3">
                <flux:input wire:model="code" label="Kode Komponen" placeholder="Contoh: TUNJ_TRANSPORT" required />
                <flux:input wire:model="name" label="Nama Komponen" placeholder="Contoh: Tunjangan Transportasi & Uang Makan" required />
                
                {{-- Sifat Tunjangan: Rutin vs Variabel --}}
                <div class="space-y-1.5 pt-1">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tipe / Sifat Tunjangan</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" 
                            wire:click="$set('isFixed', true)" 
                            class="text-left relative flex items-start gap-3 p-3 rounded-xl border transition-all cursor-pointer {{ $isFixed ? 'border-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20 ring-2 ring-emerald-600 shadow-xs' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 opacity-70 hover:opacity-100' }}">
                            <div class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border {{ $isFixed ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-400 bg-transparent' }}">
                                @if($isFixed)
                                    <div class="size-1.5 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="calendar" class="size-4 text-blue-600 dark:text-blue-400" />
                                    Tunjangan Rutin (Tetap)
                                </span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Diterima utuh per bulan secara otomatis tanpa dipengaruhi jumlah presensi.</p>
                            </div>
                        </button>
                        <button type="button" 
                            wire:click="$set('isFixed', false)" 
                            class="text-left relative flex items-start gap-3 p-3 rounded-xl border transition-all cursor-pointer {{ ! $isFixed ? 'border-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20 ring-2 ring-emerald-600 shadow-xs' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 opacity-70 hover:opacity-100' }}">
                            <div class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border {{ ! $isFixed ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-400 bg-transparent' }}">
                                @if(! $isFixed)
                                    <div class="size-1.5 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <flux:icon name="clock" class="size-4 text-emerald-600 dark:text-emerald-400" />
                                    Tunjangan Variabel
                                </span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Dihitung per hari kehadiran valid (present/late) dikali tarif harian saat payroll.</p>
                            </div>
                        </button>
                    </div>
                </div>

                <flux:input wire:model="defaultAmount" :label="$isFixed ? 'Nominal Standar Bulanan (Rp)' : 'Tarif Standar per Hari Hadir (Rp)'" type="number" step="1000" placeholder="50000" required />
                <flux:textarea wire:model="description" label="Keterangan / Aturan Pemberian" placeholder="Penjelasan atau kriteria penerima tunjangan..." rows="2" />

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <flux:checkbox wire:model="isTaxable" label="Objek Pajak PPh 21" description="Menambah Penghasilan Bruto kena pajak" />
                    <flux:checkbox wire:model="isActive" label="Status Komponen Aktif" description="Tersedia untuk diterapkan ke pegawai" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="$set('showModal', false)" variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 text-white">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

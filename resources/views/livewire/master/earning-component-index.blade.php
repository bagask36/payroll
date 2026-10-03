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

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="w-full sm:w-80">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari kode atau nama komponen..." 
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    {{-- Data Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Kode</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nama Komponen</th>
                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sifat & Pajak</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nominal Standar</th>
                    <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($components as $c)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap text-xs font-mono font-medium text-emerald-700 dark:text-emerald-400">
                            {{ $c->code }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $c->name }}</div>
                            @if($c->description)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $c->description }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <flux:badge size="sm" color="{{ $c->is_fixed ? 'blue' : 'zinc' }}">
                                    {{ $c->is_fixed ? 'Tetap' : 'Variabel/Harian' }}
                                </flux:badge>
                                <flux:badge size="sm" color="{{ $c->is_taxable ? 'amber' : 'zinc' }}">
                                    {{ $c->is_taxable ? 'Kena PPh 21' : 'Non-Pajak' }}
                                </flux:badge>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format((float) $c->default_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            <flux:badge size="sm" color="{{ $c->is_active ? 'emerald' : 'zinc' }}">
                                {{ $c->is_active ? 'Aktif' : 'Nonaktif' }}
                            </flux:badge>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button wire:click="edit({{ $c->id }})" size="xs" variant="subtle" icon="pencil-square">
                                    Edit
                                </flux:button>
                                <flux:button wire:click="toggleStatus({{ $c->id }})" size="xs" variant="ghost" class="text-zinc-500 hover:text-zinc-800">
                                    {{ $c->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-zinc-500">
                            <flux:icon name="currency-dollar" class="size-8 mx-auto text-zinc-400 mb-2" />
                            <p class="text-sm font-medium">Belum ada komponen penghasilan terdaftar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $components->links() }}
    </div>

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="md:w-lg">
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
                <flux:input wire:model="defaultAmount" label="Nominal Standar (Rp)" type="number" step="1000" placeholder="50000" required />
                <flux:textarea wire:model="description" label="Keterangan / Aturan Pemberian" placeholder="Penjelasan atau kriteria penerima tunjangan..." rows="2" />

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <flux:checkbox wire:model="isFixed" label="Tunjangan Tetap Bulanan" description="Diberikan rutin tiap bulan tanpa hitung kehadiran" />
                    <flux:checkbox wire:model="isTaxable" label="Objek Pajak PPh 21" description="Menambah Penghasilan Bruto kena pajak" />
                </div>

                <div class="pt-1">
                    <flux:checkbox wire:model="isActive" label="Status Komponen Aktif" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:button wire:click="$set('showModal', false)" variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 text-white">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

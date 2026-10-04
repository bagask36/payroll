<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('master.index') }}" class="text-xs text-zinc-500 hover:text-emerald-600 dark:text-zinc-400">Data Master</a>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tingkat & Jenis SP</span>
            </div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Tingkat &amp; Jenis Surat Peringatan (SP)
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Konfigurasi tingkatan sanksi disiplin pegawai, standar durasi masa berlaku (default 6 bulan), dan deskripsi pelanggaran.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                Tambah Jenis SP
            </flux:button>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('errorMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="flex items-center justify-between gap-4">
        <div class="w-full sm:w-80">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari kode atau jenis SP..." 
                icon="magnifying-glass"
                size="sm"
            />
        </div>
    </div>

    {{-- Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700/80 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 font-semibold">Tingkat (Level)</th>
                        <th class="px-4 py-3.5 font-semibold">Kode</th>
                        <th class="px-4 py-3.5 font-semibold">Nama Jenis Sanksi</th>
                        <th class="px-4 py-3.5 font-semibold">Masa Berlaku Default</th>
                        <th class="px-4 py-3.5 font-semibold">Deskripsi / Konsekuensi</th>
                        <th class="px-4 py-3.5 font-semibold text-center">Status</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-800 dark:text-zinc-200">
                    @forelse($warningLetterTypes as $type)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3">
                                @if($type->level === 0)
                                    <flux:badge size="sm" color="zinc">Level 0 (Teguran)</flux:badge>
                                @elseif($type->level === 1)
                                    <flux:badge size="sm" color="amber">Level 1 (SP I)</flux:badge>
                                @elseif($type->level === 2)
                                    <flux:badge size="sm" color="orange">Level 2 (SP II)</flux:badge>
                                @elseif($type->level === 3)
                                    <flux:badge size="sm" color="rose">Level 3 (SP III/Terakhir)</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">Level {{ $type->level }} (Berat)</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                {{ $type->code }}
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $type->name }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold">{{ $type->validity_months }}</span> Bulan
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 max-w-xs truncate" title="{{ $type->description }}">
                                {{ $type->description ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    wire:click="toggleStatus({{ $type->id }})" 
                                    class="cursor-pointer inline-flex items-center"
                                    title="Klik untuk mengubah status aktif"
                                >
                                    @if($type->is_active)
                                        <flux:badge size="sm" color="emerald">Aktif</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">Nonaktif</flux:badge>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $type->id }})">
                                    Edit
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                Belum ada data jenis surat peringatan yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warningLetterTypes->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $warningLetterTypes->links() }}
            </div>
        @endif
    </flux:card>

    {{-- MODAL TAMBAH / EDIT --}}
    <flux:modal wire:model="showModal" class="w-full max-w-2xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingId ? 'Edit Master Jenis SP' : 'Tambah Jenis SP Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Atur kode sanksi, tingkat keparahan, dan standar masa berlaku surat peringatan.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="code" label="Kode Sanksi" placeholder="Misal: SP-1, TEGURAN" required />
                <flux:input wire:model="level" type="number" min="0" max="10" label="Tingkat (Level)" placeholder="0 - 4" required />
            </div>

            <flux:input wire:model="name" label="Nama Jenis Surat Peringatan" placeholder="Misal: Surat Peringatan Pertama (SP I)" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="validityMonths" type="number" min="1" max="60" label="Masa Berlaku Standar (Bulan)" placeholder="6" required />
                <div class="flex items-center pt-7">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="isActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Status Aktif</span>
                    </label>
                </div>
            </div>

            <flux:textarea wire:model="description" label="Deskripsi / Konsekuensi Sanksi" placeholder="Keterangan pelanggaran atau dampak administratif sanksi ini..." rows="3" />

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Master SP</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

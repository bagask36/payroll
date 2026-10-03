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

    {{-- Filter & Search Toolbar --}}
    <flux:card class="p-4">
        <div class="max-w-md">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari kode atau nama unit kerja..." 
                icon="magnifying-glass"
                clearable
            />
        </div>
    </flux:card>

    {{-- Data Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Kode Unit</flux:table.column>
                <flux:table.column>Nama Unit Kerja / Ruangan</flux:table.column>
                <flux:table.column>Departemen Induk</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($units as $u)
                    <flux:table.row :key="$u->id">
                        <flux:table.cell class="font-mono font-semibold text-xs text-zinc-800 dark:text-zinc-200">
                            {{ $u->code }}
                        </flux:table.cell>
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $u->name }}
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-600 dark:text-zinc-400 text-xs">
                            {{ $u->department?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="xs" :color="$u->is_active ? 'emerald' : 'zinc'">
                                {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editUnit({{ $u->id }})">
                                Edit
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8 text-zinc-400 italic">
                            Belum ada unit kerja yang terdaftar atau cocok dengan pencarian.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($units->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $units->links() }}
            </div>
        @endif
    </flux:card>

    {{-- MODAL UNIT --}}
    <flux:modal wire:model="showUnitModal" class="md:w-96 space-y-4">
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

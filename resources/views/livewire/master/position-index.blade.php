<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Standar Jabatan Pegawai
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Katalog formasi jabatan medis, perawat, penunjang, dan jenjang golongan kepangkatan.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreatePosition">
                Tambah Jabatan Baru
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
                placeholder="Cari nama jabatan..." 
                icon="magnifying-glass"
                clearable
            />
        </div>
    </flux:card>

    {{-- Data Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nama Jabatan</flux:table.column>
                <flux:table.column>Unit Kerja Terkait</flux:table.column>
                <flux:table.column>Golongan / Jenjang</flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($positions as $pos)
                    <flux:table.row :key="$pos->id">
                        <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $pos->title }}
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-600 dark:text-zinc-400 text-xs">
                            {{ $pos->unit?->name ?? 'Lintas Unit' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="xs" color="blue">
                                {{ $pos->grade_level ?? 'III/a' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editPosition({{ $pos->id }})">
                                Edit
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-8 text-zinc-400 italic">
                            Belum ada standar jabatan yang terdaftar.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($positions->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $positions->links() }}
            </div>
        @endif
    </flux:card>

    {{-- MODAL POSITION --}}
    <flux:modal wire:model="showPositionModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingPositionId ? 'Edit Standar Jabatan' : 'Tambah Jabatan Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Penetapan gelar posisi dan rentang jenjang karir.</flux:subheading>
        </div>

        <form wire:submit="savePosition" class="space-y-4">
            <flux:input wire:model="positionTitle" label="Nama / Judul Jabatan" placeholder="Misal: Dokter Spesialis Jantung, Perawat Pelaksana" required />
            <flux:select wire:model="positionUnitId" label="Unit Kerja Terkait (Opsional)">
                <flux:select.option value="">-- Lintas Unit / Fleksibel --</flux:select.option>
                @foreach($units as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="positionGradeLevel" label="Golongan / Grade Level" placeholder="Misal: III/a, IV/b, Spesialis" />

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Jabatan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

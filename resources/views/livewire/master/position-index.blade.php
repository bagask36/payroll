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

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.position-table />
    </flux:card>

    {{-- MODAL POSITION --}}
    <flux:modal wire:model="showPositionModal" class="md:w-lg space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingPositionId ? 'Edit Standar Jabatan' : 'Tambah Jabatan Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Penetapan gelar posisi, jenjang karir, dan standar remunerasi.</flux:subheading>
        </div>

        <form wire:submit="savePosition" class="space-y-4">
            <flux:input wire:model="positionTitle" label="Nama / Judul Jabatan" placeholder="Misal: Dokter Spesialis Jantung, Perawat Pelaksana" required />
            <flux:select wire:model="positionUnitId" label="Unit Kerja Terkait (Opsional)">
                <flux:select.option value="">-- Lintas Unit / Fleksibel --</flux:select.option>
                @foreach($units as $u)
                    <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="positionReportsToId" label="Atasan Langsung / Supervisor (Reports To)">
                <flux:select.option value="">-- Tidak Memiliki Atasan Langsung (Level Puncak / Mandiri) --</flux:select.option>
                @foreach($allPositions as $pos)
                    <flux:select.option value="{{ $pos->id }}">{{ $pos->title }} ({{ $pos->unit?->name ?? 'Lintas Unit' }})</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="positionGradeLevel" label="Golongan / Grade Level" placeholder="Misal: III/a, IV/b, Spesialis" />

            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-3">
                <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                    Standar Remunerasi Jabatan
                </div>
                <p class="text-xs text-zinc-500">
                    Nilai ini akan terisi otomatis saat membuat SK Penetapan / Penugasan pegawai baru pada jabatan ini.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="positionDefaultBasicSalary" 
                        label="Standar Gaji Pokok (Rp)" 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        placeholder="misal: 6500000" 
                    />
                    <flux:input 
                        wire:model="positionDefaultPositionalAllowance" 
                        label="Standar Tunjangan Jabatan (Rp)" 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        placeholder="misal: 1500000" 
                    />
                </div>
            </div>

            {{-- SK Dasar Formasi / Penetapan Jabatan --}}
            <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                        SK Formasi &amp; Dasar Penetapan Jabatan
                    </div>
                    <span class="text-[11px] text-zinc-400">Otomatis ditarik ke Penugasan Pegawai</span>
                </div>
                <p class="text-xs text-zinc-500">
                    Nomor SK dan berkas scan fisik SK formasi ini akan otomatis terisi saat HR menetapkan pegawai pada jabatan ini.
                </p>

                <flux:input 
                    wire:model="positionDefaultContractNumber" 
                    label="Nomor SK Formasi / Penetapan Jabatan" 
                    placeholder="Misal: 015/SK-DIR/RSJ/2026 atau SK/DIR/2026/088" 
                />

                <div class="space-y-2 p-3 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900/40">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Upload Berkas Scan Fisik SK Jabatan</span>
                        <span class="text-[11px] text-zinc-400">PDF, JPG, PNG (Maks. 10MB)</span>
                    </div>

                    @if($positionExistingDocumentName)
                        <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300">
                            <div class="flex items-center gap-1.5 truncate">
                                <flux:icon name="document-check" class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                <span class="truncate">Berkas terpasang: <strong>{{ $positionExistingDocumentName }}</strong></span>
                            </div>
                            @if($editingPositionId)
                                <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadPositionDocument({{ $editingPositionId }})">
                                    Unduh
                                </flux:button>
                            @endif
                        </div>
                    @endif

                    <flux:input type="file" wire:model="positionSkDocumentFile" accept=".pdf,.jpg,.jpeg,.png" />
                    <div wire:loading wire:target="positionSkDocumentFile" class="text-xs text-sky-600 dark:text-sky-400">Mengunggah berkas SK jabatan…</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="positionSkDocumentFile">Simpan Jabatan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Arsip Dokumen Kepatuhan Pegawai & Medis
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Penyimpanan terenkripsi dokumen resmi: KTP, KK, Ijazah, Kontrak Kerja, Lisensi STR & SIP.
            </flux:subheading>
        </div>

        @can('create', App\Models\Document::class)
            <div>
                <flux:button variant="primary" icon="arrow-up-tray" wire:click="openUploadModal">
                    Unggah Dokumen Baru
                </flux:button>
            </div>
        @endcan
    </div>

    {{-- Feedback & Error Banners --}}
    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400 shrink-0" />
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('errorMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Documents PowerGrid Table --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.document-table />
    </flux:card>

    {{-- Upload Document Modal --}}
    <flux:modal wire:model="showUploadModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">Unggah Dokumen Kepatuhan</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Format PDF, JPG, atau PNG (Maksimal 10 MB).</flux:subheading>
        </div>

        <form wire:submit="uploadDocument" class="space-y-4">
            @can('document.upload_all')
                @if($employees->isNotEmpty())
                    <flux:select wire:model="targetEmployeeId" label="Pilih Pegawai Pemilik Dokumen" required>
                        @foreach($employees as $emp)
                            <flux:select.option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->nip }})</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            @endcan

            <flux:select wire:model="uploadType" label="Jenis Dokumen Resmi" required>
                <flux:select.option value="certificate">Sertifikat Pelatihan Medis</flux:select.option>
                <flux:select.option value="str">Surat Tanda Registrasi (STR)</flux:select.option>
                <flux:select.option value="sip">Surat Izin Praktik (SIP)</flux:select.option>
                <flux:select.option value="contract">Surat Perjanjian Kerja (SPK)</flux:select.option>
                <flux:select.option value="ktp">KTP Elektronik</flux:select.option>
                <flux:select.option value="kk">Kartu Keluarga (KK)</flux:select.option>
                <flux:select.option value="ijazah">Ijazah & Transkrip Pendidikan</flux:select.option>
                <flux:select.option value="other">Dokumen Pendukung Lainnya</flux:select.option>
            </flux:select>

            <div>
                <label class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">Pilih Berkas</label>
                <input 
                    type="file" 
                    wire:model="uploadedFile" 
                    class="block w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                    required
                />
                @error('uploadedFile') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Unggah & Arsipkan
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

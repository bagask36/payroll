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

    {{-- DataTable Toolbar with Search, Filters, and Per-Page Selector --}}
    <x-datatable.toolbar 
        :search="$search" 
        search-placeholder="Cari nama berkas dokumen..." 
        :per-page="$perPage"
        :active-filters-count="$this->activeFiltersCount"
    >
        <flux:select wire:model.live="documentTypeFilter" label="Jenis Dokumen">
            <flux:select.option value="all">Semua Jenis Dokumen</flux:select.option>
            <flux:select.option value="ktp">Kartu Tanda Penduduk (KTP)</flux:select.option>
            <flux:select.option value="kk">Kartu Keluarga (KK)</flux:select.option>
            <flux:select.option value="ijazah">Ijazah & Transkrip</flux:select.option>
            <flux:select.option value="contract">Perjanjian Kontrak Kerja</flux:select.option>
            <flux:select.option value="str">Surat Tanda Registrasi (STR)</flux:select.option>
            <flux:select.option value="sip">Surat Izin Praktik (SIP)</flux:select.option>
            <flux:select.option value="certificate">Sertifikat Pelatihan Medis</flux:select.option>
            <flux:select.option value="other">Dokumen Lainnya</flux:select.option>
        </flux:select>
    </x-datatable.toolbar>

    {{-- Documents Table --}}
    <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <flux:table :paginate="$documents">
            <flux:table.columns>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'original_name'" 
                    :direction="$sortDirection" 
                    wire:click="sort('original_name')"
                >
                    Nama Dokumen & Berkas
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'document_type'" 
                    :direction="$sortDirection" 
                    wire:click="sort('document_type')"
                >
                    Jenis Dokumen
                </flux:table.column>
                <flux:table.column>Pemilik (Personil)</flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'file_size'" 
                    :direction="$sortDirection" 
                    wire:click="sort('file_size')"
                >
                    Format & Ukuran
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'created_at'" 
                    :direction="$sortDirection" 
                    wire:click="sort('created_at')"
                >
                    Tanggal Unggah
                </flux:table.column>
                <flux:table.column align="end">Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($documents as $doc)
                    <flux:table.row :key="$doc->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40 transition-colors">
                        <flux:table.cell>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <flux:icon name="document-text" class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span class="truncate max-w-xs">{{ $doc->original_name }}</span>
                            </div>
                            <div class="text-[11px] font-mono text-zinc-400 mt-0.5">
                                UUID: {{ substr($doc->uuid, 0, 13) }}...
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $badgeColor = match($doc->document_type) {
                                    'str', 'sip' => 'purple',
                                    'contract' => 'blue',
                                    'ktp', 'kk' => 'amber',
                                    'ijazah', 'certificate' => 'emerald',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="xs" color="{{ $badgeColor }}" inset="top bottom">
                                {{ strtoupper($doc->document_type) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-xs text-zinc-600 dark:text-zinc-300">
                            @if($doc->documentable instanceof \App\Models\Employee)
                                <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ $doc->documentable->full_name }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">Pegawai ({{ $doc->documentable->nip }})</div>
                            @elseif($doc->documentable instanceof \App\Models\Doctor)
                                <div class="font-medium text-emerald-700 dark:text-emerald-400">{{ $doc->documentable->name }}</div>
                                <div class="text-[11px] text-zinc-400">Dokter Spesialis</div>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-xs font-mono text-zinc-500">
                            <div>{{ strtoupper(substr($doc->mime_type ?? 'bin', -3)) }}</div>
                            <div class="text-[11px] text-zinc-400">{{ number_format($doc->file_size / 1024, 1) }} KB</div>
                        </flux:table.cell>

                        <flux:table.cell class="text-xs text-zinc-500 font-mono">
                            {{ $doc->created_at->format('d/m/Y H:i') }}
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            @can('download', $doc)
                                <flux:button 
                                    size="xs" 
                                    variant="subtle" 
                                    icon="arrow-down-tray"
                                    wire:click="generateDownloadUrl({{ $doc->id }})"
                                >
                                    Unduh Berkas
                                </flux:button>
                            @endcan
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="p-0">
                            <x-datatable.empty 
                                title="Dokumen Tidak Ditemukan" 
                                description="Belum ada dokumen kepatuhan yang tersimpan dalam arsip atau tidak ada data yang cocok dengan kriteria pencarian Anda." 
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
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

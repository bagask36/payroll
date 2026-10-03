<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.index') }}">
                <flux:button size="sm" variant="subtle" icon="arrow-left">
                    Kembali ke Direktori
                </flux:button>
            </a>
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $employee->name }}
                </flux:heading>
                <flux:subheading class="text-zinc-500 font-mono text-xs">
                    NIP: {{ $employee->nip }}
                </flux:subheading>
            </div>
        </div>

        <div>
            @if($employee->status === 'ACTIVE')
                <flux:badge size="lg" color="emerald">Pegawai Aktif</flux:badge>
            @else
                <flux:badge size="lg" color="zinc">Non-aktif</flux:badge>
            @endif
        </div>
    </div>

    {{-- Profile Summary Header Card --}}
    <flux:card class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
            <span class="text-xs font-medium text-zinc-500">Unit / Departemen</span>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $employee->unit?->name ?? 'Belum Ditugaskan' }}
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Jabatan Saat Ini</span>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $employee->position?->title ?? 'Staf' }}
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Ikatan Kerja</span>
            <div class="mt-1">
                @php $contract = $employee->currentEmployment?->contract_type; @endphp
                @if($contract === 'permanent')
                    <flux:badge size="sm" color="emerald">Pegawai Tetap (PKWTT)</flux:badge>
                @elseif($contract === 'contract')
                    <flux:badge size="sm" color="blue">Kontrak (PKWT)</flux:badge>
                @elseif($contract === 'probation')
                    <flux:badge size="sm" color="amber">Masa Percobaan</flux:badge>
                @else
                    <span class="text-xs text-zinc-400">-</span>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Tanggal Bergabung</span>
            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $employee->currentEmployment?->start_date ? $employee->currentEmployment->start_date->format('d F Y') : '-' }}
            </div>
        </div>
    </flux:card>

    {{-- Tabs Navigation --}}
    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2">
        <flux:button 
            size="sm" 
            :variant="$activeTab === 'bio' ? 'primary' : 'subtle'" 
            wire:click="setTab('bio')"
        >
            Biodata & Identitas
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'employment' ? 'primary' : 'subtle'" 
            wire:click="setTab('employment')"
        >
            Riwayat Penugasan
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'salary' ? 'primary' : 'subtle'" 
            wire:click="setTab('salary')"
        >
            Kompensasi & Gaji
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'bank' ? 'primary' : 'subtle'" 
            wire:click="setTab('bank')"
        >
            Rekening Payroll
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'documents' ? 'primary' : 'subtle'" 
            wire:click="setTab('documents')"
        >
            Berkas & Kepatuhan
        </flux:button>
    </div>

    {{-- Tab 1: Biodata & PII --}}
    @if($activeTab === 'bio')
        <flux:card class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Informasi Pribadi & Kontak</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <span class="text-xs text-zinc-500">Nama Lengkap</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->name }}</p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Email Rumah Sakit</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->email }}</p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Nomor Telepon</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->phone }}</p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Jenis Kelamin</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                        {{ $employee->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Tanggal Lahir</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                        {{ $employee->birth_date ? $employee->birth_date->format('d F Y') : '-' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Nomor Induk Kependudukan (NIK KTP)</span>
                    <p class="font-mono font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                        @if($canViewSensitive)
                            {{ $employee->ktp_number }}
                        @else
                            {{ substr((string)$employee->ktp_number, 0, 4) . '************' }}
                            <span class="text-xs text-zinc-400 font-sans block">(Tersamarkan - Akses PII Dibatasi)</span>
                        @endif
                    </p>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Tab 2: Riwayat Penugasan & Kontrak --}}
    @if($activeTab === 'employment')
        <flux:card class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Riwayat Ikatan Kerja & Penempatan Unit</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Unit / Bagian</th>
                            <th class="px-4 py-3">Jabatan</th>
                            <th class="px-4 py-3">Ikatan Kerja</th>
                            <th class="px-4 py-3">Mulai Tugas</th>
                            <th class="px-4 py-3">Akhir Kontrak</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($employee->employments as $empRec)
                            <tr>
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $employee->unit?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $employee->position?->title ?? '-' }}</td>
                                <td class="px-4 py-3 uppercase text-xs">{{ $empRec->contract_type }}</td>
                                <td class="px-4 py-3 text-xs">{{ $empRec->start_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $empRec->end_date?->format('d/m/Y') ?? 'Tidak Terbatas' }}</td>
                                <td class="px-4 py-3">
                                    @if($empRec->is_active)
                                        <flux:badge size="sm" color="emerald">Penugasan Aktif</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">Selesai</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-zinc-400 italic">Belum ada riwayat penugasan tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    {{-- Tab 3: Kompensasi & Gaji (Strictly Protected) --}}
    @if($activeTab === 'salary')
        @if($canViewSalary)
            <flux:card class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Struktur Kompensasi & Gaji Pokok</h3>
                @php $latestSalary = $employee->currentSalary ?? $employee->salaries->first(); @endphp
                @if($latestSalary)
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                        <div>
                            <span class="text-xs text-zinc-500">Gaji Pokok (Base Salary)</span>
                            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                Rp {{ number_format((float)$latestSalary->basic_salary, 0, ',', '.') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500">Berlaku Efektif Sejak</span>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1">
                                {{ $latestSalary->effective_from?->format('d F Y') ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500">Alasan Penetapan / Revisi</span>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mt-1">
                                {{ $latestSalary->revision_reason ?? 'Penetapan awal kontrak' }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-zinc-400 italic">Belum ada struktur gaji aktif yang ditentukan untuk pegawai ini.</p>
                @endif
            </flux:card>
        @else
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 text-center space-y-2">
                <flux:icon name="lock-closed" class="w-8 h-8 text-zinc-400 mx-auto" />
                <h4 class="font-semibold text-zinc-800 dark:text-zinc-200">Akses Terbatas: Data Gaji & Kompensasi</h4>
                <p class="text-xs text-zinc-500 max-w-md mx-auto">
                    Data gaji hanya dapat diakses oleh Manajemen SDM dan Pejabat Penggajian berwenang untuk mematuhi regulasi kerahasiaan keuangan rumah sakit.
                </p>
            </div>
        @endif
    @endif

    {{-- Tab 4: Rekening Payroll --}}
    @if($activeTab === 'bank')
        @if($canViewBank)
            <flux:card class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Rekening Bank Pencairan Gaji</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    @forelse($employee->bankAccounts as $bank)
                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">{{ $bank->bank_name }}</span>
                                @if($bank->is_primary)
                                    <flux:badge size="sm" color="emerald">Utama</flux:badge>
                                @endif
                            </div>
                            <div class="text-xs text-zinc-500">
                                <div>Atas Nama: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bank->account_holder }}</span></div>
                                <div class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $bank->account_number }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400 italic col-span-3">Belum ada nomor rekening bank terdaftar.</p>
                    @endforelse
                </div>
            </flux:card>
        @else
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 text-center space-y-2">
                <flux:icon name="lock-closed" class="w-8 h-8 text-zinc-400 mx-auto" />
                <h4 class="font-semibold text-zinc-800 dark:text-zinc-200">Akses Terbatas: Data Perbankan</h4>
                <p class="text-xs text-zinc-500 max-w-md mx-auto">
                    Nomor rekening pencairan gaji dilindungi sesuai kebijakan privasi perbankan pegawai.
                </p>
            </div>
        @endif
    @endif

    {{-- Tab 5: Berkas & Dokumen Kepatuhan --}}
    @if($activeTab === 'documents')
        <flux:card class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Arsip Berkas Digital Pegawai</h3>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($employee->documents as $doc)
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <flux:icon name="document-text" class="w-6 h-6 text-zinc-400" />
                            <div>
                                <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $doc->document_type }}</span>
                                <p class="text-xs text-zinc-400">{{ $doc->file_name }} ({{ number_format($doc->file_size_bytes / 1024, 1) }} KB)</p>
                            </div>
                        </div>
                        <div>
                            @can('download', $doc)
                                <a href="{{ $doc->getTemporaryDownloadUrl() }}" target="_blank">
                                    <flux:button size="xs" variant="subtle" icon="arrow-down-tray">
                                        Unduh
                                    </flux:button>
                                </a>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-zinc-400 italic">Belum ada dokumen kepatuhan yang diunggah.</p>
                @endforelse
            </div>
        </flux:card>
    @endif
</div>

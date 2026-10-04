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

        <div class="flex items-center gap-2">
            @if($hasActiveSp)
                <button type="button" wire:click="setTab('disciplinary')" class="cursor-pointer" title="Klik untuk lihat rincian sanksi SP">
                    <flux:badge size="lg" color="rose" icon="exclamation-triangle" class="hover:opacity-80 transition-opacity">
                        Dalam Sanksi SP
                    </flux:badge>
                </button>
            @endif
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

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'biometrics' ? 'primary' : 'subtle'" 
            wire:click="setTab('biometrics')"
        >
            Biometrik Wajah
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'disciplinary' ? 'primary' : 'subtle'" 
            wire:click="setTab('disciplinary')"
            class="relative inline-flex items-center gap-1.5"
        >
            <span>Sanksi &amp; SP</span>
            @if($hasActiveSp)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white animate-pulse">
                    Aktif
                </span>
            @endif
        </flux:button>
    </div>

    {{-- Tab 1: Biodata & PII --}}
    @if($activeTab === 'bio')
        @php
            $maritalLabels = ['single' => 'Belum Menikah', 'married' => 'Menikah', 'divorced' => 'Cerai Hidup', 'widowed' => 'Cerai Mati'];
        @endphp
        <div class="space-y-4">
            @if($bioFeedback)
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-2">
                    <flux:icon name="check-circle" class="w-5 h-5 shrink-0" />
                    <span>{{ $bioFeedback }}</span>
                </div>
            @endif

            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Informasi Pribadi & Kontak</h3>
                    @if($canUpdateBio)
                        <flux:button variant="primary" size="sm" icon="pencil-square" wire:click="openBioModal">
                            Edit Biodata
                        </flux:button>
                    @endif
                </div>
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
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->phone ?: '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Jenis Kelamin</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                            {{ $employee->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Tempat, Tanggal Lahir</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                            {{ $employee->birth_place ?: '-' }}, {{ $employee->birth_date ? $employee->birth_date->format('d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Status Pernikahan / Tanggungan PTKP</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                            {{ $maritalLabels[$employee->marital_status] ?? '-' }} · {{ (int) $employee->tax_dependents }} tanggungan
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Agama / Hari Raya</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5 capitalize">
                            {{ $employee->religion ?: '-' }}
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
                    <div>
                        <span class="text-xs text-zinc-500">NPWP</span>
                        <p class="font-mono font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                            @if($canViewSensitive)
                                {{ $employee->npwp_number ?: '-' }}
                            @else
                                <span class="text-xs text-zinc-400 font-sans">(Akses PII Dibatasi)</span>
                            @endif
                        </p>
                    </div>
                    <div class="sm:col-span-2 md:col-span-1">
                        <span class="text-xs text-zinc-500">Alamat Domisili</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                            @if($canViewSensitive)
                                {{ $employee->address ?: '-' }}
                            @else
                                <span class="text-xs text-zinc-400">(Akses PII Dibatasi)</span>
                            @endif
                        </p>
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Tab 2: Riwayat Penugasan & Kontrak --}}
    @if($activeTab === 'employment')
        <div class="space-y-4">
            @if($employmentFeedback)
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span>{{ $employmentFeedback }}</span>
                    </div>
                    <button type="button" wire:click="$set('employmentFeedback', null)" class="text-emerald-600 hover:text-emerald-800">
                        <flux:icon name="x-mark" class="w-4 h-4" />
                    </button>
                </div>
            @endif

            @if($employmentError)
                <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="exclamation-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                        <span>{{ $employmentError }}</span>
                    </div>
                    <button type="button" wire:click="$set('employmentError', null)" class="text-rose-600 hover:text-rose-800">
                        <flux:icon name="x-mark" class="w-4 h-4" />
                    </button>
                </div>
            @endif

            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Riwayat Ikatan Kerja &amp; Penempatan Unit</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Catatan historis mutasi unit, promosi jabatan, dan pembaharuan ikatan kerja.</p>
                    </div>
                    @if($canUpdateEmployment)
                        <flux:button variant="primary" size="sm" icon="plus" wire:click="openEmploymentModal">
                            Catat Penugasan / SK Baru
                        </flux:button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">No. SK / Kontrak</th>
                                <th class="px-4 py-3">Unit / Bagian</th>
                                <th class="px-4 py-3">Jabatan</th>
                                <th class="px-4 py-3">Ikatan Kerja</th>
                                <th class="px-4 py-3">Mulai Tugas</th>
                                <th class="px-4 py-3">Akhir Kontrak</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Berkas SK</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($employee->employments as $empRec)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $empRec->contract_number }}
                                        @if($empRec->notes)
                                            <span class="block text-[11px] font-sans font-normal text-zinc-400">{{ $empRec->notes }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $empRec->unit?->name ?? ($employee->unit?->name ?? '-') }}</td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $empRec->position?->title ?? ($employee->position?->title ?? '-') }}</td>
                                    <td class="px-4 py-3 uppercase text-xs font-medium">
                                        @if($empRec->contract_type === 'permanent')
                                            Tetap (PKWTT)
                                        @elseif($empRec->contract_type === 'contract')
                                            Kontrak (PKWT)
                                        @elseif($empRec->contract_type === 'probation')
                                            Percobaan
                                        @else
                                            {{ $empRec->contract_type }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ $empRec->start_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $empRec->end_date?->format('d/m/Y') ?? 'Tidak Terbatas' }}</td>
                                    <td class="px-4 py-3">
                                        @if($empRec->is_active)
                                            <flux:badge size="sm" color="emerald">Penugasan Aktif</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">Selesai</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        @if($empRec->document)
                                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadEmploymentDocument({{ $empRec->id }})">
                                                Unduh SK
                                            </flux:button>
                                        @else
                                            <span class="text-zinc-400 italic">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs">
                                        @if($canUpdateEmployment)
                                            <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editEmployment({{ $empRec->id }})">
                                                Edit / Lengkapi
                                            </flux:button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-zinc-400 italic">Belum ada riwayat penugasan tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>

            {{-- Catatan Kedisiplinan & Riwayat Sanksi (Cross-reference) --}}
            <flux:card class="space-y-3 border-dashed">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="exclamation-triangle" class="size-4 text-amber-500" />
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
                            Catatan Kedisiplinan &amp; Sanksi Disiplin Pegawai
                        </h4>
                    </div>
                    <flux:button size="xs" variant="subtle" icon="arrow-right" wire:click="setTab('disciplinary')">
                        Buka Tab Sanksi &amp; SP
                    </flux:button>
                </div>
                @if($hasActiveSp)
                    <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between">
                        <div>
                            <strong>Perhatian SDM:</strong> Pegawai memiliki sanksi Surat Peringatan (SP) yang <strong>Berlaku Aktif</strong>. Harap jadikan pertimbangan utama dalam evaluasi masa kerja, kenaikan jabatan, rotasi, atau perpanjangan ikatan dinas.
                        </div>
                        <flux:badge size="sm" color="rose">Ada SP Aktif</flux:badge>
                    </div>
                @else
                    <div class="text-xs text-zinc-500 flex items-center justify-between">
                        <span>Pegawai memiliki rekam kedisiplinan tertib (tidak ada sanksi Surat Peringatan yang sedang berlaku aktif).</span>
                        <flux:badge size="sm" color="emerald">Disiplin Tertib</flux:badge>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif

    {{-- Tab 3: Kompensasi & Gaji (Strictly Protected) --}}
    @if($activeTab === 'salary')
        @if($canViewSalary)
            <div class="space-y-6">
                @if($salaryFeedback)
                    <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <span>{{ $salaryFeedback }}</span>
                        </div>
                        <button type="button" wire:click="$set('salaryFeedback', null)" class="text-emerald-600 hover:text-emerald-800">
                            <flux:icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @endif

                @if($salaryError)
                    <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon name="exclamation-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                            <span>{{ $salaryError }}</span>
                        </div>
                        <button type="button" wire:click="$set('salaryError', null)" class="text-rose-600 hover:text-rose-800">
                            <flux:icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @endif

                {{-- 1. Gaji Pokok Card --}}
                <flux:card class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Gaji Pokok (Base Salary)</h3>
                            <p class="text-xs text-zinc-400 mt-0.5">Penetapan gaji pokok resmi berdasarkan surat keputusan dan kontrak kerja.</p>
                        </div>
                        @if($canUpdateSalary)
                            <flux:button variant="primary" size="sm" icon="pencil-square" wire:click="openSalaryModal">
                                Revisi Gaji Pokok
                            </flux:button>
                        @endif
                    </div>

                    @php $latestSalary = $employee->currentSalary ?? $employee->salaries->first(); @endphp
                    @if($latestSalary)
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                            <div>
                                <span class="text-xs text-zinc-500">Gaji Pokok Aktif</span>
                                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
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
                                <span class="text-xs text-zinc-500">Alasan Penetapan / SK</span>
                                <p class="text-sm text-zinc-700 dark:text-zinc-300 mt-1">
                                    {{ $latestSalary->revision_reason ?? 'Penetapan awal kontrak' }}
                                </p>
                            </div>
                        </div>

                        {{-- Riwayat Perubahan Gaji jika ada > 1 --}}
                        @if($employee->salaries->count() > 1)
                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-2">Riwayat Revisi Gaji Pokok</span>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left">
                                        <thead class="text-zinc-400 bg-zinc-100/60 dark:bg-zinc-800/40">
                                            <tr>
                                                <th class="py-2 px-3">Gaji Pokok</th>
                                                <th class="py-2 px-3">Masa Berlaku</th>
                                                <th class="py-2 px-3">Alasan Penetapan</th>
                                                <th class="py-2 px-3">Ditetapkan Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                            @foreach($employee->salaries()->latest('effective_from')->get() as $sal)
                                                <tr>
                                                    <td class="py-2 px-3 font-mono font-medium">Rp {{ number_format((float)$sal->basic_salary, 0, ',', '.') }}</td>
                                                    <td class="py-2 px-3">
                                                        {{ $sal->effective_from?->format('d/m/Y') }} s/d {{ $sal->effective_to?->format('d/m/Y') ?? 'Sekarang' }}
                                                    </td>
                                                    <td class="py-2 px-3">{{ $sal->revision_reason }}</td>
                                                    <td class="py-2 px-3 text-zinc-400">{{ $sal->createdBy?->name ?? 'Sistem' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-zinc-400 italic">Belum ada struktur gaji aktif yang ditentukan untuk pegawai ini.</p>
                    @endif
                </flux:card>

                {{-- 2. Komponen Gaji Karyawan Card --}}
                <flux:card class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Komponen Gaji Karyawan (Tunjangan & Potongan Rutin)</h3>
                            <p class="text-xs text-zinc-400 mt-0.5">Komponen rutin yang otomatis dimasukkan dalam kalkulasi penggajian bulanan.</p>
                        </div>
                        @if($canUpdateSalary)
                            <flux:button variant="primary" size="sm" icon="plus" wire:click="openComponentModal">
                                Tambah Komponen Gaji
                            </flux:button>
                        @endif
                    </div>

                    @php
                        $allowances = $assignedComponents->filter(fn($c) => $c->component?->type === 'allowance');
                        $deductions = $assignedComponents->filter(fn($c) => $c->component?->type === 'deduction');
                        $totalAllowances = $allowances->where('is_active', true)->sum('amount');
                        $totalDeductions = $deductions->where('is_active', true)->sum('amount');
                        $currentBase = (float) ($latestSalary?->basic_salary ?? 0);
                        $estGross = $currentBase + $totalAllowances;
                        $estTakeHome = $estGross - $totalDeductions;
                    @endphp

                    {{-- Summary Box --}}
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-zinc-50/80 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700">
                        <div>
                            <span class="text-xs text-zinc-500">Gaji Pokok</span>
                            <p class="text-sm font-bold font-mono text-zinc-800 dark:text-zinc-200 mt-0.5">Rp {{ number_format($currentBase, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">+ Total Tunjangan Rutin</span>
                            <p class="text-sm font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5">Rp {{ number_format($totalAllowances, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-rose-600 dark:text-rose-400 font-medium">- Total Potongan Rutin</span>
                            <p class="text-sm font-bold font-mono text-rose-700 dark:text-rose-300 mt-0.5">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 font-medium">Estimasi Penghasilan Kotor</span>
                            <p class="text-sm font-bold font-mono text-zinc-900 dark:text-zinc-100 mt-0.5">Rp {{ number_format($estGross, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Section A: Tunjangan (Allowances) --}}
                    <div class="space-y-2 mt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                1. Daftar Tunjangan Rutin (Allowances)
                            </span>
                            <span class="text-xs text-zinc-400">Total: Rp {{ number_format($totalAllowances, 0, ',', '.') }}</span>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                                    <tr>
                                        <th class="py-2.5 px-3">Komponen</th>
                                        <th class="py-2.5 px-3">Nominal / Bulan</th>
                                        <th class="py-2.5 px-3">Masa Berlaku</th>
                                        <th class="py-2.5 px-3">Keterangan</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        @if($canUpdateSalary)
                                            <th class="py-2.5 px-3 text-right">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @forelse($allowances as $item)
                                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <td class="py-2.5 px-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $item->component?->name ?? 'Komponen' }}
                                                <span class="block text-[11px] font-mono text-zinc-400">{{ $item->component?->code }}</span>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                                Rp {{ number_format((float)$item->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-300">
                                                {{ $item->effective_from?->format('d/m/Y') }}
                                                @if($item->effective_to)
                                                    s/d {{ $item->effective_to->format('d/m/Y') }}
                                                @else
                                                    <span class="text-zinc-400">(Seterusnya)</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-zinc-500">{{ $item->notes ?: '-' }}</td>
                                            <td class="py-2.5 px-3">
                                                @if($item->is_active)
                                                    <flux:badge size="xs" color="emerald">Aktif</flux:badge>
                                                @else
                                                    <flux:badge size="xs" color="zinc">Nonaktif</flux:badge>
                                                @endif
                                            </td>
                                            @if($canUpdateSalary)
                                                <td class="py-2.5 px-3 text-right space-x-1">
                                                    <flux:button variant="ghost" size="xs" icon="pencil" wire:click="openComponentModal({{ $item->id }})">
                                                        Edit
                                                    </flux:button>
                                                    <flux:button variant="danger" size="xs" icon="trash" wire:confirm="Hapus komponen ini dari pegawai?" wire:click="deleteComponent({{ $item->id }})">
                                                        Hapus
                                                    </flux:button>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canUpdateSalary ? 6 : 5 }}" class="py-4 text-center text-zinc-400 italic">
                                                Belum ada tunjangan rutin yang ditetapkan untuk pegawai ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Section B: Potongan (Deductions) --}}
                    <div class="space-y-2 mt-6">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">
                                2. Daftar Potongan Rutin (Deductions)
                            </span>
                            <span class="text-xs text-zinc-400">Total: Rp {{ number_format($totalDeductions, 0, ',', '.') }}</span>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                                    <tr>
                                        <th class="py-2.5 px-3">Komponen Potongan</th>
                                        <th class="py-2.5 px-3">Nominal / Bulan</th>
                                        <th class="py-2.5 px-3">Masa Berlaku</th>
                                        <th class="py-2.5 px-3">Keterangan</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        @if($canUpdateSalary)
                                            <th class="py-2.5 px-3 text-right">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @forelse($deductions as $item)
                                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <td class="py-2.5 px-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $item->component?->name ?? 'Potongan' }}
                                                <span class="block text-[11px] font-mono text-zinc-400">{{ $item->component?->code }}</span>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-rose-700 dark:text-rose-400">
                                                -Rp {{ number_format((float)$item->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-zinc-600 dark:text-zinc-300">
                                                {{ $item->effective_from?->format('d/m/Y') }}
                                                @if($item->effective_to)
                                                    s/d {{ $item->effective_to->format('d/m/Y') }}
                                                @else
                                                    <span class="text-zinc-400">(Seterusnya)</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-zinc-500">{{ $item->notes ?: '-' }}</td>
                                            <td class="py-2.5 px-3">
                                                @if($item->is_active)
                                                    <flux:badge size="xs" color="emerald">Aktif</flux:badge>
                                                @else
                                                    <flux:badge size="xs" color="zinc">Nonaktif</flux:badge>
                                                @endif
                                            </td>
                                            @if($canUpdateSalary)
                                                <td class="py-2.5 px-3 text-right space-x-1">
                                                    <flux:button variant="ghost" size="xs" icon="pencil" wire:click="openComponentModal({{ $item->id }})">
                                                        Edit
                                                    </flux:button>
                                                    <flux:button variant="danger" size="xs" icon="trash" wire:confirm="Hapus potongan ini dari pegawai?" wire:click="deleteComponent({{ $item->id }})">
                                                        Hapus
                                                    </flux:button>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canUpdateSalary ? 6 : 5 }}" class="py-4 text-center text-zinc-400 italic">
                                                Belum ada potongan rutin yang ditetapkan untuk pegawai ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </flux:card>
            </div>
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
            <div class="space-y-4">
                @if($bankFeedback)
                    <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-2">
                        <flux:icon name="check-circle" class="w-5 h-5 shrink-0" />
                        <span>{{ $bankFeedback }}</span>
                    </div>
                @endif

                <flux:card class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Rekening Bank Pencairan Gaji</h3>
                            <p class="text-xs text-zinc-400 mt-0.5">Nomor rekening disimpan terenkripsi (AES-256). Rekening utama dipakai saat payroll run.</p>
                        </div>
                        @if($canManageBank)
                            <flux:button variant="primary" size="sm" icon="plus" wire:click="openBankModal">
                                Tambah Rekening
                            </flux:button>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($employee->bankAccounts as $bank)
                            <div wire:key="bank-{{ $bank->id }}" class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">{{ $bank->bank_name }}</span>
                                    @if($bank->is_primary)
                                        <flux:badge size="sm" color="emerald">Utama</flux:badge>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500">
                                    <div>Atas Nama: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bank->account_holder }}</span></div>
                                    <div class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        @if($canManageBank)
                                            {{ $bank->account_number }}
                                        @else
                                            •••• {{ substr((string) $bank->account_number, -4) }}
                                        @endif
                                    </div>
                                </div>
                                @if($canManageBank)
                                    <div class="flex justify-end gap-1 pt-1 border-t border-zinc-200/70 dark:border-zinc-700/70">
                                        <flux:button variant="ghost" size="xs" icon="pencil" wire:click="openBankModal({{ $bank->id }})">Edit</flux:button>
                                        <flux:button variant="danger" size="xs" icon="trash" wire:confirm="Hapus rekening {{ $bank->bank_name }} ini?" wire:click="deleteBank({{ $bank->id }})">Hapus</flux:button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-zinc-400 italic col-span-3">Belum ada nomor rekening bank terdaftar.</p>
                        @endforelse
                    </div>
                </flux:card>
            </div>
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
        @php
            $docTypeLabels = ['ktp' => 'KTP', 'kk' => 'Kartu Keluarga', 'ijazah' => 'Ijazah', 'contract' => 'Kontrak Kerja', 'str' => 'STR', 'sip' => 'SIP', 'certificate' => 'Sertifikat', 'other' => 'Lainnya'];
        @endphp
        <div class="space-y-4">
            @if($docFeedback)
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-2">
                    <flux:icon name="check-circle" class="w-5 h-5 shrink-0" />
                    <span>{{ $docFeedback }}</span>
                </div>
            @endif

            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Arsip Berkas Digital Pegawai</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">PDF/JPG/PNG maks. 10 MB. Unduhan menggunakan tautan sementara (15 menit).</p>
                    </div>
                    @if($canUploadDoc)
                        <flux:button variant="primary" size="sm" icon="arrow-up-tray" wire:click="openDocModal">
                            Unggah Berkas
                        </flux:button>
                    @endif
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($employee->documents as $doc)
                        <div wire:key="doc-{{ $doc->id }}" class="py-3 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:icon name="document-text" class="w-6 h-6 text-zinc-400 shrink-0" />
                                <div class="min-w-0">
                                    <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $docTypeLabels[$doc->document_type] ?? strtoupper($doc->document_type) }}</span>
                                    <p class="text-xs text-zinc-400 truncate">
                                        {{ $doc->original_name }} ({{ number_format(((int) $doc->file_size) / 1024, 1) }} KB) · {{ $doc->created_at?->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @can('download', $doc)
                                    <a href="{{ $doc->getTemporaryDownloadUrl() }}" target="_blank" rel="noopener">
                                        <flux:button size="xs" variant="subtle" icon="arrow-down-tray">Unduh</flux:button>
                                    </a>
                                @endcan
                                @can('delete', $doc)
                                    <flux:button size="xs" variant="danger" icon="trash" wire:confirm="Hapus berkas {{ $doc->original_name }} dari arsip?" wire:click="deleteDoc({{ $doc->id }})">Hapus</flux:button>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-zinc-400 italic">Belum ada dokumen kepatuhan yang diunggah.</p>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Tab 6: Biometrik Wajah (Face AI) --}}
    @if($activeTab === 'biometrics')
        <div class="space-y-6">
            @if($enrollStatusMessage)
                <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
                    <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="text-sm font-medium">{{ $enrollStatusMessage }}</span>
                </div>
            @endif

            @if($enrollErrorMessage)
                <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center gap-3">
                    <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                    <span class="text-sm font-medium">{{ $enrollErrorMessage }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Status Card --}}
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                            <flux:icon name="user" class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Status Biometrik Wajah</h4>
                            <p class="text-xs text-zinc-500">Integrasi Face AI & Geofencing Presensi</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-500">Status Pendaftaran:</span>
                            @if(!empty($employee->face_biometric_vector))
                                <flux:badge size="sm" color="emerald">Terdaftar &amp; Aktif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Belum Terdaftar</flux:badge>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-500">Format Vektor:</span>
                            <span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                @if(!empty($employee->face_biometric_vector))
                                    {{ count($employee->face_biometric_vector) }}-Dimensi (AES-256)
                                @else
                                    -
                                @endif
                            </span>
                        </div>

                        <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 text-xs text-zinc-500 space-y-1">
                            <div class="font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <flux:icon name="shield-check" class="w-4 h-4 text-emerald-600" />
                                Standar Kepatuhan Privasi Data (UU PDP)
                            </div>
                            <p>
                                Vektor biometrik disimpan dalam bentuk enkripsi kriptografi satu arah di basis data PostgreSQL. Foto fisik wajah tidak disimpan di Google Drive maupun storage publik.
                            </p>
                        </div>

                        @if(!empty($employee->face_biometric_vector) && $canManageBiometrics)
                            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex justify-end">
                                <flux:button 
                                    size="sm" 
                                    variant="danger" 
                                    icon="trash"
                                    wire:click="removeBiometrics"
                                    wire:confirm="Apakah Anda yakin ingin menghapus data biometrik wajah pegawai ini?"
                                >
                                    Hapus Data Biometrik
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Enrollment / Update Card --}}
                @if($canManageBiometrics)
                    <flux:card class="space-y-4">
                        <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                            <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                <flux:icon name="camera" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ !empty($employee->face_biometric_vector) ? 'Perbarui Biometrik Wajah' : 'Daftarkan Biometrik Wajah' }}
                                </h4>
                                <p class="text-xs text-zinc-500">Gunakan kamera langsung atau unggah foto resmi</p>
                            </div>
                        </div>

                        <div class="space-y-4" x-data="biometricEnrollment()">
                            {{-- Interactive Webcam Viewport --}}
                            <div class="relative rounded-xl overflow-hidden bg-zinc-950 border border-zinc-800 flex flex-col items-center justify-center min-h-[220px]">
                                <video x-ref="video" class="w-full h-48 object-cover" autoplay playsinline x-show="isStreaming"></video>
                                <canvas x-ref="canvas" class="hidden"></canvas>

                                <template x-if="!isStreaming && !$wire.webcamCapture && !$wire.photoUpload">
                                    <div class="p-6 text-center space-y-2">
                                        <flux:icon name="video-camera" class="w-10 h-10 text-zinc-600 mx-auto" />
                                        <p class="text-xs text-zinc-400">Kamera saat ini belum aktif</p>
                                    </div>
                                </template>

                                <template x-if="$wire.webcamCapture">
                                    <div class="p-2 w-full text-center">
                                        <img :src="$wire.webcamCapture" class="h-44 mx-auto rounded-lg object-cover border border-emerald-500" alt="Captured Face" />
                                        <span class="text-xs text-emerald-400 mt-1 block">Foto Wajah Berhasil Diambil</span>
                                    </div>
                                </template>
                            </div>

                            {{-- Camera Controls --}}
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button"
                                    x-show="!isStreaming"
                                    @click="startCamera()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition"
                                >
                                    <flux:icon name="video-camera" class="w-4 h-4" />
                                    Buka Kamera
                                </button>

                                <button 
                                    type="button"
                                    x-show="isStreaming"
                                    @click="captureFrame()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition"
                                >
                                    <flux:icon name="camera" class="w-4 h-4" />
                                    Ambil Foto
                                </button>

                                <button 
                                    type="button"
                                    x-show="isStreaming"
                                    @click="stopCamera()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                                >
                                    Tutup Kamera
                                </button>
                            </div>

                            {{-- Or File Upload --}}
                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                                <flux:field>
                                    <flux:label class="text-xs">Atau Unggah Berkas Foto Wajah (JPG/PNG)</flux:label>
                                    <input 
                                        type="file" 
                                        wire:model="photoUpload" 
                                        accept="image/jpeg,image/png,image/webp" 
                                        class="block w-full text-xs text-zinc-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300"
                                    />
                                    <flux:error name="photoUpload" />
                                </flux:field>

                                @if($photoUpload)
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <flux:icon name="check-circle" class="w-4 h-4" />
                                        Berkas foto terpilih siap diekstraksi
                                    </div>
                                @endif
                            </div>

                            {{-- Action Submit Button --}}
                            <div class="pt-2">
                                <flux:button 
                                    variant="primary" 
                                    class="w-full"
                                    wire:click="enrollBiometrics"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="enrollBiometrics">
                                        Proses &amp; Simpan Biometrik Wajah
                                    </span>
                                    <span wire:loading wire:target="enrollBiometrics">
                                        Mengekstrak Vektor Face AI...
                                    </span>
                                </flux:button>
                            </div>
                        </div>

                        <script>
                            function biometricEnrollment() {
                                return {
                                    isStreaming: false,
                                    mediaStream: null,
                                    startCamera() {
                                        const video = this.$refs.video;
                                        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                                            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                                                .then(stream => {
                                                    this.mediaStream = stream;
                                                    video.srcObject = stream;
                                                    this.isStreaming = true;
                                                })
                                                .catch(err => {
                                                    alert('Gagal mengakses kamera: ' + err.message);
                                                });
                                        } else {
                                            alert('Peramban tidak mendukung akses kamera.');
                                        }
                                    },
                                    stopCamera() {
                                        if (this.mediaStream) {
                                            this.mediaStream.getTracks().forEach(track => track.stop());
                                            this.mediaStream = null;
                                        }
                                        this.isStreaming = false;
                                    },
                                    captureFrame() {
                                        const video = this.$refs.video;
                                        const canvas = this.$refs.canvas;
                                        canvas.width = video.videoWidth || 640;
                                        canvas.height = video.videoHeight || 480;
                                        const ctx = canvas.getContext('2d');
                                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                                        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                                        @this.set('webcamCapture', dataUrl);
                                        this.stopCamera();
                                    }
                                };
                            }
                        </script>
                    </flux:card>
                @else
                    <flux:card class="flex flex-col items-center justify-center text-center p-8 space-y-2">
                        <flux:icon name="lock-closed" class="w-8 h-8 text-zinc-400" />
                        <h4 class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">Akses Terbatas: Pendaftaran Biometrik</h4>
                        <p class="text-xs text-zinc-500 max-w-sm">
                            Pendaftaran dan pembaruan biometrik wajah pegawai hanya dapat dilakukan oleh Manajemen SDM / HR Admin berwenang.
                        </p>
                    </flux:card>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab 7: Sanksi & Surat Peringatan (SP) --}}
    @if($activeTab === 'disciplinary')
        <div class="space-y-6">
            {{-- Feedback & Error Banners --}}
            @if($spFeedback)
                <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span class="text-sm font-medium">{{ $spFeedback }}</span>
                    </div>
                    <flux:button size="sm" variant="subtle" wire:click="$set('spFeedback', null)">Tutup</flux:button>
                </div>
            @endif

            @if($spError)
                <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                        <span class="text-sm font-medium">{{ $spError }}</span>
                    </div>
                    <flux:button size="sm" variant="subtle" wire:click="$set('spError', null)">Tutup</flux:button>
                </div>
            @endif

            {{-- Active Warning Alert Banner --}}
            @if($hasActiveSp)
                <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <div>
                            <h4 class="font-semibold text-sm">Pegawai Sedang Dalam Masa Sanksi Disiplin Aktif</h4>
                            <p class="text-xs text-amber-800/80 dark:text-amber-300/80 mt-0.5">
                                Karyawan ini memiliki surat peringatan yang masih dalam masa berlaku. Harap diperhatikan dalam evaluasi kinerja, pengajuan kenaikan jenjang, dan perpanjangan kontrak kerja.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Card Table Sanksi & SP --}}
            <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            Riwayat Surat Peringatan &amp; Sanksi Disiplin
                        </h3>
                        <p class="text-xs text-zinc-500 mt-0.5">
                            Dokumentasi resmi surat teguran, SP I, SP II, SP III, dan riwayat sanksi kerja lainnya.
                        </p>
                    </div>

                    @if($canManageDisciplinary)
                        <flux:button variant="primary" icon="plus" wire:click="openCreateWarningLetter">
                            Terbitkan Surat Peringatan (SP)
                        </flux:button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700/80 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 font-semibold">Tingkat / Jenis Sanksi</th>
                                <th class="px-4 py-3.5 font-semibold">Nomor Surat</th>
                                <th class="px-4 py-3.5 font-semibold">Tanggal Terbit</th>
                                <th class="px-4 py-3.5 font-semibold">Masa Berlaku</th>
                                <th class="px-4 py-3.5 font-semibold">Uraian Pelanggaran</th>
                                <th class="px-4 py-3.5 font-semibold">Penandatangan</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Berkas</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Status</th>
                                @if($canManageDisciplinary)
                                    <th class="px-4 py-3.5 font-semibold text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-800 dark:text-zinc-200">
                            @forelse($warningLetters as $sp)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-4 py-3">
                                        @php $lvl = $sp->warningLetterType?->level ?? 1; @endphp
                                        @if($lvl === 0)
                                            <flux:badge size="sm" color="zinc">{{ $sp->warningLetterType?->name ?? 'Teguran' }}</flux:badge>
                                        @elseif($lvl === 1)
                                            <flux:badge size="sm" color="amber">{{ $sp->warningLetterType?->name ?? 'SP I' }}</flux:badge>
                                        @elseif($lvl === 2)
                                            <flux:badge size="sm" color="orange">{{ $sp->warningLetterType?->name ?? 'SP II' }}</flux:badge>
                                        @elseif($lvl === 3)
                                            <flux:badge size="sm" color="rose">{{ $sp->warningLetterType?->name ?? 'SP III' }}</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="red">{{ $sp->warningLetterType?->name ?? 'Sanksi Berat' }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $sp->letter_number }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $sp->issued_date ? $sp->issued_date->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $sp->valid_from ? $sp->valid_from->format('d M Y') : '-' }} s/d {{ $sp->valid_until ? $sp->valid_until->format('d M Y') : '-' }}
                                        </div>
                                        @if($sp->effective_status === 'active')
                                            <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                                                (Sisa: {{ (int) now()->diffInDays($sp->valid_until, false) }} hari)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300 max-w-xs">
                                        <div class="line-clamp-2" title="{{ $sp->violation_reason }}">
                                            {{ $sp->violation_reason }}
                                        </div>
                                        @if($sp->notes)
                                            <div class="mt-1 text-[11px] text-zinc-400 italic">
                                                Catatan: {{ $sp->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $sp->authorized_by }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($sp->document_path)
                                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadWarningLetter({{ $sp->id }})">
                                                Unduh Berkas
                                            </flux:button>
                                        @else
                                            <span class="text-xs text-zinc-400 italic">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($sp->status === 'revoked')
                                            <flux:badge size="sm" color="purple" title="Dicabut pada: {{ $sp->revoked_at ? $sp->revoked_at->format('d M Y H:i') : '' }} | Alasan: {{ $sp->revocation_reason }}">
                                                Dicabut
                                            </flux:badge>
                                        @elseif($sp->effective_status === 'expired')
                                            <flux:badge size="sm" color="zinc">Selesai (Kedaluwarsa)</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="rose">Berlaku Aktif</flux:badge>
                                        @endif
                                    </td>
                                    @if($canManageDisciplinary)
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if($sp->status === 'active' && $sp->effective_status === 'active')
                                                    <flux:button size="xs" variant="subtle" icon="x-circle" wire:click="openRevokeModal({{ $sp->id }})" title="Cabut sanksi SP lebih awal">
                                                        Cabut SP
                                                    </flux:button>
                                                @endif

                                                <flux:button size="xs" variant="subtle" icon="pencil-square" wire:click="editWarningLetter({{ $sp->id }})" title="Edit data SP">
                                                    Edit
                                                </flux:button>

                                                <flux:button size="xs" variant="danger" icon="trash" wire:confirm="Apakah Anda yakin ingin menghapus arsip surat peringatan ini?" wire:click="deleteWarningLetter({{ $sp->id }})" title="Hapus arsip">
                                                </flux:button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManageDisciplinary ? 9 : 8 }}" class="px-4 py-8 text-center text-zinc-500">
                                        <div class="flex flex-col items-center justify-center space-y-1">
                                            <flux:icon name="check-badge" class="w-8 h-8 text-emerald-500/70" />
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Tidak ada catatan sanksi / SP.</span>
                                            <span class="text-xs text-zinc-400">Pegawai memiliki catatan kedisiplinan yang bersih.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- MODAL TERBITKAN / EDIT SURAT PERINGATAN (SP) --}}
    <flux:modal wire:model="showWarningLetterModal" class="md:w-2xl space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $editingWarningLetterId ? 'Edit Surat Peringatan (SP)' : 'Terbitkan Surat Peringatan (SP) Baru' }}
            </flux:heading>
            <flux:subheading class="text-xs text-zinc-500">
                Penerbitan sanksi kedisiplinan resmi untuk pegawai {{ $employee->full_name }} (NIP: {{ $employee->nip }}).
            </flux:subheading>
        </div>

        <form wire:submit="saveWarningLetter" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model.live="spTypeId" label="Tingkat / Jenis Surat Peringatan" required>
                    <option value="">-- Pilih Jenis Sanksi SP --</option>
                    @foreach($availableWarningLetterTypes as $type)
                        <option value="{{ $type->id }}">
                            {{ $type->name }} (Masa Berlaku: {{ $type->validity_months }} Bulan)
                        </option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="spLetterNumber" label="Nomor Surat Resmi" placeholder="Misal: 015/SP-I/SDM-RSJ/X/2026" required />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:input wire:model="spIssuedDate" type="date" label="Tanggal Penetapan Surat" required />
                <flux:input wire:model.live="spValidFrom" type="date" label="Mulai Berlaku" required />
                <flux:input wire:model="spValidUntil" type="date" label="Berlaku Sampai Dengan" required />
            </div>

            <flux:textarea wire:model="spViolationReason" label="Uraian Pelanggaran Tata Tertib" placeholder="Jelaskan tindakan pelanggaran, kronologi kejadian, atau klausul peraturan rumah sakit yang dilanggar..." rows="3" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="spAuthorizedBy" label="Pejabat Penandatangan / Pengesah" placeholder="Misal: Direktur Utama / Kepala Bagian SDM" required />
                <flux:input wire:model="spNotes" label="Catatan Pembinaan / Komitmen (Opsional)" placeholder="Komitmen perbaikan sikap atau evaluasi mingguan" />
            </div>

            <flux:field>
                <flux:label>Upload Berkas Scan Surat SP (PDF / JPG / PNG, Max 5MB)</flux:label>
                <input 
                    type="file" 
                    wire:model="spDocumentFile" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-xs text-zinc-500 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300"
                />
                <flux:error name="spDocumentFile" />
            </flux:field>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan &amp; Terbitkan SP</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL PENCABUTAN SURAT PERINGATAN (REVOKE SP) --}}
    <flux:modal wire:model="showRevokeModal" class="md:w-lg space-y-4">
        <div>
            <flux:heading size="lg">Cabut Surat Peringatan (Rehabilitasi)</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">
                Pencabutan sanksi disiplin lebih awal sebelum masa berlaku berakhir atas dasar pembinaan atau perbaikan kinerja.
            </flux:subheading>
        </div>

        <form wire:submit="revokeWarningLetter" class="space-y-4">
            <flux:textarea 
                wire:model="revocationReason" 
                label="Alasan &amp; Pertimbangan Pencabutan Sanksi" 
                placeholder="Tuliskan dasar pertimbangan pencabutan SP (misal: perbaikan absensi dan kepatuhan SOP selama 3 bulan pemantauan)..." 
                rows="4" 
                required 
            />

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">Konfirmasi Cabut SP</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Catat Penugasan / SK Baru --}}
    <flux:modal wire:model="showEmploymentModal" class="md:w-2xl space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $editingEmploymentId ? 'Koreksi / Lengkapi Riwayat SK Penugasan' : 'Catat Penugasan / SK Baru' }}
            </flux:heading>
            <flux:subheading>
                {{ $editingEmploymentId ? 'Perbarui nomor SK, masa penugasan, atau lengkapi berkas scan fisik SK resmi.' : 'Mencatat penugasan, kenaikan jabatan, atau rotasi kerja. Mendukung penetapan tanggal berlaku surut (retroactive).' }}
            </flux:subheading>
        </div>

        <form wire:submit.prevent="saveEmployment" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select label="Unit Penempatan" wire:model="employmentUnitId" required>
                    <option value="">Pilih Unit...</option>
                    @foreach($availableUnits as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</option>
                    @endforeach
                </flux:select>

                <flux:select label="Jabatan" wire:model.live="employmentPositionId" required>
                    <option value="">Pilih Jabatan...</option>
                    @foreach($availablePositions as $pos)
                        <option value="{{ $pos->id }}">{{ $pos->title }}</option>
                    @endforeach
                </flux:select>

                <flux:select label="Jenis Ikatan Kerja" wire:model="employmentContractType" required>
                    <option value="permanent">Pegawai Tetap (PKWTT)</option>
                    <option value="contract">Kontrak (PKWT)</option>
                    <option value="probation">Masa Percobaan (Probation)</option>
                    <option value="internship">Magang / Internship</option>
                </flux:select>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Nomor SK / SPK / Kontrak</label>
                        @if($employmentPositionId)
                            <button type="button" wire:click="pullSkFromPosition" class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium inline-flex items-center gap-1 cursor-pointer" title="Tarik nomor SK &amp; berkas dari standar master jabatan">
                                <flux:icon name="arrow-path" class="size-3" />
                                <span>Tarik SK Jabatan</span>
                            </button>
                        @endif
                    </div>
                    <flux:input 
                        wire:model="employmentContractNumber" 
                        placeholder="misal: SK/RSJ/2026/045" 
                        required 
                    />
                </div>

                <flux:input 
                    label="Tanggal Mulai Tugas" 
                    type="date" 
                    wire:model="employmentStartDate" 
                    required 
                />

                <flux:input 
                    label="Tanggal Berakhir (Opsional)" 
                    type="date" 
                    wire:model="employmentEndDate" 
                    placeholder="Kosongkan jika tetap" 
                />
            </div>

            {{-- Upload Berkas Fisik SK --}}
            <div class="space-y-2 p-3.5 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Lampiran Berkas Fisik SK (Scan PDF, JPG, PNG)</span>
                    <span class="text-[11px] text-zinc-400">Maks. 10 MB (Opsional)</span>
                </div>
                @if($employmentExistingDocumentName)
                    <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300">
                        <div class="flex items-center gap-1.5 truncate">
                            <flux:icon name="document-check" class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <span class="truncate">Berkas terlampir: <strong>{{ $employmentExistingDocumentName }}</strong></span>
                        </div>
                        <span class="text-[10px] text-zinc-400 shrink-0">Pilih file baru bila ingin mengganti</span>
                    </div>
                @endif
                <flux:input type="file" wire:model="employmentDocumentFile" accept=".pdf,.jpg,.jpeg,.png" />
                <div wire:loading wire:target="employmentDocumentFile" class="text-xs text-sky-600 dark:text-sky-400">Mengunggah berkas SK…</div>
            </div>

            @if($canUpdateSalary)
                <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <flux:checkbox wire:model.live="syncSalaryWithSk" label="Sesuaikan Gaji & Tunjangan Berdasarkan SK Jabatan" />
                        @php
                            $selectedPos = $availablePositions->firstWhere('id', (int) $employmentPositionId);
                        @endphp
                        @if($selectedPos)
                            <span class="text-xs text-sky-600 dark:text-sky-400 font-medium">
                                Standar Master: {{ $selectedPos->title }} ({{ $selectedPos->grade_level ?? '-' }})
                            </span>
                        @endif
                    </div>

                    @if($syncSalaryWithSk)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-zinc-200/60 dark:border-zinc-700/60">
                            <flux:input 
                                label="Gaji Pokok SK (Rp)" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                wire:model="employmentBasicSalary" 
                                placeholder="Terisi otomatis dari master jabatan" 
                            />
                            <flux:input 
                                label="Tunjangan Jabatan SK (Rp)" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                wire:model="employmentPositionalAllowance" 
                                placeholder="Terisi otomatis dari master jabatan" 
                            />
                        </div>
                        <p class="text-[11px] text-zinc-500">
                            * Standar remunerasi otomatis ditarik dari Master Data Jabatan. Nilai di atas dapat disesuaikan bila SK memiliki klausul khusus.
                        </p>
                    @endif
                </div>
            @endif

            <flux:textarea 
                label="Catatan / Keterangan Penugasan" 
                wire:model="employmentNotes" 
                rows="2" 
                placeholder="misal: Rotasi dinas triwulan II / Promosi Kepala Ruangan" 
            />

            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
                <span class="font-medium text-zinc-700 dark:text-zinc-300">Catatan Sistem:</span>
                @if($editingEmploymentId)
                    Menyimpan perubahan akan mengoreksi data arsip penugasan ini. Jika catatan ini berstatus penugasan aktif, profil jabatan dan unit pegawai akan turut disinkronkan.
                @else
                    Mendukung penetapan tanggal berlaku surut (retroactive). Jika tanggal mulai berada di masa lampau, sistem akan secara otomatis menyesuaikan batas akhir penugasan dan riwayat gaji sebelumnya agar linimasa tetap tertib dan tidak tumpang tindih.
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showEmploymentModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="employmentDocumentFile">
                    {{ $editingEmploymentId ? 'Simpan Perubahan SK' : 'Simpan & Terapkan Penugasan' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Edit Biodata --}}
    <flux:modal wire:model="showBioModal" class="md:w-2xl space-y-4">
        <div>
            <flux:heading size="lg">Edit Biodata Pegawai</flux:heading>
            <flux:subheading>Perubahan dicatat pada audit trail.</flux:subheading>
        </div>

        <form wire:submit.prevent="saveBio" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input label="Nama Lengkap" wire:model="bioFullName" required />
                <flux:input label="Nomor Telepon" wire:model="bioPhone" required />
                <flux:select label="Jenis Kelamin" wire:model="bioGender">
                    <option value="male">Laki-laki</option>
                    <option value="female">Perempuan</option>
                </flux:select>
                <flux:input label="Tempat Lahir" wire:model="bioBirthPlace" required />
                <flux:input label="Tanggal Lahir" type="date" wire:model="bioBirthDate" required />
                <flux:select label="Status Pernikahan" wire:model="bioMaritalStatus">
                    <option value="single">Belum Menikah</option>
                    <option value="married">Menikah</option>
                    <option value="divorced">Cerai Hidup</option>
                    <option value="widowed">Cerai Mati</option>
                </flux:select>
                <flux:select label="Agama / Hari Raya" wire:model="bioReligion">
                    <option value="islam">Islam (Idul Fitri)</option>
                    <option value="kristen">Kristen Protestan (Natal)</option>
                    <option value="katolik">Katolik (Natal)</option>
                    <option value="hindu">Hindu (Nyepi)</option>
                    <option value="buddha">Buddha (Waisak)</option>
                    <option value="khonghucu">Khonghucu (Imlek)</option>
                </flux:select>
                <flux:input label="Jumlah Tanggungan (PTKP, maks. 3)" type="number" min="0" max="3" wire:model="bioTaxDependents" />
                @if($canViewSensitive)
                    <flux:input label="NPWP" wire:model="bioNpwp" placeholder="Opsional" />
                    <flux:input label="NIK KTP (16 digit)" wire:model="bioKtp" maxlength="16" inputmode="numeric" required />
                @endif
            </div>
            @if($canViewSensitive)
                <flux:textarea label="Alamat Domisili" wire:model="bioAddress" rows="2" required />
            @else
                <p class="text-xs text-zinc-400">NIK KTP, NPWP, dan alamat hanya dapat diubah oleh pengguna dengan akses data sensitif.</p>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showBioModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Biodata</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Rekening Bank --}}
    <flux:modal wire:model="showBankModal" class="md:w-md space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingBankId ? 'Edit Rekening Payroll' : 'Tambah Rekening Payroll' }}</flux:heading>
            <flux:subheading>Nomor rekening disimpan terenkripsi.</flux:subheading>
        </div>

        <form wire:submit.prevent="saveBank" class="space-y-4">
            <flux:input label="Nama Bank" wire:model="bankName" placeholder="misal: BCA, Mandiri, BNI, BRI" list="bank-name-options" required />
            <datalist id="bank-name-options">
                <option value="BCA"></option>
                <option value="Mandiri"></option>
                <option value="BNI"></option>
                <option value="BRI"></option>
                <option value="BSI"></option>
                <option value="CIMB Niaga"></option>
            </datalist>
            <flux:input label="Nomor Rekening" wire:model="accountNumber" inputmode="numeric" placeholder="Hanya angka" required />
            <flux:input label="Nama Pemilik Rekening" wire:model="accountHolder" required />
            <flux:checkbox label="Jadikan rekening utama" wire:model="isPrimaryBank" description="Rekening utama digunakan untuk pencairan gaji." />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showBankModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Rekening</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Unggah Dokumen --}}
    <flux:modal wire:model="showDocModal" class="md:w-md space-y-4">
        <div>
            <flux:heading size="lg">Unggah Berkas Pegawai</flux:heading>
            <flux:subheading>Format PDF, JPG, atau PNG. Maksimal 10 MB.</flux:subheading>
        </div>

        <form wire:submit.prevent="uploadDoc" class="space-y-4">
            <flux:select label="Jenis Dokumen" wire:model="docUploadType">
                <option value="ktp">KTP</option>
                <option value="kk">Kartu Keluarga</option>
                <option value="ijazah">Ijazah</option>
                <option value="contract">Kontrak Kerja</option>
                <option value="str">STR</option>
                <option value="sip">SIP</option>
                <option value="certificate">Sertifikat</option>
                <option value="other">Lainnya</option>
            </flux:select>

            <flux:input type="file" label="Berkas" wire:model="docUploadedFile" accept=".pdf,.jpg,.jpeg,.png" />
            <div wire:loading wire:target="docUploadedFile" class="text-xs text-zinc-500">Mengunggah berkas sementara…</div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showDocModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="docUploadedFile,uploadDoc">Unggah</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal 1: Revisi Gaji Pokok --}}
    <flux:modal wire:model="showSalaryModal" class="md:w-md space-y-4">
        <div>
            <flux:heading size="lg">Revisi Gaji Pokok Pegawai</flux:heading>
            <flux:subheading>Tetapkan perubahan nominal gaji pokok dengan tanggal efektif baru.</flux:subheading>
        </div>

        <form wire:submit.prevent="saveSalary" class="space-y-4">
            @if($employee->position?->default_basic_salary)
                <div class="flex items-center justify-between p-2.5 rounded-lg bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 text-xs">
                    <span class="text-sky-800 dark:text-sky-300">
                        Standar Jabatan ({{ $employee->position->title }}): <strong>Rp {{ number_format((float)$employee->position->default_basic_salary, 0, ',', '.') }}</strong>
                    </span>
                    <button type="button" wire:click="applyPositionDefaultSalary" class="text-sky-600 hover:text-sky-800 dark:text-sky-400 font-semibold underline cursor-pointer">
                        Gunakan Standar
                    </button>
                </div>
            @endif

            <flux:input 
                label="Nominal Gaji Pokok Baru (Rp)" 
                type="number" 
                step="0.01" 
                min="0" 
                wire:model="basicSalary" 
                placeholder="misal: 7500000" 
                required 
            />

            <flux:input 
                label="Berlaku Efektif Mulai Tanggal" 
                type="date" 
                wire:model="salaryEffectiveFrom" 
                required 
            />

            <flux:textarea 
                label="Alasan Penetapan / Nomor SK" 
                wire:model="revisionReason" 
                placeholder="misal: Kenaikan berkala tahunan / SK Direksi No. 045/SK/2026" 
                rows="2" 
                required 
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showSalaryModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Revisi</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal 2: Kelola Komponen Gaji (Tunjangan / Potongan) --}}
    <flux:modal wire:model="showComponentModal" class="md:w-lg space-y-4">
        <div>
            <flux:heading size="lg">
                {{ $editingComponentAssignmentId ? 'Edit Komponen Gaji Pegawai' : 'Tetapkan Komponen Gaji Pegawai' }}
            </flux:heading>
            <flux:subheading>
                Pilih komponen dari master data (tunjangan atau potongan) untuk diterapkan ke pegawai ini.
            </flux:subheading>
        </div>

        <form wire:submit.prevent="saveComponent" class="space-y-4">
            <flux:select label="Pilih Komponen Gaji" wire:model.live="selectedComponentId" placeholder="-- Pilih Komponen --" required>
                <option value="">-- Pilih Komponen --</option>
                <optgroup label="Tunjangan (Allowances)">
                    @foreach($availableComponents->where('type', 'allowance') as $ac)
                        <option value="{{ $ac->id }}">[{{ $ac->code }}] {{ $ac->name }} (Default: Rp {{ number_format((float)$ac->default_amount, 0, ',', '.') }})</option>
                    @endforeach
                </optgroup>
                <optgroup label="Potongan (Deductions)">
                    @foreach($availableComponents->where('type', 'deduction') as $ac)
                        <option value="{{ $ac->id }}">[{{ $ac->code }}] {{ $ac->name }} (Default: Rp {{ number_format((float)$ac->default_amount, 0, ',', '.') }})</option>
                    @endforeach
                </optgroup>
            </flux:select>

            <flux:input 
                label="Nominal Komponen Bulanan (Rp)" 
                type="number" 
                step="0.01" 
                min="0" 
                wire:model="componentAmount" 
                placeholder="misal: 500000" 
                required 
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input 
                    label="Berlaku Mulai Tanggal" 
                    type="date" 
                    wire:model="componentEffectiveFrom" 
                    required 
                />
                <flux:input 
                    label="Berlaku Sampai Tanggal (Opsional)" 
                    type="date" 
                    wire:model="componentEffectiveTo" 
                    placeholder="Kosongkan jika seterusnya" 
                />
            </div>

            <flux:input 
                label="Keterangan / Catatan Tambahan" 
                wire:model="componentNotes" 
                placeholder="misal: Sesuai penugasan unit IGD" 
            />

            <flux:checkbox 
                label="Status Komponen Aktif" 
                wire:model="componentIsActive" 
                description="Hanya komponen berstatus aktif yang akan dihitung pada saat payroll run." 
            />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showComponentModal', false)">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Komponen</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

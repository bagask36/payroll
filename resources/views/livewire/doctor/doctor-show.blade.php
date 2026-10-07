<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('doctors.index') }}">
                <flux:button size="sm" variant="subtle" icon="arrow-left">
                    Kembali ke Direktori
                </flux:button>
            </a>
            <div class="flex items-center gap-3">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 font-mono text-sm font-bold text-white shadow-xs">
                    {{ strtoupper(substr(str_replace(['dr. ', 'drg. ', 'Sp.'], '', $doctor->name), 0, 2)) }}
                </div>
                <div>
                    <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ $doctor->name }}
                    </flux:heading>
                    <flux:subheading class="text-zinc-500 font-medium text-xs">
                        {{ $doctor->specialization }} &bull; {{ $doctor->email }}
                    </flux:subheading>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($doctor->isStrExpired() || $doctor->isSipExpired())
                <flux:badge size="lg" color="rose" icon="exclamation-triangle">
                    Lisensi Kedaluwarsa
                </flux:badge>
            @elseif($doctor->isExpiringSoon(60))
                <flux:badge size="lg" color="amber" icon="clock">
                    Lisensi Mendekati Jatuh Tempo
                </flux:badge>
            @endif

            @if($doctor->status === 'active')
                <flux:badge size="lg" color="emerald">Dokter Aktif</flux:badge>
            @else
                <flux:badge size="lg" color="zinc">Non-aktif</flux:badge>
            @endif
        </div>
    </div>

    {{-- Profile Summary Header Card --}}
    <flux:card class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6">
        <div>
            <span class="text-xs font-medium text-zinc-500">Spesialisasi</span>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $doctor->specialization }}
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Kategori Medis</span>
            <div class="mt-1">
                @if($doctor->employee_id)
                    <flux:badge size="sm" color="emerald">Dokter Organik RS</flux:badge>
                @else
                    <flux:badge size="sm" color="blue">Dokter Mitra / Tamu</flux:badge>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Lisensi STR (Kemenkes)</span>
            <div class="mt-1 flex flex-col gap-0.5">
                <span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                    {{ $doctor->str_number ?? '-' }}
                </span>
                @if($doctor->str_valid_until)
                    <span class="text-[11px] {{ $doctor->isStrExpired() ? 'text-rose-600 font-semibold' : 'text-zinc-400' }}">
                        s/d {{ $doctor->str_valid_until->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Izin Praktik SIP (Dinkes)</span>
            <div class="mt-1 flex flex-col gap-0.5">
                <span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                    {{ $doctor->sip_number ?? '-' }}
                </span>
                @if($doctor->sip_valid_until)
                    <span class="text-[11px] {{ $doctor->isSipExpired() ? 'text-rose-600 font-semibold' : 'text-zinc-400' }}">
                        s/d {{ $doctor->sip_valid_until->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Status Kepegawaian</span>
            <div class="mt-1">
                @if($doctor->employee)
                    <a href="{{ route('employees.show', $doctor->employee_id) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 underline">
                        <span>NIP: {{ $doctor->employee->nip }}</span>
                        <flux:icon name="arrow-top-right-on-square" class="size-3.5" />
                    </a>
                    <div class="text-[11px] text-zinc-400 mt-0.5">{{ $doctor->employee->unit?->name ?? 'Unit RS' }}</div>
                @else
                    <span class="text-xs text-zinc-400">Tidak Terhubung Pegawai</span>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Tabs Navigation --}}
    <div class="border-b border-zinc-200 dark:border-zinc-800 pb-2">
        <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar py-0.5">
            <flux:button 
                size="sm" 
                icon="identification"
                wire:click="setTab('bio')"
                class="{{ $activeTab === 'bio' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-transparent text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Profil & Kredensial
            </flux:button>

            <flux:button 
                size="sm" 
                icon="calendar"
                wire:click="setTab('schedules')"
                class="{{ $activeTab === 'schedules' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-transparent text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Jadwal & Shift Praktik ({{ $doctor->scheduleAssignments->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="clock"
                wire:click="setTab('attendance')"
                class="{{ $activeTab === 'attendance' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-transparent text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Riwayat Presensi ({{ $doctor->attendances->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="document-text"
                wire:click="setTab('leave')"
                class="{{ $activeTab === 'leave' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-transparent text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Cuti & Izin Dokter ({{ $doctor->leaveRequests->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="folder"
                wire:click="setTab('documents')"
                class="{{ $activeTab === 'documents' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-transparent text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Berkas & Kredensial ({{ $doctor->documents->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="user"
                wire:click="setTab('account')"
                class="{{ $activeTab === 'account' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'bg-transparent text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Akun Portal
            </flux:button>
        </div>
    </div>

    {{-- Feedback Messages --}}
    @if($bioFeedback || $credFeedback || $linkFeedback || $docFeedback || $accountFeedback)
        <x-flash-message :message="$bioFeedback ?? $credFeedback ?? $linkFeedback ?? $docFeedback ?? $accountFeedback" />
    @endif

    @if($bioError || $credError || $linkError || $docError || $accountError)
        <x-flash-message :message="$bioError ?? $credError ?? $linkError ?? $docError ?? $accountError" type="danger" />
    @endif

    {{-- TAB 1: BIODATA & KREDENSIAL --}}
    @if($activeTab === 'bio')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Biodata & Kontak --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <flux:heading size="md">Informasi Profesional & Kontak</flux:heading>
                        <flux:subheading class="text-xs text-zinc-500">Identitas medis dan data komunikasi.</flux:subheading>
                    </div>
                    @if($canUpdateBio)
                        <flux:button size="sm" variant="subtle" icon="pencil" wire:click="openBioModal">
                            Ubah
                        </flux:button>
                    @endif
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-1.5 border-b border-zinc-50 dark:border-zinc-800/50">
                        <span class="text-zinc-500">Nama Lengkap & Gelar</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $doctor->name }}</span>
                    </div>

                    <div class="flex justify-between py-1.5 border-b border-zinc-50 dark:border-zinc-800/50">
                        <span class="text-zinc-500">Spesialisasi Klinis</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $doctor->specialization }}</span>
                    </div>

                    <div class="flex justify-between py-1.5 border-b border-zinc-50 dark:border-zinc-800/50">
                        <span class="text-zinc-500">Alamat Email</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $doctor->email }}</span>
                    </div>

                    <div class="flex justify-between py-1.5 border-b border-zinc-50 dark:border-zinc-800/50">
                        <span class="text-zinc-500">Nomor Telepon / WhatsApp</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $doctor->phone_number ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between py-1.5 border-b border-zinc-50 dark:border-zinc-800/50">
                        <span class="text-zinc-500">Status Praktik</span>
                        <span>
                            @if($doctor->status === 'active')
                                <flux:badge size="sm" color="emerald">Aktif Praktik</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Nonaktif</flux:badge>
                            @endif
                        </span>
                    </div>

                    <div class="flex justify-between py-1.5">
                        <span class="text-zinc-500">Kode UUID Dokter</span>
                        <span class="font-mono text-xs text-zinc-400">{{ $doctor->uuid ?? '-' }}</span>
                    </div>
                </div>
            </flux:card>

            {{-- Kredensial Medis (STR & SIP) --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <flux:heading size="md">Kredensial Lisensi Medis</flux:heading>
                        <flux:subheading class="text-xs text-zinc-500">Status registrasi Kemenkes & Izin Praktik Dinkes.</flux:subheading>
                    </div>
                    @if($canManageCredentials)
                        <flux:button size="sm" variant="subtle" icon="pencil" wire:click="openCredentialModal">
                            Perbarui Kredensial
                        </flux:button>
                    @endif
                </div>

                <div class="space-y-4">
                    {{-- STR Box --}}
                    <div class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Surat Tanda Registrasi (STR)</span>
                            @if($doctor->isStrExpired())
                                <flux:badge size="xs" color="rose">Kedaluwarsa</flux:badge>
                            @else
                                <flux:badge size="xs" color="emerald">Berlaku</flux:badge>
                            @endif
                        </div>
                        <div class="font-mono text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $doctor->str_number ?? '-' }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            Masa Berlaku s/d: 
                            <strong class="{{ $doctor->isStrExpired() ? 'text-rose-600' : 'text-zinc-800 dark:text-zinc-200' }}">
                                {{ $doctor->str_valid_until ? $doctor->str_valid_until->format('d F Y') : '-' }}
                            </strong>
                        </div>
                    </div>

                    {{-- SIP Box --}}
                    <div class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Surat Izin Praktik (SIP)</span>
                            @if($doctor->isSipExpired())
                                <flux:badge size="xs" color="rose">Kedaluwarsa</flux:badge>
                            @else
                                <flux:badge size="xs" color="emerald">Berlaku</flux:badge>
                            @endif
                        </div>
                        <div class="font-mono text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $doctor->sip_number ?? '-' }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            Masa Berlaku s/d: 
                            <strong class="{{ $doctor->isSipExpired() ? 'text-rose-600' : 'text-zinc-800 dark:text-zinc-200' }}">
                                {{ $doctor->sip_valid_until ? $doctor->sip_valid_until->format('d F Y') : '-' }}
                            </strong>
                        </div>
                    </div>

                    {{-- Link Employee Info --}}
                    <div class="p-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-medium text-zinc-500">Integrasi Pegawai Organik</span>
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                @if($doctor->employee)
                                    {{ $doctor->employee->full_name }} ({{ $doctor->employee->nip }})
                                @else
                                    Dokter Kemitraan (Bukan Pegawai Organik)
                                @endif
                            </div>
                        </div>
                        @if($canLinkEmployee)
                            <flux:button size="sm" variant="subtle" wire:click="openLinkModal">
                                Kelola Tautan
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- TAB 2: JADWAL & SHIFT --}}
    @if($activeTab === 'schedules')
        <flux:card class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div>
                    <flux:heading size="md">Jadwal Praktik & Shift Medis</flux:heading>
                    <flux:subheading class="text-xs text-zinc-500">Daftar penugasan roster jadwal praktik klinik.</flux:subheading>
                </div>
                <a href="{{ route('schedules.index') }}">
                    <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square">
                        Buka Roster Jadwal RS
                    </flux:button>
                </a>
            </div>

            @if($doctor->scheduleAssignments->isEmpty())
                <div class="p-8 text-center text-zinc-400">
                    <flux:icon name="calendar" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                    <p class="text-sm">Belum ada penugasan jadwal praktik untuk dokter ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Tanggal Praktik</th>
                                <th class="p-3">Shift</th>
                                <th class="p-3">Jam Kerja</th>
                                <th class="p-3">Unit / Poli</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($doctor->scheduleAssignments as $assignment)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                    <td class="p-3 font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $assignment->schedule_date ? \Carbon\Carbon::parse($assignment->schedule_date)->format('d F Y') : '-' }}
                                    </td>
                                    <td class="p-3 font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $assignment->shift?->name ?? 'Reguler' }}
                                    </td>
                                    <td class="p-3 font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ $assignment->shift?->start_time ?? '-' }} - {{ $assignment->shift?->end_time ?? '-' }}
                                    </td>
                                    <td class="p-3 text-zinc-600 dark:text-zinc-400">
                                        {{ $assignment->unit?->name ?? '-' }}
                                    </td>
                                    <td class="p-3">
                                        @if($assignment->status === 'completed')
                                            <flux:badge size="xs" color="emerald">Selesai</flux:badge>
                                        @elseif($assignment->status === 'scheduled')
                                            <flux:badge size="xs" color="blue">Terjadwal</flux:badge>
                                        @else
                                            <flux:badge size="xs" color="zinc">{{ ucfirst($assignment->status) }}</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 3: PRESENSI --}}
    @if($activeTab === 'attendance')
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div>
                    <flux:heading size="md">Riwayat Presensi & Kehadiran Medis</flux:heading>
                    <flux:subheading class="text-xs text-zinc-500">Log kehadiran clock-in dan clock-out dokter.</flux:subheading>
                </div>
                <a href="{{ route('attendance.index') }}">
                    <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square">
                        Direktori Presensi
                    </flux:button>
                </a>
            </div>

            @if($doctor->attendances->isEmpty())
                <div class="p-8 text-center text-zinc-400">
                    <flux:icon name="clock" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                    <p class="text-sm">Belum ada catatan presensi untuk dokter ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Tanggal</th>
                                <th class="p-3">Jam Masuk (Clock In)</th>
                                <th class="p-3">Jam Keluar (Clock Out)</th>
                                <th class="p-3">Keterlambatan</th>
                                <th class="p-3">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($doctor->attendances as $att)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                    <td class="p-3 font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $att->date ? \Carbon\Carbon::parse($att->date)->format('d F Y') : '-' }}
                                    </td>
                                    <td class="p-3 font-mono text-zinc-800 dark:text-zinc-200">
                                        {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="p-3 font-mono text-zinc-800 dark:text-zinc-200">
                                        {{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="p-3 font-mono">
                                        @if(($att->late_minutes ?? 0) > 0)
                                            <span class="text-rose-600 font-semibold">{{ $att->late_minutes }} Menit</span>
                                        @else
                                            <span class="text-zinc-400">Tepat Waktu</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if($att->status === 'present')
                                            <flux:badge size="xs" color="emerald">Hadir</flux:badge>
                                        @elseif($att->status === 'late')
                                            <flux:badge size="xs" color="amber">Terlambat</flux:badge>
                                        @elseif($att->status === 'absent')
                                            <flux:badge size="xs" color="rose">Alpa</flux:badge>
                                        @else
                                            <flux:badge size="xs" color="zinc">{{ ucfirst($att->status ?? 'Normal') }}</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 4: CUTI & IZIN --}}
    @if($activeTab === 'leave')
        <div class="space-y-6">
            {{-- Kuota Cuti --}}
            <flux:card class="space-y-4">
                <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <flux:heading size="md">Alokasi & Kuota Cuti Dokter</flux:heading>
                    <flux:subheading class="text-xs text-zinc-500">Saldo hak cuti tahunan dan izin medis.</flux:subheading>
                </div>

                @if($doctor->leaveBalances->isEmpty())
                    <p class="text-xs text-zinc-400">Belum ada alokasi saldo cuti aktif.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($doctor->leaveBalances as $balance)
                            <div class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30">
                                <span class="text-xs font-semibold text-zinc-500">{{ $balance->leaveType?->name ?? 'Cuti' }}</span>
                                <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                    {{ $balance->remaining_days ?? 0 }} <span class="text-xs font-normal text-zinc-400">/ {{ $balance->allocated_days ?? 0 }} Hari</span>
                                </div>
                                <div class="text-[11px] text-zinc-400 mt-0.5">Tahun {{ $balance->year }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            {{-- Riwayat Pengajuan --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <flux:heading size="md">Riwayat Pengajuan Cuti & Izin</flux:heading>
                        <flux:subheading class="text-xs text-zinc-500">Permohonan izin dan disposisi persetujuan berjenjang.</flux:subheading>
                    </div>
                    <a href="{{ route('leaves.index') }}">
                        <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square">
                            Modul Cuti
                        </flux:button>
                    </a>
                </div>

                @if($doctor->leaveRequests->isEmpty())
                    <div class="p-8 text-center text-zinc-400">
                        <flux:icon name="document-text" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                        <p class="text-sm">Belum ada riwayat pengajuan cuti atau izin.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500 uppercase tracking-wider">
                                <tr>
                                    <th class="p-3">Jenis Permohonan</th>
                                    <th class="p-3">Periode</th>
                                    <th class="p-3">Durasi</th>
                                    <th class="p-3">Alasan</th>
                                    <th class="p-3">Status Disposisi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach($doctor->leaveRequests as $req)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                        <td class="p-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $req->leaveType?->name ?? 'Izin Medis' }}
                                        </td>
                                        <td class="p-3 text-zinc-600 dark:text-zinc-400">
                                            {{ $req->start_date ? \Carbon\Carbon::parse($req->start_date)->format('d/m/Y') : '-' }} s/d {{ $req->end_date ? \Carbon\Carbon::parse($req->end_date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="p-3 font-mono font-medium">
                                            {{ $req->total_days }} Hari
                                        </td>
                                        <td class="p-3 text-zinc-500 max-w-xs truncate">
                                            {{ $req->reason }}
                                        </td>
                                        <td class="p-3">
                                            @if($req->status === 'approved')
                                                <flux:badge size="xs" color="emerald">Disetujui</flux:badge>
                                            @elseif($req->status === 'rejected')
                                                <flux:badge size="xs" color="rose">Ditolak</flux:badge>
                                            @elseif($req->status === 'submitted')
                                                <flux:badge size="xs" color="amber">Menunggu Persetujuan</flux:badge>
                                            @else
                                                <flux:badge size="xs" color="zinc">{{ ucfirst($req->status) }}</flux:badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif

    {{-- TAB 5: BERKAS & DOKUMEN --}}
    @if($activeTab === 'documents')
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div>
                    <flux:heading size="md">Arsip Berkas Kredensial Medis</flux:heading>
                    <flux:subheading class="text-xs text-zinc-500">Scan STR, SIP, sertifikat pelatihan, dan kredensial klinis.</flux:subheading>
                </div>
                @if($canUploadDoc)
                    <flux:button size="sm" variant="primary" icon="plus" wire:click="openDocModal">
                        Unggah Berkas Kredensial
                    </flux:button>
                @endif
            </div>

            @if($doctor->documents->isEmpty())
                <div class="p-8 text-center text-zinc-400">
                    <flux:icon name="folder" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                    <p class="text-sm">Belum ada dokumen digital yang tersimpan pada rekam dokter ini.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($doctor->documents as $doc)
                        <div class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-800/40 flex flex-col justify-between space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                        <flux:icon name="document-text" class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                            {{ strtoupper($doc->document_type) }}
                                        </div>
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate" title="{{ $doc->original_name }}">
                                            {{ $doc->original_name }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-[11px] text-zinc-400 flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-2">
                                <span>{{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</span>
                                <span>{{ $doc->created_at ? $doc->created_at->format('d/m/Y') : '-' }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ $doc->getTemporaryDownloadUrl(30) }}" target="_blank" class="flex-1">
                                    <flux:button size="xs" variant="subtle" icon="arrow-down-tray" class="w-full">
                                        Unduh
                                    </flux:button>
                                </a>
                                @can('delete', $doc)
                                    <flux:button size="xs" variant="danger" icon="trash" wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Hapus dokumen ini dari arsip?" />
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 6: AKUN PORTAL --}}
    @if($activeTab === 'account')
        <flux:card class="max-w-2xl space-y-6">
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <flux:heading size="md">Pengaturan Akun Portal Tenaga Medis</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Akses login dokter ke sistem Rumah Sakit Juwita.</flux:subheading>
            </div>

            @if($generatedPasswordNotice)
                <div class="p-3 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 rounded-xl text-xs font-medium text-amber-800 dark:text-amber-200 flex items-center gap-2">
                    <flux:icon name="key" class="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                    <span>{{ $generatedPasswordNotice }}</span>
                </div>
            @endif

            @if($doctor->user)
                <div class="space-y-4">
                    <div class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-medium text-zinc-500">Akun Pengguna Terhubung</span>
                            <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $doctor->user->email }}</div>
                            <div class="text-xs text-zinc-400 mt-0.5">
                                Status: <strong class="{{ $doctor->user->is_active ? 'text-emerald-600' : 'text-rose-600' }}">{{ $doctor->user->is_active ? 'Aktif' : 'Nonaktif' }}</strong>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="subtle" wire:click="toggleUserStatus">
                                {{ $doctor->user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </flux:button>
                        </div>
                    </div>

                    {{-- Form Reset Password --}}
                    <form wire:submit="resetUserPassword" class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                        <flux:heading size="sm">Ganti Password Akun</flux:heading>
                        
                        <flux:input 
                            wire:model="newPassword" 
                            label="Password Baru" 
                            type="password" 
                            placeholder="Minimal 8 karakter"
                            required 
                        />

                        <flux:input 
                            wire:model="newPasswordConfirmation" 
                            label="Konfirmasi Password Baru" 
                            type="password" 
                            placeholder="Ulangi password baru"
                            required 
                        />

                        <div class="pt-2">
                            <flux:button type="submit" variant="primary" size="sm">
                                Simpan Password Baru
                            </flux:button>
                        </div>
                    </form>
                </div>
            @else
                <div class="p-6 text-center space-y-4 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
                    <flux:icon name="user-plus" class="size-10 mx-auto text-zinc-400" />
                    <div>
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Dokter Belum Memiliki Akun Login</h4>
                        <p class="text-xs text-zinc-500 mt-1">Buat akun portal mandiri untuk memungkinkan dokter melihat jadwal, presensi, dan mengajukan cuti.</p>
                    </div>

                    <flux:button variant="primary" size="sm" wire:click="openAccountModal">
                        Aktifkan Akun Portal Dokter
                    </flux:button>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- MODAL: BIODATA & KONTAK --}}
    @if($showBioModal)
        <flux:modal wire:model="showBioModal" class="w-full max-w-xl space-y-6">
            <div>
                <flux:heading size="lg">Perbarui Biodata & Kontak Dokter</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Edit identitas klinis dan nomor telepon.</flux:subheading>
            </div>

            <form wire:submit="saveBio" class="space-y-4">
                <flux:input 
                    wire:model="bioName" 
                    label="Nama Lengkap & Gelar" 
                    required 
                />

                <flux:input 
                    wire:model="bioSpecialization" 
                    label="Spesialisasi Klinis" 
                    required 
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="bioEmail" 
                        label="Alamat Email" 
                        type="email" 
                    />

                    <flux:input 
                        wire:model="bioPhone" 
                        label="Nomor Telepon / WhatsApp" 
                    />
                </div>

                <flux:select wire:model="bioStatus" label="Status Praktik">
                    <flux:select.option value="active">Aktif</flux:select.option>
                    <flux:select.option value="inactive">Nonaktif</flux:select.option>
                </flux:select>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button variant="subtle" wire:click="$set('showBioModal', false)">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Simpan Perubahan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: KREDENSIAL STR & SIP --}}
    @if($showCredentialModal)
        <flux:modal wire:model="showCredentialModal" class="w-full max-w-xl space-y-6">
            <div>
                <flux:heading size="lg">Perbarui Kredensial STR & SIP</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Pembaruan nomor dan masa berlaku izin lisensi medis.</flux:subheading>
            </div>

            <form wire:submit="saveCredentials" class="space-y-4">
                <div class="space-y-3 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
                    <flux:input 
                        wire:model="strNumber" 
                        label="Nomor STR (Kemenkes)" 
                        required 
                    />

                    <flux:input 
                        wire:model="strValidUntil" 
                        label="Masa Berlaku STR" 
                        type="date" 
                        required 
                    />
                </div>

                <div class="space-y-3 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
                    <flux:input 
                        wire:model="sipNumber" 
                        label="Nomor SIP (Dinkes)" 
                        required 
                    />

                    <flux:input 
                        wire:model="sipValidUntil" 
                        label="Masa Berlaku SIP" 
                        type="date" 
                        required 
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button variant="subtle" wire:click="$set('showCredentialModal', false)">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Perbarui Kredensial
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: LINK PEGAWAI ORGANIK --}}
    @if($showLinkModal)
        <flux:modal wire:model="showLinkModal" class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="lg">Tautan Pegawai Organik RS</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Hubungkan dokter dengan profil pegawai organik RS Juwita.</flux:subheading>
            </div>

            <form wire:submit="saveLinkEmployee" class="space-y-4">
                <flux:select wire:model="selectedEmployeeId" label="Pilih Pegawai Organik RS">
                    <flux:select.option value="">-- Bukan Pegawai Organik (Mitra / Tamu) --</flux:select.option>
                    @foreach($availableEmployees as $emp)
                        <flux:select.option value="{{ $emp->id }}">
                            {{ $emp->full_name }} (NIP: {{ $emp->nip }}) - {{ $emp->unit?->name ?? 'RS' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button variant="subtle" wire:click="$set('showLinkModal', false)">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Simpan Tautan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: UNGGAH DOKUMEN --}}
    @if($showDocModal)
        <flux:modal wire:model="showDocModal" class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="lg">Unggah Berkas Kredensial Medis</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">File dokumen digital pendukung (PDF, JPG, PNG maks 10MB).</flux:subheading>
            </div>

            <form wire:submit="uploadDoc" class="space-y-4">
                <flux:select wire:model="docUploadType" label="Kategori Dokumen" required>
                    <flux:select.option value="str">Scan Surat Tanda Registrasi (STR)</flux:select.option>
                    <flux:select.option value="sip">Scan Surat Izin Praktik (SIP)</flux:select.option>
                    <flux:select.option value="certificate">Sertifikat Kredensial / Pelatihan Medis</flux:select.option>
                    <flux:select.option value="ijazah">Ijazah & Spesialisasi Medis</flux:select.option>
                    <flux:select.option value="contract">Perjanjian Kerja Sama Medis</flux:select.option>
                    <flux:select.option value="other">Dokumen Pendukung Lainnya</flux:select.option>
                </flux:select>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Pilih Berkas</label>
                    <input 
                        type="file" 
                        wire:model="docUploadedFile" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-300 cursor-pointer"
                        required 
                    />
                    <div wire:loading wire:target="docUploadedFile" class="text-xs text-emerald-600 mt-1">
                        Mengunggah berkas...
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button variant="subtle" wire:click="$set('showDocModal', false)">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Unggah Dokumen
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: BUAT AKUN PORTAL --}}
    @if($showAccountModal)
        <flux:modal wire:model="showAccountModal" class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="lg">Aktivasi Akun Portal Dokter</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Buat akun pengguna mandiri untuk dokter ini.</flux:subheading>
            </div>

            <form wire:submit="createPortalAccount" class="space-y-4">
                <flux:input 
                    wire:model="accountEmail" 
                    label="Email Login Portal" 
                    type="email" 
                    placeholder="dokter@rsjuwita.co.id" 
                    required 
                />

                <div class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800/40 p-3 rounded-lg">
                    Sistem akan otomatis meng-generate kata sandi sementara dan memberikan peran (role) <strong>Dokter (doctor)</strong>.
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button variant="subtle" wire:click="$set('showAccountModal', false)">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Buat & Aktifkan Akun
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

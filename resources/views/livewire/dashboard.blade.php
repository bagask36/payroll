<div class="space-y-8">
    {{-- ========================================================================= --}}
    {{-- 1. ROLE: EMPLOYEE & DOCTOR (SELF-SERVICE PORTAL DASHBOARD)                --}}
    {{-- ========================================================================= --}}
    @if($userRole === 'employee' || $userRole === 'doctor')
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $userRole === 'doctor' ? 'Portal Tenaga Medis & Dokter' : 'Portal Layanan Mandiri Pegawai' }}
                </flux:heading>
                <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Selamat datang kembali, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>
                    @if(auth()->user()?->employee)
                        • {{ auth()->user()->employee->position?->title ?? 'Staf' }} ({{ auth()->user()->employee->unit?->name ?? 'RS Juwita' }})
                    @elseif($myDoctorCredentials)
                        • {{ $myDoctorCredentials['specialization'] }}
                    @endif
                </flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                <flux:badge size="lg" color="zinc" class="px-3 py-1 font-mono">
                    {{ now()->translatedFormat('l, d F Y') }}
                </flux:badge>
                <flux:button wire:click="refreshData" icon="arrow-path" variant="subtle" size="sm">
                    Segarkan
                </flux:button>
            </div>
        </div>

        {{-- Doctor License Credential Card (If Doctor) --}}
        @if($myDoctorCredentials)
            <div class="p-4 rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/60 dark:bg-blue-950/30 text-blue-950 dark:text-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900/50 rounded-lg text-blue-700 dark:text-blue-300 shrink-0">
                        <flux:icon name="identification" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase tracking-wider">Kredensial Praktik Medis Anda</div>
                        <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2 mt-0.5">
                            <span>STR: <span class="font-mono">{{ $myDoctorCredentials['str_number'] ?? '-' }}</span> (s/d {{ $myDoctorCredentials['str_valid_until'] ?? '-' }})</span>
                            @if($myDoctorCredentials['is_str_expired'])
                                <flux:badge size="xs" color="rose">STR Kedaluwarsa</flux:badge>
                            @elseif($myDoctorCredentials['is_str_expiring_soon'])
                                <flux:badge size="xs" color="amber">STR Segera Berakhir</flux:badge>
                            @else
                                <flux:badge size="xs" color="emerald">STR Aktif</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ route('documents.index') }}">
                        <flux:button size="sm" variant="outline" icon="document-arrow-up">
                            Arsip Dokumen Lisensi
                        </flux:button>
                    </a>
                </div>
            </div>
        @endif

        {{-- 4 Primary Metric Cards for Employee / Doctor --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Shift Hari Ini --}}
            <flux:card class="border-l-4 border-l-blue-500 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-zinc-500 block">Jadwal Tugas Hari Ini</span>
                    <div class="mt-1">
                        <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100 block">
                            {{ $myShiftToday['shift_name'] ?? 'Tidak Ada Jadwal / Libur' }}
                        </span>
                        @if($myShiftToday)
                            <span class="text-xs font-mono text-blue-600 dark:text-blue-400 font-medium">
                                {{ $myShiftToday['start_time'] }} - {{ $myShiftToday['end_time'] }} WIB
                            </span>
                        @else
                            <span class="text-xs text-zinc-400">Jadwal Off / Istirahat</span>
                        @endif
                    </div>
                </div>
                <div class="pt-3 mt-2 border-t border-zinc-100 dark:border-zinc-800 text-[11px] text-zinc-400 flex items-center justify-between">
                    <span>Besok: {{ $myShiftTomorrow['shift_name'] ?? 'Libur' }}</span>
                    <a href="{{ route('schedules.index') }}" class="text-blue-600 hover:underline">Roster &rarr;</a>
                </div>
            </flux:card>

            {{-- Card 2: Presensi Hari Ini --}}
            <flux:card class="border-l-4 border-l-emerald-500 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-zinc-500 block">Presensi Hari Ini</span>
                    <div class="mt-1">
                        @if($myAttendanceToday && $myAttendanceToday['has_checked_in'])
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold text-emerald-600">Masuk: {{ $myAttendanceToday['clock_in'] }}</span>
                                @if($myAttendanceToday['late_minutes'] > 0)
                                    <flux:badge size="xs" color="amber">+{{ $myAttendanceToday['late_minutes'] }}m</flux:badge>
                                @endif
                            </div>
                            <span class="text-xs text-zinc-500">
                                Pulang: {{ $myAttendanceToday['clock_out'] ?? 'Belum Check-Out' }}
                            </span>
                        @else
                            <span class="text-lg font-bold text-amber-600">Belum Presensi</span>
                            <span class="text-xs text-zinc-400 block">Silakan lakukan clock-in</span>
                        @endif
                    </div>
                </div>
                <div class="pt-3 mt-2 border-t border-zinc-100 dark:border-zinc-800 text-[11px] text-zinc-400 flex items-center justify-between">
                    <span>Status: {{ ucfirst(str_replace('_', ' ', $myAttendanceToday['status'] ?? 'Belum Hadir')) }}</span>
                    <a href="{{ route('attendance.index') }}" class="text-emerald-600 hover:underline">Presensi &rarr;</a>
                </div>
            </flux:card>

            {{-- Card 3: Saldo Cuti Tersisa --}}
            <flux:card class="border-l-4 border-l-purple-500 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-zinc-500 block">Sisa Saldo Cuti ({{ now()->year }})</span>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-purple-600">
                            {{ $myLeaveQuota['remaining_days'] ?? 0 }}
                        </span>
                        <span class="text-xs font-semibold text-zinc-500">Hari Kerja</span>
                    </div>
                    <div class="text-[11px] text-zinc-400 mt-0.5">
                        Terpakai: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $myLeaveQuota['used_days'] ?? 0 }}</span> Hari
                    </div>
                </div>
                <div class="pt-3 mt-2 border-t border-zinc-100 dark:border-zinc-800 text-[11px] flex items-center justify-between">
                    <span class="text-zinc-400">Total Kuota: {{ $myLeaveQuota['allocated_days'] ?? 0 }} Hari</span>
                    <a href="{{ route('leaves.apply') }}" class="text-purple-600 hover:underline">Ajukan &rarr;</a>
                </div>
            </flux:card>

            {{-- Card 4: Slip Gaji Terakhir --}}
            <flux:card class="border-l-4 border-l-amber-500 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-zinc-500 block">Gaji Bersih (Slip Terakhir)</span>
                    <div class="mt-1">
                        @if($myLatestPayslip)
                            <span class="text-lg font-bold font-mono text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) $myLatestPayslip['net_salary'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-zinc-500 block">{{ $myLatestPayslip['period_name'] }}</span>
                        @else
                            <span class="text-sm font-semibold text-zinc-500">Belum Ada Slip</span>
                            <span class="text-xs text-zinc-400 block">Periode aktif berjalan</span>
                        @endif
                    </div>
                </div>
                <div class="pt-3 mt-2 border-t border-zinc-100 dark:border-zinc-800 text-[11px] flex items-center justify-between">
                    <span class="text-emerald-600 font-medium">{{ $myLatestPayslip ? 'Finalized' : '-' }}</span>
                    <a href="{{ route('payroll.my-payslips') }}" class="text-amber-600 hover:underline">Slip Saya &rarr;</a>
                </div>
            </flux:card>
        </div>

        {{-- Quick Access Self-Service Links --}}
        <div>
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Akses Cepat Layanan Mandiri</h3>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('attendance.index') }}" class="p-3.5 bg-white dark:bg-zinc-800/80 rounded-xl border border-zinc-200 dark:border-zinc-700/80 hover:border-emerald-500 transition-all flex items-center gap-3 group">
                    <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <flux:icon name="clock" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Presensi & Geofence</div>
                        <div class="text-[11px] text-zinc-400">Clock-in / History</div>
                    </div>
                </a>

                <a href="{{ route('leaves.apply') }}" class="p-3.5 bg-white dark:bg-zinc-800/80 rounded-xl border border-zinc-200 dark:border-zinc-700/80 hover:border-purple-500 transition-all flex items-center gap-3 group">
                    <div class="p-2.5 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <flux:icon name="document-text" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Permohonan Cuti</div>
                        <div class="text-[11px] text-zinc-400">Buat Pengajuan Baru</div>
                    </div>
                </a>

                <a href="{{ route('schedules.index') }}" class="p-3.5 bg-white dark:bg-zinc-800/80 rounded-xl border border-zinc-200 dark:border-zinc-700/80 hover:border-blue-500 transition-all flex items-center gap-3 group">
                    <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <flux:icon name="calendar" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Shift & Roster</div>
                        <div class="text-[11px] text-zinc-400">Jadwal Bulanan</div>
                    </div>
                </a>

                <a href="{{ route('payroll.my-payslips') }}" class="p-3.5 bg-white dark:bg-zinc-800/80 rounded-xl border border-zinc-200 dark:border-zinc-700/80 hover:border-amber-500 transition-all flex items-center gap-3 group">
                    <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <flux:icon name="receipt-percent" class="size-5" />
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Slip Gaji Saya</div>
                        <div class="text-[11px] text-zinc-400">Unduh Bukti Transfer</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recent Leave Applications List --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Pengajuan Cuti Terakhir Saya</h3>
                <a href="{{ route('leaves.index') }}" class="text-xs text-purple-600 hover:underline">Lihat Semua &rarr;</a>
            </div>
            @if(!empty($myRecentLeaves))
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach($myRecentLeaves as $leave)
                        <flux:card class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $leave['type'] }}</span>
                                @php
                                    $stBadge = match ($leave['status']) {
                                        'approved' => ['color' => 'emerald', 'label' => 'Disetujui'],
                                        'rejected' => ['color' => 'rose', 'label' => 'Ditolak'],
                                        'cancelled' => ['color' => 'zinc', 'label' => 'Dibatalkan'],
                                        default => ['color' => 'amber', 'label' => 'Menunggu Verifikasi'],
                                    };
                                @endphp
                                <flux:badge size="xs" :color="$stBadge['color']">{{ $stBadge['label'] }}</flux:badge>
                            </div>
                            <div class="text-xs font-mono text-zinc-500">
                                {{ $leave['start_date'] }} s/d {{ $leave['end_date'] }} ({{ $leave['total_days'] }} Hari)
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 truncate" title="{{ $leave['reason'] }}">
                                "{{ $leave['reason'] }}"
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            @else
                <flux:card class="text-center py-6 text-xs text-zinc-400">
                    Belum ada riwayat permohonan cuti baru-baru ini.
                </flux:card>
            @endif
        </div>

    {{-- ========================================================================= --}}
    {{-- 2. ROLE: SUPERVISOR (UNIT / WARD HEAD MONITORING DASHBOARD)               --}}
    {{-- ========================================================================= --}}
    @elseif($userRole === 'supervisor')
        {{-- Supervisor Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Dashboard Operasional Unit Kerja
                </flux:heading>
                <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Kepala Unit / Supervisor: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>
                    @if($teamMetrics)
                        • Unit: <span class="text-emerald-600 font-semibold">{{ $teamMetrics['unit_name'] }}</span>
                    @endif
                </flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                <flux:badge size="lg" color="zinc" class="px-3 py-1 font-mono">
                    {{ now()->translatedFormat('l, d F Y') }}
                </flux:badge>
                <flux:button wire:click="refreshData" icon="arrow-path" variant="subtle" size="sm">
                    Segarkan
                </flux:button>
            </div>
        </div>

        {{-- Supervisor Team Overview Cards --}}
        @if($teamMetrics)
            <div>
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Monitoring Tim Unit ({{ $teamMetrics['unit_name'] }})</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <flux:card class="border-l-4 border-l-blue-500">
                        <div class="text-xs font-medium text-zinc-500">Total Staf Unit</div>
                        <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                            {{ $teamMetrics['total_team'] }} <span class="text-xs font-normal text-zinc-400">Orang</span>
                        </div>
                    </flux:card>

                    <flux:card class="border-l-4 border-l-emerald-500">
                        <div class="text-xs font-medium text-zinc-500">Staf Hadir Hari Ini</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600 font-mono">
                            {{ $teamMetrics['present_today'] }} <span class="text-xs font-normal text-zinc-400">Hadir</span>
                        </div>
                    </flux:card>

                    <flux:card class="border-l-4 border-l-amber-500 flex flex-col justify-between">
                        <div>
                            <div class="text-xs font-medium text-zinc-500">Antrean Cuti Tier 1</div>
                            <div class="mt-1 text-2xl font-bold text-amber-600 font-mono">
                                {{ $teamMetrics['pending_approvals'] }} <span class="text-xs font-normal text-zinc-400">Menunggu</span>
                            </div>
                        </div>
                        <div class="pt-2 text-[11px]">
                            <a href="{{ route('leaves.index') }}" class="text-amber-600 hover:underline font-semibold">Verifikasi Sekarang &rarr;</a>
                        </div>
                    </flux:card>

                    <flux:card class="border-l-4 border-l-purple-500">
                        <div class="text-xs font-medium text-zinc-500">Staf Cuti / Izin Hari Ini</div>
                        <div class="mt-1 text-2xl font-bold text-purple-600 font-mono">
                            {{ $teamMetrics['on_leave_today'] }} <span class="text-xs font-normal text-zinc-400">Orang</span>
                        </div>
                    </flux:card>
                </div>
            </div>
        @else
            <flux:card class="text-center py-6 text-zinc-400">
                <div class="text-xs">Akun Anda belum ditugaskan ke Unit Kerja tertentu. Hubungi HRD untuk menetapkan unit kerja supervisor.</div>
            </flux:card>
        @endif

        {{-- Personal Self-Service Widget for Supervisor --}}
        <div>
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Jadwal & Presensi Pribadi Anda</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:card class="space-y-1">
                    <span class="text-xs text-zinc-500 block">Jadwal Hari Ini</span>
                    <span class="text-base font-bold text-zinc-900 dark:text-zinc-100 block">{{ $myShiftToday['shift_name'] ?? 'Jadwal Normal' }}</span>
                    @if($myShiftToday)
                        <span class="text-xs font-mono text-zinc-400">{{ $myShiftToday['start_time'] }} - {{ $myShiftToday['end_time'] }} WIB</span>
                    @endif
                </flux:card>

                <flux:card class="space-y-1">
                    <span class="text-xs text-zinc-500 block">Presensi Pribadi</span>
                    <span class="text-base font-bold text-emerald-600 block">{{ !empty($myAttendanceToday['clock_in']) ? 'Hadir: '.$myAttendanceToday['clock_in'] : 'Belum Check-In' }}</span>
                    <a href="{{ route('attendance.index') }}" class="text-xs text-emerald-600 hover:underline">Buka Presensi &rarr;</a>
                </flux:card>

                <flux:card class="space-y-1">
                    <span class="text-xs text-zinc-500 block">Sisa Cuti Tahunan</span>
                    <span class="text-base font-bold text-purple-600 font-mono block">{{ $myLeaveQuota['remaining_days'] ?? 0 }} Hari</span>
                    <a href="{{ route('leaves.index') }}" class="text-xs text-purple-600 hover:underline">Kelola Cuti &rarr;</a>
                </flux:card>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 3. ROLE: PAYROLL & FINANCE (COMPENSATION & DISBURSEMENT DASHBOARD)         --}}
    {{-- ========================================================================= --}}
    @elseif($userRole === 'payroll_finance')
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Dashboard Penggajian & Kompensasi
                </flux:heading>
                <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Selamat datang, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>. Monitor eksekusi siklus payroll dan pencairan dana.
                </flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                <flux:badge size="lg" color="zinc" class="px-3 py-1 font-mono">
                    {{ now()->translatedFormat('l, d F Y') }}
                </flux:badge>
                <flux:button wire:click="refreshData" icon="arrow-path" variant="subtle" size="sm">
                    Segarkan
                </flux:button>
            </div>
        </div>

        @if($payroll)
            <div>
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Rekapitulasi Penggajian Terakhir</h3>
                </div>
                <flux:card class="grid grid-cols-1 md:grid-cols-4 gap-6 bg-gradient-to-br from-zinc-50 to-emerald-50/20 dark:from-zinc-900 dark:to-zinc-800">
                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Penghasilan Kotor (Bruto)</div>
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                            Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Potongan & Pajak</div>
                        <div class="text-lg font-bold text-red-600 font-mono">
                            - Rp {{ number_format((float) ($payroll['total_deductions'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Slip Gaji Karyawan</div>
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $payroll['total_payslips'] ?? 0 }} Slip
                        </div>
                    </div>

                    <div class="p-3 bg-white dark:bg-zinc-900 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <div class="text-xs font-medium text-zinc-500">Dana Bersih (Netto) Disalurkan</div>
                        <div class="text-xl font-extrabold text-emerald-600 font-mono">
                            Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                </flux:card>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('payroll.index') }}">
                    <flux:button variant="primary" icon="banknotes">
                        Buka Manajemen Payroll
                    </flux:button>
                </a>
                <a href="{{ route('reports.payroll') }}">
                    <flux:button variant="outline" icon="chart-bar">
                        Laporan Penggajian
                    </flux:button>
                </a>
            </div>
        @else
            <flux:card class="text-center py-8 text-zinc-400">
                <div class="font-medium text-sm text-zinc-600 dark:text-zinc-300">Belum Ada Siklus Penggajian</div>
                <div class="text-xs text-zinc-400 mt-1">Data ringkasan penggajian dan pembayaran akan muncul setelah ada payroll yang dihitung.</div>
                <div class="mt-4">
                    <a href="{{ route('payroll.index') }}">
                        <flux:button variant="primary" icon="banknotes" size="sm">
                            Buka Modul Payroll
                        </flux:button>
                    </a>
                </div>
            </flux:card>
        @endif

    {{-- ========================================================================= --}}
    {{-- 4. ROLE: MANAGEMENT (SUPER ADMIN, HR ADMIN, HR MANAGER)                  --}}
    {{-- ========================================================================= --}}
    @else
        {{-- Management Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Dashboard Operasional & SDM
                </flux:heading>
                <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Selamat datang kembali, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>. Pantau indikator kinerja rumah sakit terkini.
                </flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                <flux:badge size="lg" color="zinc" class="px-3 py-1 font-mono">
                    {{ now()->translatedFormat('l, d F Y') }}
                </flux:badge>
                <flux:button wire:click="refreshData" icon="arrow-path" variant="subtle" size="sm">
                    Segarkan
                </flux:button>
            </div>
        </div>

        {{-- Doctor Credential Compliance Warning Alert --}}
        @if(!empty($doctorAlerts))
            <div class="p-4 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 space-y-2">
                <div class="flex items-center gap-2 font-semibold">
                    <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    <span>Peringatan Kepatuhan Izin Praktik Medis ({{ count($doctorAlerts) }} Dokter Perlu Perhatian)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                    @foreach(array_slice($doctorAlerts, 0, 6) as $doc)
                        <div class="p-3 bg-white/80 dark:bg-zinc-900/80 rounded-lg border border-amber-200 dark:border-amber-900/60 text-xs">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $doc['name'] }}</div>
                            <div class="text-zinc-500">{{ $doc['specialization'] ?? 'Dokter Umum' }}</div>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span>STR: {{ $doc['str_valid_until'] }}</span>
                                <flux:badge size="sm" color="amber">Segera Berakhir</flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Staffing Distribution Metrics --}}
        <div>
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Distribusi Ketenagaan Rumah Sakit</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <flux:card class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                        <flux:icon name="users" class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Karyawan Aktif</div>
                        <div class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ number_format($staffing['total_active_employees'] ?? 0) }}</div>
                    </div>
                </flux:card>

                <flux:card class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <flux:icon name="user-group" class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500">Dokter & Spesialis Aktif</div>
                        <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($staffing['total_active_doctors'] ?? 0) }}</div>
                    </div>
                </flux:card>

                <flux:card class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                        <flux:icon name="building-office-2" class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Tenaga Kerja Keseluruhan</div>
                        <div class="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400">{{ number_format($staffing['total_workforce'] ?? 0) }}</div>
                    </div>
                </flux:card>
            </div>
        </div>

        {{-- Today's Attendance Overview --}}
        <div>
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Rekapitulasi Kehadiran Hari Ini ({{ $attendance['date'] ?? now()->toDateString() }})</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <flux:card class="border-l-4 border-l-blue-500">
                    <div class="text-xs font-medium text-zinc-500">Jadwal Terdaftar</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $attendance['total_scheduled'] ?? 0 }}</span>
                        <span class="text-xs text-zinc-500 font-medium">Staf</span>
                    </div>
                </flux:card>

                <flux:card class="border-l-4 border-l-emerald-500">
                    <div class="text-xs font-medium text-zinc-500">Hadir Tepat Waktu</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-emerald-600">{{ $attendance['present_count'] ?? 0 }}</span>
                        <span class="text-xs text-emerald-600 font-medium">Staf</span>
                    </div>
                </flux:card>

                <flux:card class="border-l-4 border-l-amber-500">
                    <div class="text-xs font-medium text-zinc-500">Hadir Terlambat</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-amber-600">{{ $attendance['late_count'] ?? 0 }}</span>
                        <span class="text-xs text-amber-600 font-medium">Staf</span>
                    </div>
                </flux:card>

                <flux:card class="border-l-4 border-l-purple-500">
                    <div class="text-xs font-medium text-zinc-500">Total Check-In</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-purple-600">{{ $attendance['total_checked_in'] ?? 0 }}</span>
                        <span class="text-xs text-purple-600 font-medium">Staf</span>
                    </div>
                </flux:card>
            </div>
        </div>

        {{-- Payroll Summary Card --}}
        @if($payroll)
            <div>
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-zinc-500 uppercase tracking-wider">Ringkasan Penggajian Terakhir</h3>
                </div>
                <flux:card class="grid grid-cols-1 md:grid-cols-4 gap-6 bg-gradient-to-br from-zinc-50 to-emerald-50/20 dark:from-zinc-900 dark:to-zinc-800">
                    <div>
                        <div class="text-xs font-medium text-zinc-500">Gaji Kotor (Bruto)</div>
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                            Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Potongan & Pajak</div>
                        <div class="text-lg font-bold text-red-600 font-mono">
                            - Rp {{ number_format((float) ($payroll['total_deductions'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-zinc-500">Total Slip Gaji</div>
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $payroll['total_payslips'] ?? 0 }} Slip
                        </div>
                    </div>

                    <div class="p-3 bg-white dark:bg-zinc-900 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <div class="text-xs font-medium text-zinc-500">Take Home Pay (Bersih)</div>
                        <div class="text-xl font-extrabold text-emerald-600 font-mono">
                            Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    @endif
</div>

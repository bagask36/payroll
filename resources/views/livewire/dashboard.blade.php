<div class="space-y-8">
    {{-- ========================================================================= --}}
    {{-- 1. ROLE: EMPLOYEE & DOCTOR (SELF-SERVICE PORTAL DASHBOARD)                --}}
    {{-- ========================================================================= --}}
    @if($userRole === 'employee' || $userRole === 'doctor')
        {{-- Hero Header Section --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-8 -top-8 size-48 rounded-full bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent blur-2xl dark:from-emerald-500/20"></div>
            <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-500/20">
                        <flux:icon :name="$userRole === 'doctor' ? 'user-group' : 'identification'" class="size-7" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                {{ $userRole === 'doctor' ? 'Portal Tenaga Medis & Dokter' : 'Portal Layanan Mandiri Pegawai' }}
                            </flux:heading>
                            <flux:badge size="sm" :color="$userRole === 'doctor' ? 'blue' : 'emerald'">
                                {{ $userRole === 'doctor' ? 'Tenaga Medis' : 'Staf Karyawan' }}
                            </flux:badge>
                        </div>
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->greeting }}, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>
                            @if(auth()->user()?->employee)
                                <span class="mx-1 text-zinc-300 dark:text-zinc-700">•</span>
                                <span>{{ auth()->user()->employee->position?->title ?? 'Staf' }}</span>
                                <span class="text-zinc-400 dark:text-zinc-500">({{ auth()->user()->employee->unit?->name ?? 'RS Juwita' }})</span>
                            @elseif($myDoctorCredentials)
                                <span class="mx-1 text-zinc-300 dark:text-zinc-700">•</span>
                                <span>{{ $myDoctorCredentials['specialization'] }}</span>
                            @endif
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3.5 py-2 text-xs font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-mono">{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="cursor-pointer">
                        <span wire:loading.remove wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4" />
                            <span>Segarkan</span>
                        </span>
                        <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-emerald-600" />
                            <span>Memuat...</span>
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Doctor License Credential Card (If Doctor) --}}
        @if($myDoctorCredentials)
            <div class="overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50/90 via-indigo-50/50 to-white p-5 shadow-xs dark:border-blue-900/60 dark:from-blue-950/40 dark:via-zinc-900 dark:to-zinc-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            <flux:icon name="identification" class="size-6" />
                        </div>
                        <div class="space-y-1">
                            <div class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                Kredensial Praktik Medis Anda
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center gap-2">
                                    <span>STR: <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $myDoctorCredentials['str_number'] ?? '-' }}</span> (s/d {{ $myDoctorCredentials['str_valid_until'] ?? '-' }})</span>
                                    @if($myDoctorCredentials['is_str_expired'])
                                        <flux:badge size="xs" color="rose">STR Kedaluwarsa</flux:badge>
                                    @elseif($myDoctorCredentials['is_str_expiring_soon'])
                                        <flux:badge size="xs" color="amber">STR Segera Berakhir</flux:badge>
                                    @else
                                        <flux:badge size="xs" color="emerald">STR Aktif</flux:badge>
                                    @endif
                                </div>

                                @if(!empty($myDoctorCredentials['sip_number']))
                                    <div class="flex items-center gap-2">
                                        <span class="text-zinc-300 dark:text-zinc-700">•</span>
                                        <span>SIP: <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $myDoctorCredentials['sip_number'] }}</span> (s/d {{ $myDoctorCredentials['sip_valid_until'] ?? '-' }})</span>
                                        @if($myDoctorCredentials['is_sip_expired'])
                                            <flux:badge size="xs" color="rose">SIP Kedaluwarsa</flux:badge>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <a href="{{ route('documents.index') }}">
                            <flux:button size="sm" variant="outline" icon="document-arrow-up" class="hover:bg-blue-50 dark:hover:bg-blue-950/50">
                                Arsip Dokumen Lisensi
                            </flux:button>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- 4 Primary Metric Cards for Employee / Doctor --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Card 1: Shift Hari Ini --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal Tugas Hari Ini</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <flux:icon name="calendar" class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $myShiftToday['shift_name'] ?? 'Tidak Ada Jadwal / Libur' }}
                        </div>
                        @if($myShiftToday)
                            <div class="mt-1 flex items-center gap-1.5 text-xs font-mono font-medium text-blue-600 dark:text-blue-400">
                                <flux:icon name="clock" class="size-3.5" />
                                <span>{{ $myShiftToday['start_time'] }} - {{ $myShiftToday['end_time'] }} WIB</span>
                            </div>
                        @else
                            <span class="mt-1 block text-xs text-zinc-400">Jadwal Off / Istirahat</span>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span class="truncate">Besok: {{ $myShiftTomorrow['shift_name'] ?? 'Libur' }}</span>
                    <a href="{{ route('schedules.index') }}" class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">Roster &rarr;</a>
                </div>
            </div>

            {{-- Card 2: Presensi Hari Ini --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Presensi Hari Ini</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <flux:icon name="clock" class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        @if($myAttendanceToday && $myAttendanceToday['has_checked_in'])
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    Masuk: {{ $myAttendanceToday['clock_in'] }}
                                </span>
                                @if($myAttendanceToday['late_minutes'] > 0)
                                    <flux:badge size="xs" color="amber">+{{ $myAttendanceToday['late_minutes'] }}m</flux:badge>
                                @endif
                            </div>
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                Pulang: {{ $myAttendanceToday['clock_out'] ?? 'Belum Check-Out' }}
                            </span>
                        @else
                            <div class="flex items-center gap-2">
                                <span class="relative flex size-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex size-2.5 rounded-full bg-amber-500"></span>
                                </span>
                                <span class="text-lg font-bold text-amber-600 dark:text-amber-400">Belum Presensi</span>
                            </div>
                            <span class="mt-1 block text-xs text-zinc-400">Silakan lakukan clock-in</span>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span>Status: {{ ucfirst(str_replace('_', ' ', $myAttendanceToday['status'] ?? 'Belum Hadir')) }}</span>
                    <a href="{{ route('attendance.index') }}" class="font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Presensi &rarr;</a>
                </div>
            </div>

            {{-- Card 3: Saldo Cuti Tersisa --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sisa Saldo Cuti ({{ now()->year }})</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <flux:icon name="calendar-days" class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="font-mono text-2xl font-bold tabular-nums text-purple-600 dark:text-purple-400">
                                {{ $myLeaveQuota['remaining_days'] ?? 0 }}
                            </span>
                            <span class="text-xs font-semibold text-zinc-500">Hari Kerja</span>
                        </div>
                        {{-- Mini Progress Bar --}}
                        @php
                            $allocated = max((float) ($myLeaveQuota['allocated_days'] ?? 0), 1);
                            $used = (float) ($myLeaveQuota['used_days'] ?? 0);
                            $pctUsed = min(round(($used / $allocated) * 100), 100);
                        @endphp
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 transition-all duration-500" style="width: {{ $pctUsed }}%"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-[11px] text-zinc-500 dark:text-zinc-400">
                            <span>Terpakai: <strong class="text-zinc-700 dark:text-zinc-300">{{ $myLeaveQuota['used_days'] ?? 0 }}</strong> Hari</span>
                            <span>Total: {{ $myLeaveQuota['allocated_days'] ?? 0 }} Hari</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span class="text-zinc-400">Hak Cuti Tahunan</span>
                    <a href="{{ route('leaves.apply') }}" class="font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400">Ajukan &rarr;</a>
                </div>
            </div>

            {{-- Card 4: Slip Gaji Terakhir --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-yellow-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Gaji Bersih (Slip Terakhir)</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <flux:icon name="receipt-percent" class="size-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        @if($myLatestPayslip)
                            <div class="font-mono text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) $myLatestPayslip['net_salary'], 0, ',', '.') }}
                            </div>
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $myLatestPayslip['period_name'] }}</span>
                        @else
                            <div class="text-sm font-semibold text-zinc-500">Belum Ada Slip</div>
                            <span class="mt-1 block text-xs text-zinc-400">Periode aktif berjalan</span>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $myLatestPayslip ? 'Finalized' : '-' }}</span>
                    <a href="{{ route('payroll.my-payslips') }}" class="font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400">Slip Saya &rarr;</a>
                </div>
            </div>
        </div>

        {{-- Quick Access Self-Service Links --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Akses Cepat Layanan Mandiri</h3>
                <span class="text-xs text-zinc-400">Pintasan fitur harian staf</span>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a href="{{ route('attendance.index') }}" class="group flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-500/40">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400">
                        <flux:icon name="clock" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Presensi &amp; Geofence</div>
                        <div class="text-[11px] text-zinc-400">Clock-in / History</div>
                    </div>
                </a>

                <a href="{{ route('leaves.apply') }}" class="group flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-purple-500/40">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-600 group-hover:text-white dark:bg-purple-950/60 dark:text-purple-400">
                        <flux:icon name="document-text" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Permohonan Cuti</div>
                        <div class="text-[11px] text-zinc-400">Buat Pengajuan Baru</div>
                    </div>
                </a>

                <a href="{{ route('schedules.index') }}" class="group flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-blue-500/40">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/60 dark:text-blue-400">
                        <flux:icon name="calendar" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Shift & Roster</div>
                        <div class="text-[11px] text-zinc-400">Jadwal Bulanan</div>
                    </div>
                </a>

                <a href="{{ route('payroll.my-payslips') }}" class="group flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-amber-500/40">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-950/60 dark:text-amber-400">
                        <flux:icon name="receipt-percent" class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Slip Gaji Saya</div>
                        <div class="text-[11px] text-zinc-400">Unduh Bukti Transfer</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recent Leave Applications List --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pengajuan Cuti Terakhir Saya</h3>
                <a href="{{ route('leaves.index') }}" class="text-xs font-medium text-purple-600 hover:text-purple-700 hover:underline dark:text-purple-400">
                    Lihat Semua Riwayat &rarr;
                </a>
            </div>
            @if(!empty($myRecentLeaves))
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @foreach($myRecentLeaves as $leave)
                        <div class="flex flex-col justify-between rounded-2xl border border-zinc-200/80 bg-white p-4.5 shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="space-y-2">
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
                                <div class="flex items-center gap-1.5 text-xs font-mono text-zinc-500 dark:text-zinc-400">
                                    <flux:icon name="calendar" class="size-3.5" />
                                    <span>{{ $leave['start_date'] }} s/d {{ $leave['end_date'] }} ({{ $leave['total_days'] }} Hari)</span>
                                </div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2 italic" title="{{ $leave['reason'] }}">
                                    "{{ $leave['reason'] }}"
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-800">
                    <flux:icon name="document-text" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">Belum ada riwayat permohonan cuti baru-baru ini.</p>
                </div>
            @endif
        </div>

    {{-- ========================================================================= --}}
    {{-- 2. ROLE: SUPERVISOR (UNIT / WARD HEAD MONITORING DASHBOARD)               --}}
    {{-- ========================================================================= --}}
    @elseif($userRole === 'supervisor')
        {{-- Supervisor Hero Header --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-8 -top-8 size-48 rounded-full bg-gradient-to-br from-blue-500/10 via-emerald-500/5 to-transparent blur-2xl dark:from-blue-500/20"></div>
            <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white shadow-md shadow-blue-500/20">
                        <flux:icon name="building-office-2" class="size-7" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Dashboard Operasional Unit Kerja
                            </flux:heading>
                            @if($teamMetrics)
                                <flux:badge size="sm" color="blue">{{ $teamMetrics['unit_name'] }}</flux:badge>
                            @endif
                        </div>
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Kepala Unit / Supervisor: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>
                            @if($teamMetrics)
                                <span class="mx-1 text-zinc-300 dark:text-zinc-700">•</span>
                                <span>Unit: <strong class="text-emerald-600 dark:text-emerald-400">{{ $teamMetrics['unit_name'] }}</strong></span>
                            @endif
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3.5 py-2 text-xs font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <flux:icon name="calendar" class="size-4 text-blue-600 dark:text-blue-400" />
                        <span class="font-mono">{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="cursor-pointer">
                        <span wire:loading.remove wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4" />
                            <span>Segarkan</span>
                        </span>
                        <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-blue-600" />
                            <span>Memuat...</span>
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Supervisor Team Overview Cards --}}
        @if($teamMetrics)
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Monitoring Tim Unit ({{ $teamMetrics['unit_name'] }})</h3>
                    <span class="text-xs text-zinc-400">Status operasional shift aktif</span>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Total Staf Unit --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Staf Unit</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                    <flux:icon name="users" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $teamMetrics['total_team'] }}
                                </span>
                                <span class="text-xs font-medium text-zinc-400">Orang</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <span>Anggota aktif di instalasi</span>
                        </div>
                    </div>

                    {{-- Staf Hadir Hari Ini --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Staf Hadir Hari Ini</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <flux:icon name="check-circle" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                                    {{ $teamMetrics['present_today'] }}
                                </span>
                                <span class="text-xs font-medium text-emerald-600/80 dark:text-emerald-400/80">Hadir</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            @php
                                $rate = $teamMetrics['total_team'] > 0 ? round(($teamMetrics['present_today'] / $teamMetrics['total_team']) * 100, 1) : 0;
                            @endphp
                            <span>Tingkat kehadiran: <strong class="text-emerald-600">{{ $rate }}%</strong></span>
                        </div>
                    </div>

                    {{-- Antrean Cuti Tier 1 --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Antrean Cuti Tier 1</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                    <flux:icon name="document-magnifying-glass" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-bold tabular-nums text-amber-600 dark:text-amber-400">
                                    {{ $teamMetrics['pending_approvals'] }}
                                </span>
                                <span class="text-xs font-medium text-amber-600/80 dark:text-amber-400/80">Menunggu</span>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 text-[11px] dark:border-zinc-800">
                            <span class="text-zinc-400">Perlu persetujuan Anda</span>
                            <a href="{{ route('leaves.index') }}" class="font-bold text-amber-600 hover:underline dark:text-amber-400">Verifikasi Sekarang &rarr;</a>
                        </div>
                    </div>

                    {{-- Staf Cuti / Izin Hari Ini --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Staf Cuti / Izin Hari Ini</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                    <flux:icon name="calendar-days" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-bold tabular-nums text-purple-600 dark:text-purple-400">
                                    {{ $teamMetrics['on_leave_today'] }}
                                </span>
                                <span class="text-xs font-medium text-purple-600/80 dark:text-purple-400/80">Orang</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <span>Status cuti disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-800">
                <flux:icon name="information-circle" class="mx-auto size-8 text-zinc-400" />
                <div class="mt-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Unit Kerja Belum Ditetapkan</div>
                <div class="text-xs text-zinc-400 mt-1">Akun Anda belum ditugaskan ke Unit Kerja tertentu. Hubungi HRD untuk menetapkan unit kerja supervisor.</div>
            </div>
        @endif

        {{-- Personal Self-Service Widget for Supervisor --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal & Presensi Pribadi Anda</h3>
                <span class="text-xs text-zinc-400">Informasi kehadiran supervisor</span>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal Hari Ini</span>
                        <flux:icon name="calendar" class="size-4 text-blue-500" />
                    </div>
                    <div class="mt-2 text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $myShiftToday['shift_name'] ?? 'Jadwal Normal' }}</div>
                    @if($myShiftToday)
                        <div class="mt-1 text-xs font-mono text-zinc-400">{{ $myShiftToday['start_time'] }} - {{ $myShiftToday['end_time'] }} WIB</div>
                    @endif
                </div>

                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Presensi Pribadi</span>
                        <flux:icon name="clock" class="size-4 text-emerald-500" />
                    </div>
                    <div class="mt-2 text-base font-bold text-emerald-600 dark:text-emerald-400">
                        {{ !empty($myAttendanceToday['clock_in']) ? 'Hadir: '.$myAttendanceToday['clock_in'] : 'Belum Check-In' }}
                    </div>
                    <div class="mt-1">
                        <a href="{{ route('attendance.index') }}" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">Buka Presensi &rarr;</a>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sisa Cuti Tahunan</span>
                        <flux:icon name="calendar-days" class="size-4 text-purple-500" />
                    </div>
                    <div class="mt-2 font-mono text-base font-bold text-purple-600 dark:text-purple-400">
                        {{ $myLeaveQuota['remaining_days'] ?? 0 }} Hari
                    </div>
                    <div class="mt-1">
                        <a href="{{ route('leaves.index') }}" class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400">Kelola Cuti &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 3. ROLE: PAYROLL & FINANCE (COMPENSATION & DISBURSEMENT DASHBOARD)         --}}
    {{-- ========================================================================= --}}
    @elseif($userRole === 'payroll_finance')
        {{-- Payroll Header --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-8 -top-8 size-48 rounded-full bg-gradient-to-br from-emerald-500/10 via-amber-500/5 to-transparent blur-2xl dark:from-emerald-500/20"></div>
            <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-500/20">
                        <flux:icon name="banknotes" class="size-7" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Dashboard Penggajian & Kompensasi
                            </flux:heading>
                            <flux:badge size="sm" color="emerald">Finance & Payroll</flux:badge>
                        </div>
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Selamat datang, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>. Monitor eksekusi siklus payroll dan pencairan dana.
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3.5 py-2 text-xs font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-mono">{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="cursor-pointer">
                        <span wire:loading.remove wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4" />
                            <span>Segarkan</span>
                        </span>
                        <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-emerald-600" />
                            <span>Memuat...</span>
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>

        @if($payroll)
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rekapitulasi Penggajian Terakhir</h3>
                    <flux:badge size="sm" color="emerald" class="capitalize">Status: {{ $payroll['status'] ?? 'Finalized' }}</flux:badge>
                </div>

                {{-- 4 Primary Payroll Cards --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Total Bruto --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Penghasilan Kotor (Bruto)</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                    <flux:icon name="currency-dollar" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 font-mono text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                            <span>Akumulasi gaji pokok & tunjangan</span>
                        </div>
                    </div>

                    {{-- Total Potongan --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-rose-500 to-red-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Potongan & Pajak</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                                    <flux:icon name="receipt-refund" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 font-mono text-xl font-bold tracking-tight text-rose-600 dark:text-rose-400">
                                - Rp {{ number_format((float) ($payroll['total_deductions'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                            <span>PPh 21, BPJS, & potongan kas</span>
                        </div>
                    </div>

                    {{-- Total Slip Gaji --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Slip Gaji Karyawan</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                    <flux:icon name="document-text" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $payroll['total_payslips'] ?? 0 }}
                                </span>
                                <span class="text-xs font-medium text-zinc-400">Slip</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                            <span>Karyawan masuk kalkulasi</span>
                        </div>
                    </div>

                    {{-- Dana Bersih Disalurkan (Netto) --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-emerald-300 bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/50 p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-800 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Dana Bersih (Netto) Disalurkan</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-600/30">
                                    <flux:icon name="banknotes" class="size-4" />
                                </div>
                            </div>
                            <div class="mt-3 font-mono text-xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-4 border-t border-emerald-100 pt-3 text-[11px] text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300">
                            <span>Total take-home pay via bank</span>
                        </div>
                    </div>
                </div>

                {{-- Statutory Breakdown (BPJS Kesehatan & Ketenagakerjaan) --}}
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex size-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400">
                                    <flux:icon name="heart" class="size-4" />
                                </div>
                                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">BPJS Kesehatan (Total Beban)</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) ($payroll['total_bpjs_kesehatan'] ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                    <flux:icon name="shield-check" class="size-4" />
                                </div>
                                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">BPJS Ketenagakerjaan (Total Beban)</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) ($payroll['total_bpjs_ketenagakerjaan'] ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
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
                <a href="{{ route('reports.pph21') }}">
                    <flux:button variant="subtle" icon="calculator">
                        Laporan PPh 21
                    </flux:button>
                </a>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-800">
                <flux:icon name="banknotes" class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                <div class="mt-3 text-sm font-bold text-zinc-700 dark:text-zinc-300">Belum Ada Siklus Penggajian</div>
                <div class="mt-1 text-xs text-zinc-400">Data ringkasan penggajian dan pembayaran akan muncul setelah ada payroll yang dihitung.</div>
                <div class="mt-4">
                    <a href="{{ route('payroll.index') }}">
                        <flux:button variant="primary" icon="banknotes" size="sm">
                            Buka Modul Payroll
                        </flux:button>
                    </a>
                </div>
            </div>
        @endif

    {{-- ========================================================================= --}}
    {{-- 4. ROLE: MANAGEMENT (SUPER ADMIN, HR ADMIN, HR MANAGER)                  --}}
    {{-- ========================================================================= --}}
    @else
        {{-- Management Hero Header --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-8 -top-8 size-48 rounded-full bg-gradient-to-br from-emerald-500/10 via-blue-500/5 to-transparent blur-2xl dark:from-emerald-500/20"></div>
            <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-500/20">
                        <flux:icon name="building-office-2" class="size-7" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Dashboard Operasional & SDM
                            </flux:heading>
                            <flux:badge size="sm" color="emerald">RS Juwita Executive</flux:badge>
                        </div>
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->greeting }}, <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>. Pantau indikator kinerja rumah sakit terkini.
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3.5 py-2 text-xs font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-mono">{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="cursor-pointer">
                        <span wire:loading.remove wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4" />
                            <span>Segarkan</span>
                        </span>
                        <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-emerald-600" />
                            <span>Memuat...</span>
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Doctor Credential Compliance Warning Alert --}}
        @if(!empty($doctorAlerts))
            <div class="overflow-hidden rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-xs dark:border-amber-800 dark:bg-amber-950/40">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-amber-900 dark:text-amber-200">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-200 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                            <flux:icon name="exclamation-triangle" class="size-5" />
                        </div>
                        <div>
                            <div class="text-sm font-bold">
                                Peringatan Kepatuhan Izin Praktik Medis ({{ count($doctorAlerts) }} Dokter Perlu Perhatian)
                            </div>
                            <div class="text-xs text-amber-700 dark:text-amber-300/80">
                                STR atau SIP dokter berikut akan segera kedaluwarsa dalam kurun waktu 60 hari ke depan.
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('doctors.index') }}" class="hidden sm:inline-block">
                        <flux:button size="xs" variant="outline" class="border-amber-300 text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-200">
                            Kelola Dokter &rarr;
                        </flux:button>
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach(array_slice($doctorAlerts, 0, 6) as $doc)
                        <div class="flex flex-col justify-between rounded-xl border border-amber-200/80 bg-white/90 p-3.5 shadow-2xs dark:border-amber-900/60 dark:bg-zinc-900/90">
                            <div class="flex items-start gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                    {{ strtoupper(substr($doc['name'] ?? 'Dr', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $doc['name'] }}</div>
                                    <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">{{ $doc['specialization'] ?? 'Dokter Umum' }}</div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t border-amber-100 pt-2 text-[11px] dark:border-zinc-800">
                                <span class="font-mono text-zinc-600 dark:text-zinc-400">STR: {{ $doc['str_valid_until'] }}</span>
                                <flux:badge size="xs" color="amber">Segera Berakhir</flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Staffing Distribution Metrics --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Distribusi Ketenagaan Rumah Sakit</h3>
                <a href="{{ route('employees.index') }}" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">
                    Direktori Pegawai &rarr;
                </a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Total Karyawan Aktif --}}
                <div class="group relative flex items-center gap-4 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-blue-500 to-indigo-500"></div>
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shadow-xs dark:bg-blue-950/60 dark:text-blue-400">
                        <flux:icon name="users" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Karyawan Aktif</div>
                        <div class="mt-1 font-mono text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ number_format($staffing['total_active_employees'] ?? 0) }}
                        </div>
                        <div class="text-[11px] text-zinc-400">Pegawai organik operasional</div>
                    </div>
                </div>

                {{-- Dokter & Spesialis Aktif --}}
                <div class="group relative flex items-center gap-4 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-emerald-500 to-teal-500"></div>
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-xs dark:bg-emerald-950/60 dark:text-emerald-400">
                        <flux:icon name="user-group" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Dokter & Spesialis Aktif</div>
                        <div class="mt-1 font-mono text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                            {{ number_format($staffing['total_active_doctors'] ?? 0) }}
                        </div>
                        <div class="text-[11px] text-zinc-400">Dokter berizin praktik aktif</div>
                    </div>
                </div>

                {{-- Total Tenaga Kerja Keseluruhan --}}
                <div class="group relative flex items-center gap-4 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-purple-500 to-pink-500"></div>
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 shadow-xs dark:bg-purple-950/60 dark:text-purple-400">
                        <flux:icon name="building-office-2" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Tenaga Kerja Keseluruhan</div>
                        <div class="mt-1 font-mono text-3xl font-bold tracking-tight text-purple-600 dark:text-purple-400">
                            {{ number_format($staffing['total_workforce'] ?? 0) }}
                        </div>
                        <div class="text-[11px] text-zinc-400">Karyawan + Tenaga Dokter</div>
                    </div>
                </div>
            </div>

            {{-- Contract Breakdown Pills --}}
            @if(!empty($staffing['contract_breakdown']))
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Status Ikatan Kerja:</span>
                    @foreach($staffing['contract_breakdown'] as $contractType => $count)
                        <div class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $contractType }}</span>
                            <span class="rounded bg-zinc-100 px-1.5 py-0.2 font-mono text-[11px] font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Today's Attendance Overview --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Rekapitulasi Kehadiran Hari Ini ({{ $attendance['date'] ?? now()->toDateString() }})
                    </h3>
                    @php
                        $sched = max((int) ($attendance['total_scheduled'] ?? 0), 1);
                        $checked = (int) ($attendance['total_checked_in'] ?? 0);
                        $attRate = round(($checked / $sched) * 100, 1);
                    @endphp
                    <flux:badge size="xs" :color="$attRate >= 80 ? 'emerald' : 'amber'">
                        {{ $attRate }}% Kehadiran
                    </flux:badge>
                </div>
                <a href="{{ route('attendance.index') }}" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">
                    Live Presensi &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Jadwal Terdaftar --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal Terdaftar</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                <flux:icon name="calendar" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ $attendance['total_scheduled'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-zinc-400">Staf</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                        <span>Roster shift terjadwal</span>
                    </div>
                </div>

                {{-- Hadir Tepat Waktu --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hadir Tepat Waktu</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <flux:icon name="check-circle" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                                {{ $attendance['present_count'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-emerald-600/80 dark:text-emerald-400/80">Staf</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                        <span>Check-in sesuai toleransi</span>
                    </div>
                </div>

                {{-- Hadir Terlambat --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hadir Terlambat</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                <flux:icon name="exclamation-circle" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-bold tabular-nums text-amber-600 dark:text-amber-400">
                                {{ $attendance['late_count'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-amber-600/80 dark:text-amber-400/80">Staf</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                        <span>Total: {{ $attendance['total_late_minutes'] ?? 0 }} menit keterlambatan</span>
                    </div>
                </div>

                {{-- Total Check-In --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Check-In</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                <flux:icon name="finger-print" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-bold tabular-nums text-purple-600 dark:text-purple-400">
                                {{ $attendance['total_checked_in'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-purple-600/80 dark:text-purple-400/80">Staf</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                        <span>Tepat waktu + terlambat</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payroll Summary Card --}}
        @if($payroll)
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ringkasan Penggajian Terakhir</h3>
                    <a href="{{ route('payroll.index') }}" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">
                        Modul Payroll &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Gaji Kotor (Bruto)</div>
                        <div class="mt-2 font-mono text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="mt-2 text-[11px] text-zinc-400">Total bruto periode berjalan</div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Potongan & Pajak</div>
                        <div class="mt-2 font-mono text-lg font-bold tracking-tight text-rose-600 dark:text-rose-400">
                            - Rp {{ number_format((float) ($payroll['total_deductions'] ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="mt-2 text-[11px] text-zinc-400">BPJS, PPh 21, & potongan</div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Slip Gaji</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="font-mono text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ $payroll['total_payslips'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-zinc-400">Slip</span>
                        </div>
                        <div class="mt-2 text-[11px] text-zinc-400">Karyawan terproses</div>
                    </div>

                    <div class="rounded-2xl border border-emerald-300 bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/50 p-5 shadow-xs dark:border-emerald-800 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-zinc-900">
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Take Home Pay (Bersih)</div>
                        <div class="mt-2 font-mono text-xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="mt-2 text-[11px] text-emerald-700 dark:text-emerald-300">Total netto siap salur</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Quick Operational Shortcuts --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Navigasi Operasional Cepat</h3>
                <span class="text-xs text-zinc-400">Pusat kendali modul utama</span>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <a href="{{ route('employees.index') }}" class="group flex flex-col items-center rounded-2xl border border-zinc-200/80 bg-white p-4 text-center shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/60 dark:text-blue-400">
                        <flux:icon name="users" class="size-5" />
                    </div>
                    <span class="mt-2.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">Karyawan</span>
                    <span class="text-[10px] text-zinc-400">Direktori SDM</span>
                </a>

                <a href="{{ route('doctors.index') }}" class="group flex flex-col items-center rounded-2xl border border-zinc-200/80 bg-white p-4 text-center shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400">
                        <flux:icon name="user-group" class="size-5" />
                    </div>
                    <span class="mt-2.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">Dokter</span>
                    <span class="text-[10px] text-zinc-400">STR & SIP</span>
                </a>

                <a href="{{ route('schedules.index') }}" class="group flex flex-col items-center rounded-2xl border border-zinc-200/80 bg-white p-4 text-center shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-950/60 dark:text-indigo-400">
                        <flux:icon name="calendar" class="size-5" />
                    </div>
                    <span class="mt-2.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">Jadwal Shift</span>
                    <span class="text-[10px] text-zinc-400">Roster Bulanan</span>
                </a>

                <a href="{{ route('attendance.index') }}" class="group flex flex-col items-center rounded-2xl border border-zinc-200/80 bg-white p-4 text-center shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-teal-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-teal-50 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white dark:bg-teal-950/60 dark:text-teal-400">
                        <flux:icon name="clock" class="size-5" />
                    </div>
                    <span class="mt-2.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">Presensi</span>
                    <span class="text-[10px] text-zinc-400">Monitor Log</span>
                </a>

                <a href="{{ route('leaves.index') }}" class="group flex flex-col items-center rounded-2xl border border-zinc-200/80 bg-white p-4 text-center shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-600 group-hover:text-white dark:bg-purple-950/60 dark:text-purple-400">
                        <flux:icon name="document-text" class="size-5" />
                    </div>
                    <span class="mt-2.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">Cuti & Izin</span>
                    <span class="text-[10px] text-zinc-400">
                        @if($pendingLeavesCount > 0)
                            <strong class="text-amber-600">{{ $pendingLeavesCount }} Menunggu</strong>
                        @else
                            Approval Cuti
                        @endif
                    </span>
                </a>

                <a href="{{ route('payroll.index') }}" class="group flex flex-col items-center rounded-2xl border border-zinc-200/80 bg-white p-4 text-center shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-950/60 dark:text-amber-400">
                        <flux:icon name="banknotes" class="size-5" />
                    </div>
                    <span class="mt-2.5 text-xs font-bold text-zinc-900 dark:text-zinc-100">Payroll</span>
                    <span class="text-[10px] text-zinc-400">Proses & Slip</span>
                </a>
            </div>
        </div>
    @endif
</div>

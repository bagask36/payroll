<div class="space-y-8">
    {{-- ========================================================================= --}}
    {{-- 1. ROLE: EMPLOYEE & DOCTOR (SELF-SERVICE PORTAL DASHBOARD)                --}}
    {{-- ========================================================================= --}}
    @if($userRole === 'employee' || $userRole === 'doctor')
        {{-- Hero Header Section --}}
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-7 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-12 -top-12 size-64 rounded-full bg-gradient-to-br from-emerald-500/15 via-teal-500/10 to-transparent blur-3xl dark:from-emerald-500/25"></div>
            <div class="absolute -left-12 -bottom-12 size-56 rounded-full bg-gradient-to-tr from-blue-500/10 via-indigo-500/5 to-transparent blur-2xl dark:from-blue-500/15"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4 sm:gap-5">
                    <div class="relative flex size-15 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-lg shadow-emerald-600/25">
                        <flux:icon :name="$userRole === 'doctor' ? 'user-group' : 'identification'" class="size-8" />
                        <span class="absolute -bottom-1 -right-1 flex size-4 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900">
                            <span class="size-2 rounded-full bg-white animate-pulse"></span>
                        </span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <flux:heading size="xl" class="font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 text-xl sm:text-2xl">
                                {{ $userRole === 'doctor' ? 'Portal Tenaga Medis & Dokter' : 'Portal Layanan Mandiri Pegawai' }}
                            </flux:heading>
                            <flux:badge size="sm" :color="$userRole === 'doctor' ? 'blue' : 'emerald'" class="shadow-2xs font-semibold">
                                {{ $userRole === 'doctor' ? 'Tenaga Medis' : 'Staf Karyawan' }}
                            </flux:badge>
                        </div>
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->greeting }}, <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>
                            @if(auth()->user()?->employee)
                                <span class="mx-1 text-zinc-300 dark:text-zinc-700">•</span>
                                <span>{{ auth()->user()->employee->position?->title ?? 'Staf' }}</span>
                                <span class="text-zinc-400 dark:text-zinc-500 font-medium">({{ auth()->user()->employee->unit?->name ?? 'RS Permata Bunda' }})</span>
                            @elseif($myDoctorCredentials)
                                <span class="mx-1 text-zinc-300 dark:text-zinc-700">•</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $myDoctorCredentials['specialization'] }}</span>
                            @endif
                        </flux:subheading>
                    </div>
                </div>

                {{-- Header Actions & Real-time Digital Clock --}}
                <div class="flex flex-wrap items-center gap-3">
                    <div 
                        x-data="{ 
                            time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) 
                        }" 
                        x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                        class="flex items-center gap-2.5 rounded-2xl border border-zinc-200/90 bg-zinc-50/90 px-4 py-2 text-xs text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/70 dark:text-zinc-300 shadow-2xs"
                    >
                        <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-medium">{{ now()->translatedFormat('l, d F Y') }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">•</span>
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 tabular-nums" x-text="time"></span>
                        <span class="text-[10px] uppercase font-semibold text-zinc-400">WIB</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="rounded-xl! cursor-pointer border border-zinc-200/80 dark:border-zinc-800">
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

            {{-- Quick Action Deck for Staff --}}
            <div class="mt-6 pt-5 border-t border-zinc-100 dark:border-zinc-800">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="bolt" class="size-3.5 text-emerald-500" />
                        <span>Pintasan Layanan Mandiri</span>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                        </span>
                        <span>Portal Mandiri Aktif</span>
                    </div>
                </div>

                {{-- Responsive Grid: 2 columns on mobile, 4 columns on sm+ --}}
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3">
                    {{-- 1. Presensi Mandiri --}}
                    <a href="{{ route('attendance.index') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-emerald-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                            <flux:icon name="clock" class="size-4.5 sm:size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-emerald-700 dark:text-zinc-100 dark:group-hover:text-emerald-400 transition-colors">
                                    Presensi
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                Check-in / out
                            </p>
                        </div>
                    </a>

                    {{-- 2. Ajukan Cuti / Izin --}}
                    <a href="{{ route('leaves.apply') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-purple-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white dark:bg-purple-950/60 dark:text-purple-400 shadow-2xs">
                            <flux:icon name="document-text" class="size-4.5 sm:size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-purple-700 dark:text-zinc-100 dark:group-hover:text-purple-400 transition-colors">
                                    Ajukan Cuti
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                Permohonan izin
                            </p>
                        </div>
                    </a>

                    {{-- 3. Jadwal & Roster --}}
                    <a href="{{ route('schedules.index') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-blue-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                            <flux:icon name="calendar" class="size-4.5 sm:size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-blue-700 dark:text-zinc-100 dark:group-hover:text-blue-400 transition-colors">
                                    Jadwal Shift
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                Roster penugasan
                            </p>
                        </div>
                    </a>

                    {{-- 4. Slip Gaji / Dokumen --}}
                    @if(auth()->user()?->employee)
                        <a href="{{ route('payroll.my-payslips') }}" 
                            class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-amber-500/40 dark:hover:bg-zinc-800 active:scale-98">
                            <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-950/60 dark:text-amber-400 shadow-2xs">
                                <flux:icon name="receipt-percent" class="size-4.5 sm:size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-amber-700 dark:text-zinc-100 dark:group-hover:text-amber-400 transition-colors">
                                        Slip Gaji
                                    </span>
                                    <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                                </div>
                                <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                    Unduh rincian gaji
                                </p>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('documents.index') }}" 
                            class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-teal-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-teal-500/40 dark:hover:bg-zinc-800 active:scale-98">
                            <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white dark:bg-teal-950/60 dark:text-teal-400 shadow-2xs">
                                <flux:icon name="folder" class="size-4.5 sm:size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-teal-700 dark:text-zinc-100 dark:group-hover:text-teal-400 transition-colors">
                                        Dokumen
                                    </span>
                                    <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                                </div>
                                <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                    Arsip berkas STR/SIP
                                </p>
                            </div>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Doctor License Credential Card (If Doctor) --}}
        @if($myDoctorCredentials)
            <div class="overflow-hidden rounded-3xl border border-blue-200/80 bg-gradient-to-r from-blue-50/90 via-indigo-50/50 to-white p-5 sm:p-6 shadow-xs dark:border-blue-900/60 dark:from-blue-950/40 dark:via-zinc-900 dark:to-zinc-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 shadow-xs">
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
                            <flux:button size="sm" variant="outline" icon="document-arrow-up" class="hover:bg-blue-50 dark:hover:bg-blue-950/50 rounded-xl!">
                                Arsip Dokumen Lisensi
                            </flux:button>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- 3 Primary Metric Cards for Employee / Doctor (Personal Cockpit) --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- Card 1: Shift Hari Ini --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal Tugas Hari Ini</span>
                        <div class="flex size-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                            <flux:icon name="calendar" class="size-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ $myShiftToday['shift_name'] ?? 'Tidak Ada Jadwal / Libur' }}
                        </div>
                        @if($myShiftToday)
                            <div class="mt-2 flex items-center gap-2 text-xs font-mono font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2.5 py-1 rounded-lg w-fit">
                                <flux:icon name="clock" class="size-3.5" />
                                <span>{{ $myShiftToday['start_time'] }} - {{ $myShiftToday['end_time'] }} WIB</span>
                            </div>
                        @else
                            <span class="mt-1 block text-xs text-zinc-400 font-medium">Jadwal Off / Istirahat</span>
                        @endif
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-3.5 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span class="truncate">Besok: <strong>{{ $myShiftTomorrow['shift_name'] ?? 'Libur' }}</strong></span>
                    <a href="{{ route('schedules.index') }}" class="font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400">Roster &rarr;</a>
                </div>
            </div>

            {{-- Card 2: Presensi Hari Ini --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Presensi Hari Ini</span>
                        <div class="flex size-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                            <flux:icon name="clock" class="size-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        @if($myAttendanceToday && $myAttendanceToday['has_checked_in'])
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                                    Masuk: {{ $myAttendanceToday['clock_in'] }}
                                </span>
                                @if($myAttendanceToday['late_minutes'] > 0)
                                    <flux:badge size="xs" color="amber">+{{ $myAttendanceToday['late_minutes'] }}m</flux:badge>
                                @endif
                            </div>
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                Pulang: {{ $myAttendanceToday['clock_out'] ?? 'Belum Check-Out' }}
                            </span>
                        @else
                            <div class="flex items-center gap-2">
                                <span class="relative flex size-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex size-2.5 rounded-full bg-amber-500"></span>
                                </span>
                                <span class="text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400">Belum Presensi</span>
                            </div>
                            <span class="mt-1 block text-xs text-zinc-400 font-medium">Silakan lakukan clock-in di radius geofence</span>
                        @endif
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-3.5 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span>Status: <strong>{{ ucfirst(str_replace('_', ' ', $myAttendanceToday['status'] ?? 'Belum Hadir')) }}</strong></span>
                    <a href="{{ route('attendance.index') }}" class="font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Presensi &rarr;</a>
                </div>
            </div>

            {{-- Card 3: Saldo Cuti Tersisa --}}
            <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sisa Saldo Cuti ({{ now()->year }})</span>
                        <div class="flex size-10 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 shadow-2xs">
                            <flux:icon name="calendar-days" class="size-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-extrabold tabular-nums text-purple-600 dark:text-purple-400">
                                {{ $myLeaveQuota['remaining_days'] ?? 0 }}
                            </span>
                            <span class="text-xs font-bold text-zinc-500">Hari Kerja Tersisa</span>
                        </div>
                        {{-- Mini Progress Bar --}}
                        @php
                            $allocated = max((float) ($myLeaveQuota['allocated_days'] ?? 0), 1);
                            $used = (float) ($myLeaveQuota['used_days'] ?? 0);
                            $pctUsed = min(round(($used / $allocated) * 100), 100);
                        @endphp
                        <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 transition-all duration-500" style="width: {{ $pctUsed }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">
                            <span>Terpakai: <strong class="text-zinc-700 dark:text-zinc-300">{{ $myLeaveQuota['used_days'] ?? 0 }}</strong> Hari</span>
                            <span>Total Kuota: {{ $myLeaveQuota['allocated_days'] ?? 0 }} Hari</span>
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-3.5 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <span class="text-zinc-400">Hak Cuti Tahunan</span>
                    <a href="{{ route('leaves.apply') }}" class="font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400">Ajukan Cuti &rarr;</a>
                </div>
            </div>
        </div>

        {{-- Quick Access Self-Service Links --}}
        <div>
            <div class="mb-3.5 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Akses Cepat Layanan Mandiri</h3>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Pintasan fitur harian staf rumah sakit</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                <a href="{{ route('attendance.index') }}" class="group flex items-center gap-3.5 rounded-3xl border border-zinc-200/80 bg-white p-4.5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-emerald-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 transition-all group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                        <flux:icon name="clock" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Presensi & Geofence</div>
                        <div class="text-[11px] text-zinc-400 font-medium">Clock-in / History</div>
                    </div>
                </a>

                <a href="{{ route('leaves.apply') }}" class="group flex items-center gap-3.5 rounded-3xl border border-zinc-200/80 bg-white p-4.5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-purple-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 transition-all group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white dark:bg-purple-950/60 dark:text-purple-400 shadow-2xs">
                        <flux:icon name="document-text" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Permohonan Cuti</div>
                        <div class="text-[11px] text-zinc-400 font-medium">Buat Pengajuan Baru</div>
                    </div>
                </a>

                <a href="{{ route('schedules.index') }}" class="group flex items-center gap-3.5 rounded-3xl border border-zinc-200/80 bg-white p-4.5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-blue-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 transition-all group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                        <flux:icon name="calendar" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Shift & Roster</div>
                        <div class="text-[11px] text-zinc-400 font-medium">Jadwal Bulanan</div>
                    </div>
                </a>

                <a href="{{ route('payroll.my-payslips') }}" class="group flex items-center gap-3.5 rounded-3xl border border-zinc-200/80 bg-white p-4.5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-amber-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 transition-all group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-950/60 dark:text-amber-400 shadow-2xs">
                        <flux:icon name="receipt-percent" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">Slip Gaji Saya</div>
                        <div class="text-[11px] text-zinc-400 font-medium">Unduh Bukti Transfer</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recent Leave Applications List --}}
        <div>
            <div class="mb-3.5 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pengajuan Cuti Terakhir Saya</h3>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Status dan riwayat permohonan terkini</p>
                </div>
                <a href="{{ route('leaves.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-700 hover:underline dark:text-purple-400">
                    <span>Lihat Semua Riwayat</span>
                    <span>&rarr;</span>
                </a>
            </div>
            @if(!empty($myRecentLeaves))
                <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
                    @foreach($myRecentLeaves as $leave)
                        <div class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $leave['type'] }}</span>
                                    @php
                                        $stBadge = match ($leave['status']) {
                                            'approved' => ['color' => 'emerald', 'label' => 'Disetujui'],
                                            'rejected' => ['color' => 'rose', 'label' => 'Ditolak'],
                                            'cancelled' => ['color' => 'zinc', 'label' => 'Dibatalkan'],
                                            default => ['color' => 'amber', 'label' => 'Menunggu Verifikasi'],
                                        };
                                    @endphp
                                    <flux:badge size="xs" :color="$stBadge['color']" class="font-bold">{{ $stBadge['label'] }}</flux:badge>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs font-mono text-zinc-500 dark:text-zinc-400 font-medium">
                                    <flux:icon name="calendar" class="size-3.5 text-zinc-400" />
                                    <span>{{ $leave['start_date'] }} s/d {{ $leave['end_date'] }} ({{ $leave['total_days'] }} Hari)</span>
                                </div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2 italic bg-zinc-50 dark:bg-zinc-800/50 p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800" title="{{ $leave['reason'] }}">
                                    "{{ $leave['reason'] }}"
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-800">
                    <flux:icon name="document-text" class="mx-auto size-9 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">Belum ada riwayat permohonan cuti baru-baru ini.</p>
                </div>
            @endif
        </div>

    {{-- ========================================================================= --}}
    {{-- 2. ROLE: SUPERVISOR (UNIT / WARD HEAD MONITORING DASHBOARD)               --}}
    {{-- ========================================================================= --}}
    @elseif($userRole === 'supervisor')
        {{-- Supervisor Hero Header --}}
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-7 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-12 -top-12 size-64 rounded-full bg-gradient-to-br from-blue-500/15 via-indigo-500/10 to-transparent blur-3xl dark:from-blue-500/25"></div>
            <div class="absolute -left-12 -bottom-12 size-56 rounded-full bg-gradient-to-tr from-emerald-500/10 via-teal-500/5 to-transparent blur-2xl dark:from-emerald-500/15"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4 sm:gap-5">
                    <div class="relative flex size-15 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-600/25">
                        <flux:icon name="building-office-2" class="size-8" />
                        <span class="absolute -bottom-1 -right-1 flex size-4 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900">
                            <span class="size-2 rounded-full bg-white animate-pulse"></span>
                        </span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <flux:heading size="xl" class="font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-2xl">
                                Dashboard Operasional Unit Kerja
                            </flux:heading>
                            @if($teamMetrics)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200/60 dark:border-blue-900/40">
                                    <span class="size-1.5 rounded-full bg-blue-500"></span>
                                    {{ $teamMetrics['unit_name'] }}
                                </span>
                            @endif
                        </div>
                        <flux:subheading class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400 sm:text-sm font-medium">
                            Kepala Unit / Supervisor: <strong class="text-zinc-800 dark:text-zinc-200 font-bold">{{ auth()->user()?->name }}</strong>
                            @if($teamMetrics)
                                <span class="mx-1 text-zinc-300 dark:text-zinc-700">•</span>
                                <span>Unit Penugasan: <strong class="text-blue-600 dark:text-blue-400">{{ $teamMetrics['unit_name'] }}</strong></span>
                            @endif
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-2.5 rounded-2xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-2.5 text-xs font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-850 dark:text-zinc-300 shadow-2xs">
                        <flux:icon name="calendar" class="size-4 text-blue-600 dark:text-blue-400" />
                        <span class="font-mono font-semibold">{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="cursor-pointer rounded-2xl shadow-2xs">
                        <span wire:loading.remove wire:target="refreshData" class="flex items-center gap-1.5 font-semibold">
                            <flux:icon name="arrow-path" class="size-4" />
                            <span>Segarkan</span>
                        </span>
                        <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5 font-semibold">
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
                <div class="mb-3.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Monitoring Tim Unit ({{ $teamMetrics['unit_name'] }})</h3>
                        <p class="text-[11px] text-zinc-400 mt-0.5">Status operasional shift dan pengajuan staf aktif</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Shift Aktif
                    </span>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Total Staf Unit --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Staf Unit</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                                    <flux:icon name="users" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $teamMetrics['total_team'] }}
                                </span>
                                <span class="text-xs font-bold text-zinc-400">Orang</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <span>Anggota aktif di instalasi</span>
                        </div>
                    </div>

                    {{-- Staf Hadir Hari Ini --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Staf Hadir Hari Ini</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                                    <flux:icon name="check-circle" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-emerald-600 dark:text-emerald-400">
                                    {{ $teamMetrics['present_today'] }}
                                </span>
                                <span class="text-xs font-bold text-emerald-600/80 dark:text-emerald-400/80">Hadir</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            @php
                                $rate = $teamMetrics['total_team'] > 0 ? round(($teamMetrics['present_today'] / $teamMetrics['total_team']) * 100, 1) : 0;
                            @endphp
                            <span>Tingkat kehadiran: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $rate }}%</strong></span>
                        </div>
                    </div>

                    {{-- Antrean Cuti Tier 1 --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Antrean Cuti Tier 1</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 shadow-2xs">
                                    <flux:icon name="document-magnifying-glass" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-amber-600 dark:text-amber-400">
                                    {{ $teamMetrics['pending_approvals'] }}
                                </span>
                                <span class="text-xs font-bold text-amber-600/80 dark:text-amber-400/80">Menunggu</span>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 text-xs dark:border-zinc-800">
                            <span class="text-zinc-400">Persetujuan Anda</span>
                            <a href="{{ route('leaves.index') }}" class="font-bold text-amber-600 hover:text-amber-700 dark:text-amber-400">Verifikasi &rarr;</a>
                        </div>
                    </div>

                    {{-- Staf Cuti / Izin Hari Ini --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Staf Cuti / Izin Hari Ini</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 shadow-2xs">
                                    <flux:icon name="calendar-days" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-purple-600 dark:text-purple-400">
                                    {{ $teamMetrics['on_leave_today'] }}
                                </span>
                                <span class="text-xs font-bold text-purple-600/80 dark:text-purple-400/80">Orang</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <span>Status cuti disetujui</span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-800">
                <flux:icon name="information-circle" class="mx-auto size-9 text-zinc-400" />
                <div class="mt-2 text-sm font-bold text-zinc-700 dark:text-zinc-300">Unit Kerja Belum Ditetapkan</div>
                <div class="text-xs text-zinc-400 mt-1">Akun Anda belum ditugaskan ke Unit Kerja tertentu. Hubungi HRD untuk menetapkan unit kerja supervisor.</div>
            </div>
        @endif

        {{-- Personal Self-Service Widget for Supervisor --}}
        <div>
            <div class="mb-3.5 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal & Presensi Pribadi Anda</h3>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Informasi kehadiran dan kuota cuti supervisor</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal Hari Ini</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <flux:icon name="calendar" class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $myShiftToday['shift_name'] ?? 'Jadwal Normal' }}</div>
                    @if($myShiftToday)
                        <div class="mt-1 text-xs font-mono text-zinc-400">{{ $myShiftToday['start_time'] }} - {{ $myShiftToday['end_time'] }} WIB</div>
                    @endif
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Presensi Pribadi</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <flux:icon name="clock" class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 text-base font-bold text-emerald-600 dark:text-emerald-400">
                        {{ !empty($myAttendanceToday['clock_in']) ? 'Hadir: '.$myAttendanceToday['clock_in'] : 'Belum Check-In' }}
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('attendance.index') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Buka Presensi &rarr;</a>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sisa Cuti Tahunan</span>
                        <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <flux:icon name="calendar-days" class="size-4.5" />
                        </div>
                    </div>
                    <div class="mt-3 font-mono text-xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $myLeaveQuota['remaining_days'] ?? 0 }} Hari
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('leaves.index') }}" class="text-xs font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400">Kelola Cuti &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 3. ROLE: PAYROLL & FINANCE (COMPENSATION & DISBURSEMENT DASHBOARD)         --}}
    {{-- ========================================================================= --}}
    @elseif($userRole === 'payroll_finance')
        {{-- Payroll Header --}}
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-7 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-12 -top-12 size-64 rounded-full bg-gradient-to-br from-emerald-500/15 via-amber-500/10 to-transparent blur-3xl dark:from-emerald-500/25"></div>
            <div class="absolute -left-12 -bottom-12 size-56 rounded-full bg-gradient-to-tr from-teal-500/10 via-emerald-500/5 to-transparent blur-2xl dark:from-teal-500/15"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4 sm:gap-5">
                    <div class="relative flex size-15 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-lg shadow-emerald-600/25">
                        <flux:icon name="banknotes" class="size-8" />
                        <span class="absolute -bottom-1 -right-1 flex size-4 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900">
                            <span class="size-2 rounded-full bg-white animate-pulse"></span>
                        </span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <flux:heading size="xl" class="font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-2xl">
                                Dashboard Penggajian & Kompensasi
                            </flux:heading>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/40">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                Finance & Payroll
                            </span>
                        </div>
                        <flux:subheading class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400 sm:text-sm font-medium">
                            Selamat datang, <strong class="text-zinc-800 dark:text-zinc-200 font-bold">{{ auth()->user()?->name }}</strong>. Monitor eksekusi siklus payroll, tunjangan, dan pencairan dana rumah sakit.
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-2.5 rounded-2xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-2.5 text-xs font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-850 dark:text-zinc-300 shadow-2xs">
                        <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-mono font-semibold">{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="cursor-pointer rounded-2xl shadow-2xs">
                        <span wire:loading.remove wire:target="refreshData" class="flex items-center gap-1.5 font-semibold">
                            <flux:icon name="arrow-path" class="size-4" />
                            <span>Segarkan</span>
                        </span>
                        <span wire:loading wire:target="refreshData" class="flex items-center gap-1.5 font-semibold">
                            <flux:icon name="arrow-path" class="size-4 animate-spin text-emerald-600" />
                            <span>Memuat...</span>
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>

        @if($payroll)
            <div>
                <div class="mb-3.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rekapitulasi Penggajian Terakhir</h3>
                        <p class="text-[11px] text-zinc-400 mt-0.5">Siklus kalkulasi payroll rumah sakit yang telah difinalisasi</p>
                    </div>
                    <flux:badge size="sm" color="emerald" class="capitalize font-bold">Status: {{ $payroll['status'] ?? 'Finalized' }}</flux:badge>
                </div>

                {{-- 4 Primary Payroll Cards --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Total Bruto --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Penghasilan Kotor (Bruto)</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                                    <flux:icon name="currency-dollar" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 font-mono text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-400 dark:border-zinc-800">
                            <span>Akumulasi gaji pokok & tunjangan</span>
                        </div>
                    </div>

                    {{-- Total Potongan --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-rose-500 to-red-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Potongan & Pajak</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 shadow-2xs">
                                    <flux:icon name="receipt-refund" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 font-mono text-2xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400">
                                - Rp {{ number_format((float) ($payroll['total_deductions'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-400 dark:border-zinc-800">
                            <span>PPh 21, BPJS, & potongan kas</span>
                        </div>
                    </div>

                    {{-- Total Slip Gaji --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Slip Gaji Karyawan</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400 shadow-2xs">
                                    <flux:icon name="document-text" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $payroll['total_payslips'] ?? 0 }}
                                </span>
                                <span class="text-xs font-bold text-zinc-400">Slip</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-400 dark:border-zinc-800">
                            <span>Karyawan masuk kalkulasi</span>
                        </div>
                    </div>

                    {{-- Dana Bersih Disalurkan (Netto) --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-emerald-300/80 bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/50 p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-800/80 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-500 to-teal-400"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Dana Bersih (Netto) Disalurkan</span>
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm shadow-emerald-600/30">
                                    <flux:icon name="banknotes" class="size-5" />
                                </div>
                            </div>
                            <div class="mt-4 font-mono text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-4 border-t border-emerald-100 pt-3 text-xs text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300">
                            <span>Total take-home pay via bank</span>
                        </div>
                    </div>
                </div>

                {{-- Statutory Breakdown (BPJS Kesehatan & Ketenagakerjaan) --}}
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400 shadow-2xs">
                                    <flux:icon name="heart" class="size-5" />
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200">BPJS Kesehatan</div>
                                    <div class="text-[11px] text-zinc-400">Total beban premi perusahaan & pegawai</div>
                                </div>
                            </div>
                            <span class="font-mono text-base font-extrabold text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) ($payroll['total_bpjs_kesehatan'] ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                                    <flux:icon name="shield-check" class="size-5" />
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200">BPJS Ketenagakerjaan</div>
                                    <div class="text-[11px] text-zinc-400">JKK, JKM, JHT, & JP</div>
                                </div>
                            </div>
                            <span class="font-mono text-base font-extrabold text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format((float) ($payroll['total_bpjs_ketenagakerjaan'] ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('payroll.index') }}">
                    <flux:button variant="primary" icon="banknotes" class="rounded-2xl font-bold shadow-xs">
                        Buka Manajemen Payroll
                    </flux:button>
                </a>
                <a href="{{ route('reports.payroll') }}">
                    <flux:button variant="outline" icon="chart-bar" class="rounded-2xl font-bold shadow-2xs">
                        Laporan Penggajian
                    </flux:button>
                </a>
                <a href="{{ route('reports.pph21') }}">
                    <flux:button variant="subtle" icon="calculator" class="rounded-2xl font-bold shadow-2xs">
                        Laporan PPh 21
                    </flux:button>
                </a>
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-zinc-200 p-8 text-center dark:border-zinc-800">
                <flux:icon name="banknotes" class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                <div class="mt-3 text-sm font-bold text-zinc-700 dark:text-zinc-300">Belum Ada Siklus Penggajian</div>
                <div class="mt-1 text-xs text-zinc-400">Data ringkasan penggajian dan pembayaran akan muncul setelah ada payroll yang dihitung.</div>
                <div class="mt-4">
                    <a href="{{ route('payroll.index') }}">
                        <flux:button variant="primary" icon="banknotes" size="sm" class="rounded-2xl font-bold">
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
        <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-7 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="absolute -right-12 -top-12 size-64 rounded-full bg-gradient-to-br from-emerald-500/15 via-teal-500/10 to-transparent blur-3xl dark:from-emerald-500/25"></div>
            <div class="absolute -left-12 -bottom-12 size-56 rounded-full bg-gradient-to-tr from-blue-500/10 via-indigo-500/5 to-transparent blur-2xl dark:from-blue-500/15"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4 sm:gap-5">
                    <div class="relative flex size-15 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-lg shadow-emerald-600/25">
                        <flux:icon name="building-office-2" class="size-8" />
                        <span class="absolute -bottom-1 -right-1 flex size-4 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900">
                            <span class="size-2 rounded-full bg-white animate-pulse"></span>
                        </span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <flux:heading size="xl" class="font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 text-xl sm:text-2xl">
                                Dashboard Operasional & SDM
                            </flux:heading>
                            <flux:badge size="sm" color="emerald" class="shadow-2xs font-semibold">
                                RS Permata Bunda Executive
                            </flux:badge>
                        </div>
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->greeting }}, <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ auth()->user()?->name }}</span>. Pantau indikator kinerja rumah sakit terkini.
                        </flux:subheading>
                    </div>
                </div>

                {{-- Header Actions & Realtime Status --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Real-time Digital Clock & Date Capsule --}}
                    <div 
                        x-data="{ 
                            time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) 
                        }" 
                        x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                        class="flex items-center gap-2.5 rounded-2xl border border-zinc-200/90 bg-zinc-50/90 px-4 py-2 text-xs text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/70 dark:text-zinc-300 shadow-2xs"
                    >
                        <flux:icon name="calendar" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span class="font-medium">{{ now()->translatedFormat('l, d F Y') }}</span>
                        <span class="text-zinc-300 dark:text-zinc-600">•</span>
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 tabular-nums" x-text="time"></span>
                        <span class="text-[10px] uppercase font-semibold text-zinc-400">WIB</span>
                    </div>

                    {{-- Refresh Button --}}
                    <flux:button wire:click="refreshData" variant="subtle" size="sm" class="rounded-xl! cursor-pointer border border-zinc-200/80 dark:border-zinc-800">
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

            {{-- Executive Quick Action Deck inside Hero --}}
            <div class="mt-6 pt-5 border-t border-zinc-100 dark:border-zinc-800">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="bolt" class="size-3.5 text-amber-500" />
                        <span>Pintasan Operasional</span>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                        </span>
                        <span>Sistem SDM & Geofence Aktif</span>
                    </div>
                </div>

                {{-- Responsive Grid: 2 columns on mobile, 4 columns on sm+ --}}
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 sm:gap-3">
                    {{-- 1. Live Presensi --}}
                    <a href="{{ route('attendance.index') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-emerald-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                            <flux:icon name="clock" class="size-4.5 sm:size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-emerald-700 dark:text-zinc-100 dark:group-hover:text-emerald-400 transition-colors">
                                    Live Presensi
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                Pantau absensi
                            </p>
                        </div>
                    </a>

                    {{-- 2. Roster Shift --}}
                    <a href="{{ route('schedules.index') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-blue-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-950/60 dark:text-blue-400 shadow-2xs">
                            <flux:icon name="calendar" class="size-4.5 sm:size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-blue-700 dark:text-zinc-100 dark:group-hover:text-blue-400 transition-colors">
                                    Roster Shift
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                Jadwal kerja tim
                            </p>
                        </div>
                    </a>

                    {{-- 3. Approval Cuti --}}
                    <a href="{{ route('leaves.index') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-purple-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="relative flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white dark:bg-purple-950/60 dark:text-purple-400 shadow-2xs">
                            <flux:icon name="document-text" class="size-4.5 sm:size-5" />
                            @if($pendingLeavesCount > 0)
                                <span class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-amber-500 text-[9px] font-extrabold text-white ring-2 ring-white dark:ring-zinc-900">
                                    {{ $pendingLeavesCount }}
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-purple-700 dark:text-zinc-100 dark:group-hover:text-purple-400 transition-colors">
                                    Approval Cuti
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] font-medium {{ $pendingLeavesCount > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-zinc-500 dark:text-zinc-400' }}">
                                {{ $pendingLeavesCount > 0 ? $pendingLeavesCount . ' antrean permohonan' : 'Persetujuan cuti' }}
                            </p>
                        </div>
                    </a>

                    {{-- 4. Manajemen Payroll --}}
                    <a href="{{ route('payroll.index') }}" 
                        class="group relative flex items-center gap-2.5 sm:gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-2.5 sm:p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-500/50 hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-850/60 dark:hover:border-amber-500/40 dark:hover:bg-zinc-800 active:scale-98">
                        <div class="flex size-9 sm:size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 transition-all duration-200 group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-950/60 dark:text-amber-400 shadow-2xs">
                            <flux:icon name="banknotes" class="size-4.5 sm:size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="truncate text-xs font-bold text-zinc-900 group-hover:text-amber-700 dark:text-zinc-100 dark:group-hover:text-amber-400 transition-colors">
                                    Payroll
                                </span>
                                <flux:icon name="chevron-right" class="size-3 text-zinc-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all hidden sm:block" />
                            </div>
                            <p class="truncate text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
                                Proses gaji & slip
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Doctor Credential Compliance Warning Alert --}}
        @if(!empty($doctorAlerts))
            <div class="overflow-hidden rounded-3xl border border-amber-300/80 bg-gradient-to-r from-amber-50/90 via-orange-50/40 to-white p-5 sm:p-6 shadow-xs dark:border-amber-800/70 dark:from-amber-950/40 dark:via-zinc-900 dark:to-zinc-900">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3.5 text-amber-900 dark:text-amber-200">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-amber-200/80 text-amber-800 dark:bg-amber-900/70 dark:text-amber-300 shadow-xs">
                            <flux:icon name="exclamation-triangle" class="size-5" />
                        </div>
                        <div>
                            <div class="text-sm font-bold tracking-tight">
                                Peringatan Kepatuhan Izin Praktik Medis ({{ count($doctorAlerts) }} Dokter Perlu Perhatian)
                            </div>
                            <div class="text-xs text-amber-700 dark:text-amber-300/80 mt-0.5">
                                Surat Tanda Registrasi (STR) atau Surat Izin Praktik (SIP) dokter berikut akan segera kedaluwarsa dalam 60 hari.
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('doctors.index') }}" class="shrink-0">
                        <flux:button size="sm" variant="outline" class="border-amber-300 text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-200 rounded-xl!">
                            Kelola Dokter &rarr;
                        </flux:button>
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach(array_slice($doctorAlerts, 0, 6) as $doc)
                        <div class="flex flex-col justify-between rounded-2xl border border-amber-200/80 bg-white/95 p-4 shadow-2xs dark:border-amber-900/60 dark:bg-zinc-900/90 transition-all hover:border-amber-300 hover:shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-orange-400 text-xs font-extrabold text-white shadow-2xs">
                                    {{ strtoupper(substr($doc['name'] ?? 'Dr', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $doc['name'] }}</div>
                                    <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">{{ $doc['specialization'] ?? 'Dokter Umum' }}</div>
                                </div>
                            </div>
                            <div class="mt-3.5 flex items-center justify-between border-t border-amber-100/80 pt-2.5 text-[11px] dark:border-zinc-800">
                                <span class="font-mono text-zinc-600 dark:text-zinc-400 font-medium">STR s/d: {{ $doc['str_valid_until'] }}</span>
                                <flux:badge size="xs" color="amber" class="font-bold">Segera Berakhir</flux:badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Staffing Distribution Metrics --}}
        <div>
            <div class="mb-3.5 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Distribusi Ketenagaan Rumah Sakit</h3>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Ringkasan demografi staf medis dan non-medis aktif</p>
                </div>
                <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400">
                    <span>Direktori Pegawai</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Total Karyawan Aktif --}}
                <div class="group relative flex items-center gap-4 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-blue-500 to-indigo-500"></div>
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 shadow-xs dark:bg-blue-950/60 dark:text-blue-400">
                        <flux:icon name="users" class="size-7" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Karyawan Aktif</span>
                            <flux:badge size="xs" color="blue">Organik</flux:badge>
                        </div>
                        <div class="mt-1 font-mono text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ number_format($staffing['total_active_employees'] ?? 0) }}
                        </div>
                        <div class="text-[11px] text-zinc-400 font-medium mt-0.5">Pegawai organik operasional</div>
                    </div>
                </div>

                {{-- Dokter & Spesialis Aktif --}}
                <div class="group relative flex items-center gap-4 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-emerald-500 to-teal-500"></div>
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-xs dark:bg-emerald-950/60 dark:text-emerald-400">
                        <flux:icon name="user-group" class="size-7" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Dokter & Spesialis Aktif</span>
                            <flux:badge size="xs" color="emerald">Medis</flux:badge>
                        </div>
                        <div class="mt-1 font-mono text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
                            {{ number_format($staffing['total_active_doctors'] ?? 0) }}
                        </div>
                        <div class="text-[11px] text-zinc-400 font-medium mt-0.5">Dokter berizin praktik aktif</div>
                    </div>
                </div>

                {{-- Total Tenaga Kerja Keseluruhan --}}
                <div class="group relative flex items-center gap-4 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 sm:col-span-2 lg:col-span-1">
                    <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-purple-500 to-pink-500"></div>
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 shadow-xs dark:bg-purple-950/60 dark:text-purple-400">
                        <flux:icon name="building-office-2" class="size-7" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Tenaga Kerja Keseluruhan</span>
                            <flux:badge size="xs" color="purple">Total SDM</flux:badge>
                        </div>
                        <div class="mt-1 font-mono text-3xl font-extrabold tracking-tight text-purple-600 dark:text-purple-400">
                            {{ number_format($staffing['total_workforce'] ?? 0) }}
                        </div>
                        <div class="text-[11px] text-zinc-400 font-medium mt-0.5">Karyawan + Tenaga Dokter</div>
                    </div>
                </div>
            </div>

            {{-- Contract Breakdown Pills --}}
            @if(!empty($staffing['contract_breakdown']))
                <div class="mt-3.5 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400">Status Ikatan Kerja:</span>
                    @foreach($staffing['contract_breakdown'] as $contractType => $count)
                        <div class="flex items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-3 py-1.5 text-xs dark:border-zinc-800 dark:bg-zinc-900 shadow-2xs">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $contractType }}</span>
                            <span class="rounded-lg bg-zinc-100 px-2 py-0.5 font-mono text-[11px] font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Today's Attendance Overview & Operational Progress Radar --}}
        <div>
            @php
                $sched = max((int) ($attendance['total_scheduled'] ?? 0), 1);
                $checked = (int) ($attendance['total_checked_in'] ?? 0);
                $presentCount = (int) ($attendance['present_count'] ?? 0);
                $lateCount = (int) ($attendance['late_count'] ?? 0);
                $unreported = max(0, $sched - $checked);
                
                $pctPresent = round(($presentCount / $sched) * 100, 1);
                $pctLate = round(($lateCount / $sched) * 100, 1);
                $pctUnreported = max(0, round(100 - $pctPresent - $pctLate, 1));
                $attRate = round(($checked / $sched) * 100, 1);
            @endphp

            <div class="mb-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Rekapitulasi Kehadiran Hari Ini ({{ $attendance['date'] ?? now()->toDateString() }})
                    </h3>
                    <flux:badge size="xs" :color="$attRate >= 80 ? 'emerald' : 'amber'" class="font-bold">
                        {{ $attRate }}% Kehadiran
                    </flux:badge>
                </div>
                <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400">
                    <span>Live Presensi</span>
                    <span>&rarr;</span>
                </a>
            </div>

            {{-- Multi-Segment Stacked Visual Attendance Progress Bar --}}
            <div class="mb-4 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between text-xs font-semibold mb-2">
                    <span class="text-zinc-700 dark:text-zinc-300">Komposisi Presensi Shift Berjalan</span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $checked }} dari {{ $sched }} Staf Hadir</span>
                </div>

                {{-- Stacked Bar --}}
                <div class="h-3 w-full rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex">
                    <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ $pctPresent }}%" title="Tepat Waktu: {{ $presentCount }} ({{ $pctPresent }}%)"></div>
                    <div class="bg-amber-400 h-full transition-all duration-500" style="width: {{ $pctLate }}%" title="Terlambat: {{ $lateCount }} ({{ $pctLate }}%)"></div>
                    <div class="bg-zinc-200 dark:bg-zinc-700 h-full transition-all duration-500" style="width: {{ $pctUnreported }}%" title="Belum Check-In: {{ $unreported }} ({{ $pctUnreported }}%)"></div>
                </div>

                {{-- Segment Legend --}}
                <div class="mt-3 flex flex-wrap items-center justify-between gap-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-zinc-600 dark:text-zinc-400 font-medium">Tepat Waktu: <strong class="text-zinc-900 dark:text-zinc-100">{{ $presentCount }}</strong> ({{ $pctPresent }}%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-amber-400"></span>
                        <span class="text-zinc-600 dark:text-zinc-400 font-medium">Terlambat: <strong class="text-zinc-900 dark:text-zinc-100">{{ $lateCount }}</strong> ({{ $pctLate }}%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                        <span class="text-zinc-600 dark:text-zinc-400 font-medium">Belum Hadir: <strong class="text-zinc-900 dark:text-zinc-100">{{ $unreported }}</strong> ({{ $pctUnreported }}%)</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Jadwal Terdaftar --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Jadwal Terdaftar</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                <flux:icon name="calendar" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ $attendance['total_scheduled'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-zinc-400">Staf Terjadwal</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                        <span>Roster shift terjadwal</span>
                    </div>
                </div>

                {{-- Hadir Tepat Waktu --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hadir Tepat Waktu</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <flux:icon name="check-circle" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-extrabold tabular-nums text-emerald-600 dark:text-emerald-400">
                                {{ $attendance['present_count'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-emerald-600/80 dark:text-emerald-400/80">Staf ({{ $pctPresent }}%)</span>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800">
                        <span>Check-in sesuai toleransi</span>
                    </div>
                </div>

                {{-- Hadir Terlambat --}}
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hadir Terlambat</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                <flux:icon name="exclamation-circle" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-extrabold tabular-nums text-amber-600 dark:text-amber-400">
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
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Check-In</span>
                            <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                <flux:icon name="finger-print" class="size-4" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-extrabold tabular-nums text-purple-600 dark:text-purple-400">
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
                <div class="mb-3.5 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ringkasan Penggajian Terakhir</h3>
                        <p class="text-[11px] text-zinc-400 mt-0.5">Kalkulasi payroll periode terakhir yang difinalisasi</p>
                    </div>
                    <a href="{{ route('payroll.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400">
                        <span>Modul Payroll</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Gaji Kotor (Bruto)</div>
                        <div class="mt-2 font-mono text-xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="mt-2 text-[11px] text-zinc-400">Total bruto periode berjalan</div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Potongan & Pajak</div>
                        <div class="mt-2 font-mono text-xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400">
                            - Rp {{ number_format((float) ($payroll['total_deductions'] ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="mt-2 text-[11px] text-zinc-400">BPJS, PPh 21, & potongan</div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Slip Gaji</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="font-mono text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ $payroll['total_payslips'] ?? 0 }}
                            </span>
                            <span class="text-xs font-medium text-zinc-400">Slip Karyawan</span>
                        </div>
                        <div class="mt-2 text-[11px] text-zinc-400">Karyawan terproses</div>
                    </div>

                    <div class="rounded-3xl border border-emerald-300/80 bg-gradient-to-br from-emerald-50/90 via-white to-teal-50/50 p-5 shadow-xs dark:border-emerald-800 dark:from-emerald-950/40 dark:via-zinc-900 dark:to-zinc-900 transition-all hover:shadow-md">
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Take Home Pay (Bersih)</div>
                        <div class="mt-2 font-mono text-xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                        </div>
                        <div class="mt-2 text-[11px] text-emerald-700 dark:text-emerald-300 font-medium">Total netto siap salur</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- HR Analytics & SDM Demographics Dashboard --}}
        @if(!empty($hrAnalytics))
            <div class="space-y-6">
                {{-- Section Title & Filter Summary --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-200/80 pb-3.5 dark:border-zinc-800">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                Statistik & Demografi SDM Rumah Sakit
                            </h3>
                            <flux:badge size="sm" color="emerald" class="font-semibold shadow-2xs">
                                Terverifikasi
                            </flux:badge>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Profil komprehensif demografi pegawai, jenjang pendidikan, rasio gender, masa kerja, dan indikator performa (KPI)
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3 py-1.5 text-xs font-semibold text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                            Total SDM Organik: <strong class="text-zinc-900 dark:text-zinc-100">{{ $hrAnalytics['total_active_employees'] ?? 0 }}</strong> Orang
                        </span>
                        <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400">
                            <span>Detail SDM</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                {{-- 1. KPI & Performance Indicators (4 Modern Metric Cards) --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- KPI 1: Kehadiran Bulanan --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tingkat Kehadiran</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <flux:icon name="clock" class="size-4.5" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $hrAnalytics['kpis']['attendance_rate'] ?? 100 }}%
                                </span>
                                <flux:badge size="xs" color="emerald" class="font-bold">Bulan Ini</flux:badge>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800 flex items-center justify-between">
                            <span>Target KPI: &ge; 95%</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">Optimal</span>
                        </div>
                    </div>

                    {{-- KPI 2: Ketepatan Waktu Presensi --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ketepatan Waktu</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                    <flux:icon name="check-circle" class="size-4.5" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-blue-600 dark:text-blue-400">
                                    {{ $hrAnalytics['kpis']['punctuality_rate'] ?? 100 }}%
                                </span>
                                <flux:badge size="xs" color="blue" class="font-bold">Punctuality</flux:badge>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800 flex items-center justify-between">
                            <span>Check-in bebas keterlambatan</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400">Terpantau</span>
                        </div>
                    </div>

                    {{-- KPI 3: Indeks Kepatuhan Disiplin (Bebas SP) --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-violet-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Kepatuhan Disiplin</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                    <flux:icon name="shield-check" class="size-4.5" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-purple-600 dark:text-purple-400">
                                    {{ $hrAnalytics['kpis']['discipline_index'] ?? 100 }}%
                                </span>
                                <flux:badge size="xs" color="purple" class="font-bold">Bebas SP</flux:badge>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800 flex items-center justify-between">
                            <span>SP/Teguran Aktif: <strong class="text-zinc-700 dark:text-zinc-300">{{ $hrAnalytics['kpis']['active_warning_letters'] ?? 0 }}</strong></span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400">Disiplin Kerja</span>
                        </div>
                    </div>

                    {{-- KPI 4: Rata-Rata Saldo Cuti Aktif --}}
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sisa Cuti Rata-rata</span>
                                <div class="flex size-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                    <flux:icon name="document-text" class="size-4.5" />
                                </div>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="font-mono text-3xl font-extrabold tabular-nums text-amber-600 dark:text-amber-400">
                                    {{ $hrAnalytics['kpis']['avg_leave_balance'] ?? 0 }}
                                </span>
                                <span class="text-xs font-bold text-zinc-400">Hari / Pegawai</span>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400 dark:border-zinc-800 flex items-center justify-between">
                            <span>Kesejahteraan & Hak Istirahat</span>
                            <span class="font-semibold text-amber-600 dark:text-amber-400">Tahun {{ now()->year }}</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Core Demographics Grid (Usia, Gender, Pendidikan) --}}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    {{-- Card 1: Statistik Karyawan per Usia --}}
                    <div class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div>
                            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                        Demografi Rentang Usia
                                    </h4>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">
                                        Rata-rata Usia: <strong class="text-emerald-600 dark:text-emerald-400 font-mono">{{ $hrAnalytics['average_age'] ?? 0 }} Tahun</strong>
                                    </p>
                                </div>
                                <div class="flex size-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <flux:icon name="user" class="size-4" />
                                </div>
                            </div>

                            <div class="mt-4 space-y-3.5">
                                @forelse($hrAnalytics['age_distribution'] ?? [] as $ageGroup)
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $ageGroup['range'] }} Thn
                                            </span>
                                            <span class="font-mono text-zinc-500 dark:text-zinc-400">
                                                <strong class="text-zinc-900 dark:text-zinc-100">{{ $ageGroup['count'] }}</strong> ({{ $ageGroup['percentage'] }}%)
                                            </span>
                                        </div>
                                        <div class="h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-500" 
                                                style="width: {{ $ageGroup['percentage'] }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-zinc-400 py-4 text-center">Data usia belum tersedia</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px] text-zinc-400">
                            <span>Kelompok usia produktif</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">25 - 45 Thn Dominan</span>
                        </div>
                    </div>

                    {{-- Card 2: Komposisi Jenis Kelamin --}}
                    <div class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div>
                            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                        Proporsi Jenis Kelamin
                                    </h4>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">Komposisi gender tenaga kerja RS</p>
                                </div>
                                <div class="flex size-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                                    <flux:icon name="users" class="size-4" />
                                </div>
                            </div>

                            @php
                                $mCount = (int) ($hrAnalytics['gender']['male_count'] ?? 0);
                                $fCount = (int) ($hrAnalytics['gender']['female_count'] ?? 0);
                                $mPct = (float) ($hrAnalytics['gender']['male_percentage'] ?? 0);
                                $fPct = (float) ($hrAnalytics['gender']['female_percentage'] ?? 0);
                            @endphp

                            {{-- Gender Split Visual Progress Bar --}}
                            <div class="mt-4">
                                <div class="h-3.5 w-full rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex shadow-inner">
                                    <div class="bg-blue-500 h-full transition-all duration-500" style="width: {{ $mPct }}%" title="Laki-laki: {{ $mCount }} ({{ $mPct }}%)"></div>
                                    <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ $fPct }}%" title="Perempuan: {{ $fCount }} ({{ $fPct }}%)"></div>
                                </div>
                            </div>

                            {{-- Gender Details Pill Deck --}}
                            <div class="mt-5 grid grid-cols-2 gap-3">
                                {{-- Laki-laki --}}
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-3.5 dark:border-blue-900/40 dark:bg-blue-950/30">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2.5 rounded-full bg-blue-500"></span>
                                        <span class="text-xs font-bold text-blue-950 dark:text-blue-200">Laki-laki</span>
                                    </div>
                                    <div class="mt-2 font-mono text-2xl font-extrabold text-blue-700 dark:text-blue-300">
                                        {{ $mCount }}
                                    </div>
                                    <div class="mt-0.5 text-[11px] font-semibold text-blue-600/80 dark:text-blue-400">
                                        {{ $mPct }}% dari total
                                    </div>
                                </div>

                                {{-- Perempuan --}}
                                <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-3.5 dark:border-rose-900/40 dark:bg-rose-950/30">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2.5 rounded-full bg-rose-500"></span>
                                        <span class="text-xs font-bold text-rose-950 dark:text-rose-200">Perempuan</span>
                                    </div>
                                    <div class="mt-2 font-mono text-2xl font-extrabold text-rose-700 dark:text-rose-300">
                                        {{ $fCount }}
                                    </div>
                                    <div class="mt-0.5 text-[11px] font-semibold text-rose-600/80 dark:text-rose-400">
                                        {{ $fPct }}% dari total
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px] text-zinc-400">
                            <span>Keseimbangan lingkungan kerja</span>
                            <span class="font-semibold text-zinc-600 dark:text-zinc-300">Inklusif & Terbuka</span>
                        </div>
                    </div>

                    {{-- Card 3: Tingkat Pendidikan --}}
                    <div class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div>
                            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                        Tingkat Pendidikan
                                    </h4>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">Kualifikasi akademik staf RS</p>
                                </div>
                                <div class="flex size-8 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                                    <flux:icon name="academic-cap" class="size-4" />
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @forelse($hrAnalytics['education_distribution'] ?? [] as $edu)
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $edu['level'] }}
                                            </span>
                                            <span class="font-mono text-zinc-500 dark:text-zinc-400">
                                                <strong class="text-zinc-900 dark:text-zinc-100">{{ $edu['count'] }}</strong> ({{ $edu['percentage'] }}%)
                                            </span>
                                        </div>
                                        <div class="h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 transition-all duration-500" 
                                                style="width: {{ $edu['percentage'] }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-zinc-400 py-4 text-center">Data kualifikasi belum terdata</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px] text-zinc-400">
                            <span>Standar kompetensi medis</span>
                            <span class="font-semibold text-purple-600 dark:text-purple-400">Kualifikasi Terverifikasi</span>
                        </div>
                    </div>
                </div>

                {{-- 3. Row 3: Masa Kerja & Sebaran Unit Operasional --}}
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    {{-- Masa Kerja / Tenure --}}
                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                    Masa Kerja Pegawai (Tenure)
                                </h4>
                                <p class="text-[11px] text-zinc-400 mt-0.5">
                                    Rata-rata Masa Bakti: <strong class="text-indigo-600 dark:text-indigo-400 font-mono">{{ $hrAnalytics['tenure']['average_years'] ?? 0 }} Tahun</strong>
                                </p>
                            </div>
                            <div class="flex size-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                <flux:icon name="briefcase" class="size-4" />
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach($hrAnalytics['tenure']['distribution'] ?? [] as $tGroup)
                                <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 p-3 text-center dark:border-zinc-800 dark:bg-zinc-850">
                                    <div class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400">{{ $tGroup['range'] }}</div>
                                    <div class="mt-1.5 font-mono text-xl font-extrabold text-zinc-900 dark:text-zinc-100">
                                        {{ $tGroup['count'] }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ $tGroup['percentage'] }}%
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Top 5 Unit Distribusi --}}
                    <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                    Sebaran Unit Kerja Terbesar
                                </h4>
                                <p class="text-[11px] text-zinc-400 mt-0.5">Penempatan staf aktif pada unit operasional utama</p>
                            </div>
                            <div class="flex size-8 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400">
                                <flux:icon name="building-office" class="size-4" />
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse($hrAnalytics['unit_distribution'] ?? [] as $unit)
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-[200px]">
                                            {{ $unit['name'] }}
                                        </span>
                                        <span class="font-mono text-zinc-500 dark:text-zinc-400">
                                            <strong class="text-zinc-900 dark:text-zinc-100">{{ $unit['count'] }}</strong> Staf ({{ $unit['percentage'] }}%)
                                        </span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                        <div 
                                            class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-400 transition-all duration-500" 
                                            style="width: {{ $unit['percentage'] }}%"
                                        ></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-zinc-400 py-4 text-center">Belum ada data unit kerja</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>


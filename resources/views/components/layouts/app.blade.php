<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

        <title>{{ $title ?? config('app.name', 'SIM SDM RS Permata Bunda') }}</title>

        <!-- Web App Manifest & Mobile Capabilities -->
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="RS Permata Bunda">
        <link rel="apple-touch-icon" href="{{ asset('assets/icons/apple-touch-icon.png') }}">
        <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

        <link rel="icon" type="image/png" href="{{ asset('assets/icons/icon-192.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 antialiased selection:bg-emerald-500/20">
        {{-- Collapsible Sidebar with Enhanced Desktop Noticeability --}}
        <flux:sidebar sticky collapsible class="border-r border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900 w-72 sm:w-80 max-w-[85vw] lg:w-76 max-lg:shadow-2xl max-lg:z-70! pt-safe">
            <flux:sidebar.header class="relative px-2.5 py-3 border-b border-zinc-100 dark:border-zinc-800/80 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:justify-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0 group in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:opacity-0 transition-opacity">
                    <div class="relative flex size-9.5 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500/10 to-teal-500/10 p-1.5 border border-emerald-500/20 dark:border-emerald-500/30 shadow-2xs group-hover:scale-105 transition-transform">
                        <img 
                            src="{{ asset('assets/RS-Permata-Bunda.jpg') }}" 
                            alt="RS Permata Bunda" 
                            class="size-full object-contain rounded-xl" 
                        />
                        <span class="absolute -bottom-0.5 -right-0.5 size-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900 animate-pulse"></span>
                    </div>
                    <div class="min-w-0 flex-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="truncate text-xs font-black tracking-tight text-zinc-900 dark:text-zinc-100 uppercase">RS PERMATA BUNDA</span>
                            <span class="shrink-0 rounded-full bg-emerald-500/10 px-1.5 py-0.2 text-[9px] font-bold text-emerald-700 dark:text-emerald-300">ONLINE</span>
                        </div>
                        <p class="truncate text-[11px] font-medium text-zinc-400 mt-0.5">SIM SDM &amp; Payroll</p>
                    </div>
                </a>

                <flux:sidebar.collapse class="max-lg:hidden in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-1.5 in-data-flux-sidebar-collapsed-desktop:inset-0 in-data-flux-sidebar-collapsed-desktop:m-auto cursor-pointer" />
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')">
                    Dashboard
                </flux:sidebar.item>

                {{-- 1. DATA MASTER --}}
                @php
                    $canSeeDataMaster = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll') || auth()->user()?->hasPermission('system.config_view');
                @endphp
                @if($canSeeDataMaster)
                    <flux:sidebar.group 
                        expandable 
                        :expanded="request()->routeIs('master.*')" 
                        heading="Data Master" 
                        icon="circle-stack"
                        class="mt-3"
                    >
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin'))
                            <flux:sidebar.item icon="building-office-2" href="{{ route('master.units') }}" :current="request()->routeIs('master.units')">
                                Unit Kerja
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="briefcase" href="{{ route('master.positions') }}" :current="request()->routeIs('master.positions')">
                                Standar Jabatan
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->can('schedule.manage_shifts'))
                            <flux:sidebar.item icon="clock" href="{{ route('master.shifts') }}" :current="request()->routeIs('master.shifts')">
                                Shift Kerja
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->can('leave.manage_policy'))
                            <flux:sidebar.item icon="calendar-days" href="{{ route('master.leave-types') }}" :current="request()->routeIs('master.leave-types')">
                                Jenis Cuti
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager'))
                            <flux:sidebar.item icon="exclamation-triangle" href="{{ route('master.warning-letter-types') }}" :current="request()->routeIs('master.warning-letter-types')">
                                Tingkat &amp; Jenis SP
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->can('system.config_view'))
                            <flux:sidebar.item icon="map-pin" href="{{ route('master.geofences') }}" :current="request()->routeIs('master.geofences')">
                                Titik Geofence
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll') || auth()->user()?->can('payroll.calculate'))
                            <flux:sidebar.item icon="calendar" href="{{ route('master.payroll-periods') }}" :current="request()->routeIs('master.payroll-periods')">
                                Periode Payroll
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll'))
                            <flux:sidebar.item icon="currency-dollar" href="{{ route('master.earnings') }}" :current="request()->routeIs('master.earnings')">
                                Komponen Penghasilan
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="receipt-refund" href="{{ route('master.deductions') }}" :current="request()->routeIs('master.deductions')">
                                Komponen Potongan
                            </flux:sidebar.item>
                        @endif

                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll') || auth()->user()?->can('statutory.view'))
                            <flux:sidebar.item icon="scale" href="{{ route('master.bpjs') }}" :current="request()->routeIs('master.bpjs')">
                                Regulasi BPJS
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="calculator" href="{{ route('master.pph21') }}" :current="request()->routeIs('master.pph21')">
                                Parameter PPh 21
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- 2. KEPEGAWAIAN & MEDIS --}}
                @php
                    $canSeeEmployees = auth()->user()?->can('employee.view');
                    $canSeeDoctors = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->can('doctor.manage_credentials');
                    $canSeeOrg = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->can('org.create') || auth()->user()?->can('org.update');
                @endphp

                @if($canSeeEmployees || $canSeeDoctors || $canSeeOrg)
                    <flux:sidebar.group heading="KEPEGAWAIAN & MEDIS" class="mt-3">
                        @if($canSeeEmployees)
                            <flux:sidebar.item icon="users" href="{{ route('employees.index') }}" :current="request()->routeIs('employees.*')">
                                Pegawai & Staf
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeDoctors)
                            <flux:sidebar.item icon="user-group" href="{{ route('doctors.index') }}" :current="request()->routeIs('doctors.*')">
                                Tenaga Medis & Dokter
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeOrg)
                            <flux:sidebar.item icon="building-office" href="{{ route('org.structure') }}" :current="request()->routeIs('org.*')">
                                Struktur Organisasi
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- 3. WAKTU & OPERASIONAL --}}
                <flux:sidebar.group heading="WAKTU & OPERASIONAL" class="mt-3">
                    @can('schedule.view')
                        <flux:sidebar.item icon="calendar" href="{{ route('schedules.index') }}" :current="request()->routeIs('schedules.*')">
                            Jadwal & Shift Roster
                        </flux:sidebar.item>
                    @endcan

                    @if(auth()->user()?->can('attendance.view_summary') || auth()->user()?->can('attendance.check_in_out') || auth()->user()?->can('attendance.view_own') || auth()->user()?->can('attendance.view_all'))
                        <flux:sidebar.item icon="clock" href="{{ route('attendance.index') }}" :current="request()->routeIs('attendance.*')">
                            Presensi
                        </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->can('leave.view_own') || auth()->user()?->can('leave.create_own') || auth()->user()?->can('leave.view_all'))
                        <flux:sidebar.item icon="document-text" href="{{ route('leaves.index') }}" :current="request()->routeIs('leaves.*')">
                            Pengajuan Cuti & Izin
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                {{-- 4. KEUANGAN & DOKUMEN --}}
                @php
                    $canSeePayroll = auth()->user()?->can('payroll.view_all');
                    $canSeeMyPayslips = auth()->user()?->employee !== null;
                    $canSeeDocuments = auth()->user()?->can('document.view_own') || auth()->user()?->can('document.view_all');
                @endphp

                @if($canSeePayroll || $canSeeMyPayslips || $canSeeDocuments)
                    <flux:sidebar.group heading="KEUANGAN & DOKUMEN" class="mt-3">
                        @if($canSeePayroll)
                            <flux:sidebar.item icon="banknotes" href="{{ route('payroll.index') }}" :current="request()->routeIs('payroll.index')">
                                Penggajian (Payroll)
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeMyPayslips)
                            <flux:sidebar.item icon="receipt-percent" href="{{ route('payroll.my-payslips') }}" :current="request()->routeIs('payroll.my-payslips')">
                                Slip Gaji
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeDocuments)
                            <flux:sidebar.item icon="folder" href="{{ route('documents.index') }}" :current="request()->routeIs('documents.*')">
                                Arsip Dokumen
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- 5. LAPORAN --}}
                @php
                    $canSeeEmployeeReport = auth()->user()?->can('employee.view') || auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager');
                    $canSeeAttendanceReport = auth()->user()?->can('attendance.view_all') || auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->hasRole('payroll');
                    $canSeeLeaveReport = auth()->user()?->can('leave.view_all') || auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->hasRole('payroll');
                    $canSeePayrollReport = auth()->user()?->can('payroll.view_all') || auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->hasRole('payroll') || auth()->user()?->hasRole('finance');
                    $canSeePph21Report = $canSeePayrollReport || auth()->user()?->can('statutory.view') || auth()->user()?->hasRole('finance');
                    $canSeeDoctorReport = auth()->user()?->can('doctor.view_credentials') || auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager');
                    $canSeeReports = $canSeeEmployeeReport || $canSeeAttendanceReport || $canSeeLeaveReport || $canSeePayrollReport || $canSeePph21Report || $canSeeDoctorReport;
                @endphp

                @if($canSeeReports)
                    <flux:sidebar.group 
                        expandable 
                        :expanded="request()->routeIs('reports.*')" 
                        heading="Laporan" 
                        icon="chart-bar"
                        class="mt-3"
                    >
                        @if($canSeeEmployeeReport)
                            <flux:sidebar.item icon="users" href="{{ route('reports.employees') }}" :current="request()->routeIs('reports.employees')">
                                Laporan Kepegawaian
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeAttendanceReport)
                            <flux:sidebar.item icon="clock" href="{{ route('reports.attendance') }}" :current="request()->routeIs('reports.attendance')">
                                Laporan Kehadiran
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeLeaveReport)
                            <flux:sidebar.item icon="calendar-days" href="{{ route('reports.leaves') }}" :current="request()->routeIs('reports.leaves')">
                                Laporan Cuti & Izin
                            </flux:sidebar.item>
                        @endif

                        @if($canSeePayrollReport)
                            <flux:sidebar.item icon="banknotes" href="{{ route('reports.payroll') }}" :current="request()->routeIs('reports.payroll')">
                                Laporan Payroll
                            </flux:sidebar.item>
                        @endif

                        @if($canSeePph21Report)
                            <flux:sidebar.item icon="calculator" href="{{ route('reports.pph21') }}" :current="request()->routeIs('reports.pph21')">
                                Laporan PPh 21
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeDoctorReport)
                            <flux:sidebar.item icon="user-group" href="{{ route('reports.doctors') }}" :current="request()->routeIs('reports.doctors')">
                                Laporan Dokter
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- 6. ADMINISTRASI & SISTEM --}}
                @php
                    $canSeeAudit = auth()->user()?->can('system.audit_view');
                    $canSeeSettings = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasPermission('system.config_view');
                    $canSeeCloudStorage = auth()->user()?->isSuperAdmin() || auth()->user()?->hasRole('super_admin');
                @endphp

                @if($canSeeAudit || $canSeeSettings || $canSeeCloudStorage)
                    <flux:sidebar.group heading="ADMINISTRASI & SISTEM" class="mt-3">
                        @if($canSeeCloudStorage)
                            <flux:sidebar.item icon="cloud" href="{{ route('system.google-drive-storage') }}" :current="request()->routeIs('system.google-drive-storage')">
                                Penyimpanan Cloud
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeAudit)
                            <flux:sidebar.item icon="shield-check" href="{{ route('audit-logs.index') }}" :current="request()->routeIs('audit-logs.*')">
                                Audit Trail
                            </flux:sidebar.item>
                        @endif

                        @if($canSeeSettings)
                            <flux:sidebar.item icon="cog-6-tooth" href="{{ route('system.settings') }}" :current="request()->routeIs('system.settings')">
                                Pengaturan Sistem
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <div class="px-1 py-2 border-t border-zinc-100 dark:border-zinc-800/80 pb-safe max-lg:pb-10 in-data-flux-sidebar-collapsed-desktop:px-0">
                    <flux:dropdown position="top" align="start" class="w-full in-data-flux-sidebar-collapsed-desktop:flex in-data-flux-sidebar-collapsed-desktop:justify-center">
                        <button type="button" class="group flex w-full items-center gap-3 rounded-2xl p-2 text-left transition-all hover:bg-zinc-100/80 dark:hover:bg-zinc-800/60 cursor-pointer in-data-flux-sidebar-collapsed-desktop:p-0 in-data-flux-sidebar-collapsed-desktop:size-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:rounded-xl in-data-flux-sidebar-collapsed-desktop:mx-auto" title="{{ auth()->user()?->name ?? 'Pengguna' }}">
                            @if(auth()->user()?->profile_photo_url)
                                <img src="{{ auth()->user()->profile_photo_url }}" class="size-8.5 shrink-0 rounded-xl object-cover ring-1 ring-zinc-200 dark:ring-zinc-700 shadow-xs" alt="{{ auth()->user()->name }}" />
                            @else
                                <div class="flex size-8.5 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 font-mono text-xs font-bold text-white shadow-xs">
                                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                                <div class="truncate text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ auth()->user()?->name ?? 'Pengguna' }}
                                </div>
                                <div class="truncate text-[10px] text-zinc-400 font-medium">
                                    {{ auth()->user()?->roles->first()?->name ? ucfirst(str_replace('_', ' ', auth()->user()->roles->first()->name)) : 'Staf RS' }}
                                </div>
                            </div>
                            <flux:icon name="chevron-up-down" class="size-4 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 in-data-flux-sidebar-collapsed-desktop:hidden" />
                        </button>

                        <flux:menu class="w-64 p-1.5 rounded-2xl shadow-xl border border-zinc-200/80 dark:border-zinc-800">
                            <div class="px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-800">
                                <p class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">Masuk sebagai</p>
                                <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate mt-0.5">{{ auth()->user()?->email }}</p>
                                @if(auth()->user()?->roles->isNotEmpty())
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach(auth()->user()->roles as $role)
                                            <flux:badge size="xs" color="emerald" class="font-bold">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <flux:menu.item href="{{ route('profile') }}" icon="user-circle">
                                Kelola Akun
                            </flux:menu.item>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-rose-600 dark:text-rose-400 font-medium cursor-pointer">
                                    Keluar (Logout)
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{-- Top App Header --}}
        <flux:header sticky class="border-b border-zinc-200/80 bg-white/95 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/95 pt-safe h-16 px-3 sm:px-6 z-40!">
            <flux:sidebar.toggle class="me-2.5 lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 lg:hidden me-2.5">
                <img src="{{ asset('assets/RS-Permata-Bunda.jpg') }}" alt="SIM SDM RS Permata Bunda" class="h-7 w-auto max-w-[110px] object-contain rounded" />
            </a>

            {{-- Left: Breadcrumbs & Hospital Live Status Pill --}}
            <div class="flex items-center gap-3">
                <flux:breadcrumbs class="max-sm:hidden">
                    <flux:breadcrumbs.item href="{{ route('dashboard') }}" class="font-semibold text-zinc-900 dark:text-zinc-100">
                        SIM SDM
                    </flux:breadcrumbs.item>
                    @if(isset($title))
                        <flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item>
                    @endif
                </flux:breadcrumbs>

                <div class="hidden 2xl:flex items-center gap-1.5 px-2.5 py-0.8 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>RS Permata Bunda • 24 Jam</span>
                </div>
            </div>

            <flux:spacer />

            {{-- Right: Command Search Pill, Dark Mode, Notifications, User Capsule --}}
            <div class="flex items-center gap-2">
                {{-- Quick Command / Search Pill (Instant Keyboard ⌘K Navigation) --}}
                <button 
                    type="button" 
                    x-data 
                    x-on:click="$dispatch('open-command-palette')" 
                    class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl border border-zinc-200/90 bg-zinc-50 hover:bg-zinc-100 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-800/60 dark:hover:bg-zinc-800 text-xs text-zinc-500 dark:text-zinc-400 transition-all cursor-pointer shadow-2xs group"
                    title="Buka Pencarian Cepat Modul (⌘K)"
                >
                    <flux:icon name="magnifying-glass" class="size-3.5 text-zinc-400 group-hover:text-emerald-600 transition-colors" />
                    <span class="font-normal">Cari menu...</span>
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-semibold bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded text-zinc-400 dark:text-zinc-300">⌘K</kbd>
                </button>

                {{-- Interactive Hospital Alert & Notification Dropdown --}}
                <x-notification-dropdown />

                {{-- Dark Mode Toggle --}}
                <flux:button
                    x-data
                    x-on:click="$flux.dark = ! $flux.dark"
                    variant="subtle"
                    size="sm"
                    icon="moon"
                    class="size-8.5 rounded-xl!"
                    aria-label="Ganti mode gelap"
                />

                {{-- Executive User Profile Capsule --}}
                <flux:dropdown position="bottom" align="end" class="max-md:hidden">
                    <button type="button" class="flex items-center gap-2.5 p-1 pe-2.5 rounded-xl border border-zinc-200/80 bg-zinc-50/60 hover:bg-zinc-100 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:bg-zinc-800 transition-all cursor-pointer shadow-2xs">
                        @if(auth()->user()?->profile_photo_url)
                            <img src="{{ auth()->user()->profile_photo_url }}" class="size-7.5 shrink-0 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700 shadow-xs" alt="{{ auth()->user()->name }}" />
                        @else
                            <div class="size-7.5 rounded-lg bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
                            </div>
                        @endif
                        <div class="text-left hidden lg:block leading-tight max-w-[130px]">
                            <div class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                {{ auth()->user()?->name ?? 'User' }}
                            </div>
                            <div class="text-[10px] text-zinc-400 truncate capitalize font-medium">
                                {{ auth()->user()?->roles->first()?->name ? str_replace('_', ' ', auth()->user()->roles->first()->name) : 'Pengguna' }}
                            </div>
                        </div>
                        <flux:icon name="chevron-down" class="size-3 text-zinc-400" />
                    </button>

                    <flux:menu class="w-60">
                        <div class="px-3.5 py-2.5 border-b border-zinc-100 dark:border-zinc-800">
                            <p class="text-xs text-zinc-400 font-medium">Masuk sebagai:</p>
                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ auth()->user()?->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate font-mono mt-0.5">{{ auth()->user()?->email }}</p>
                            @if(auth()->user()?->roles->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach(auth()->user()->roles as $r)
                                        <flux:badge size="xs" color="emerald" class="capitalize">
                                            {{ str_replace('_', ' ', $r->name) }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <flux:menu.item href="{{ route('dashboard') }}" icon="home">
                            Dashboard
                        </flux:menu.item>

                        <flux:menu.item href="{{ route('profile') }}" icon="user-circle">
                            Kelola Akun
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-600 dark:text-red-400">
                                Keluar (Logout)
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:header>

        {{-- Main Content (full width with mobile bottom clearance) --}}
        <flux:main class="p-4! sm:p-6! lg:px-8! pb-24! md:pb-6!">
            {{ $slot }}
        </flux:main>

        {{-- Mobile Bottom App Navigation Bar (Phone Only: hidden on md/lg tablet and desktop) --}}
        <nav class="mobile-bottom-nav fixed bottom-0 inset-x-0 z-30 md:hidden bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-zinc-200/80 dark:border-zinc-800 pb-safe select-none shadow-[0_-4px_20px_rgba(0,0,0,0.06)] transition-all duration-200">
            <div class="grid grid-cols-5 h-16 items-center px-1">
                {{-- 1. Beranda --}}
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('dashboard') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                    <flux:icon name="home" class="size-5.5 {{ request()->routeIs('dashboard') ? 'stroke-2' : '' }}" />
                    <span class="text-[10px] tracking-tight leading-none">Beranda</span>
                </a>

                {{-- 2. Presensi --}}
                @if(auth()->user()?->can('attendance.view_summary') || auth()->user()?->can('attendance.check_in_out') || auth()->user()?->can('attendance.view_own') || auth()->user()?->can('attendance.view_all'))
                    <a href="{{ route('attendance.index') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('attendance.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <flux:icon name="clock" class="size-5.5 {{ request()->routeIs('attendance.*') ? 'stroke-2' : '' }}" />
                        <span class="text-[10px] tracking-tight leading-none">Presensi</span>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-1 py-1 opacity-40 pointer-events-none">
                        <flux:icon name="clock" class="size-5.5" />
                        <span class="text-[10px] tracking-tight leading-none">Presensi</span>
                    </a>
                @endif

                {{-- 3. Cuti & Izin --}}
                @if(auth()->user()?->can('leave.view_own') || auth()->user()?->can('leave.create_own') || auth()->user()?->can('leave.view_all'))
                    <a href="{{ route('leaves.index') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('leaves.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <flux:icon name="calendar-days" class="size-5.5 {{ request()->routeIs('leaves.*') ? 'stroke-2' : '' }}" />
                        <span class="text-[10px] tracking-tight leading-none">Cuti</span>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-1 py-1 opacity-40 pointer-events-none">
                        <flux:icon name="calendar-days" class="size-5.5" />
                        <span class="text-[10px] tracking-tight leading-none">Cuti</span>
                    </a>
                @endif

                {{-- 4. Slip Gaji / Payroll / Jadwal --}}
                @if(auth()->user()?->employee)
                    <a href="{{ route('payroll.my-payslips') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('payroll.my-payslips') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <flux:icon name="receipt-percent" class="size-5.5 {{ request()->routeIs('payroll.my-payslips') ? 'stroke-2' : '' }}" />
                        <span class="text-[10px] tracking-tight leading-none">Slip Gaji</span>
                    </a>
                @elseif(auth()->user()?->can('payroll.view_all'))
                    <a href="{{ route('payroll.index') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('payroll.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <flux:icon name="banknotes" class="size-5.5 {{ request()->routeIs('payroll.*') ? 'stroke-2' : '' }}" />
                        <span class="text-[10px] tracking-tight leading-none">Payroll</span>
                    </a>
                @elseif(auth()->user()?->can('schedule.view'))
                    <a href="{{ route('schedules.index') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('schedules.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <flux:icon name="calendar" class="size-5.5 {{ request()->routeIs('schedules.*') ? 'stroke-2' : '' }}" />
                        <span class="text-[10px] tracking-tight leading-none">Jadwal</span>
                    </a>
                @else
                    <a href="{{ route('employees.index') }}" class="flex flex-col items-center justify-center gap-1 py-1 transition-colors {{ request()->routeIs('employees.*') ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                        <flux:icon name="users" class="size-5.5 {{ request()->routeIs('employees.*') ? 'stroke-2' : '' }}" />
                        <span class="text-[10px] tracking-tight leading-none">Pegawai</span>
                    </a>
                @endif

                {{-- 5. Menu drawer toggle --}}
                <button 
                    type="button" 
                    x-data 
                    x-on:click="$dispatch('flux-sidebar-toggle')" 
                    class="flex flex-col items-center justify-center gap-1 py-1 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                    aria-label="Buka Menu Lengkap"
                >
                    <flux:icon name="squares-2x2" class="size-5.5" />
                    <span class="text-[10px] tracking-tight leading-none">Menu</span>
                </button>
            </div>
        </nav>

        {{-- Add to Home Screen (A2HS) Banner Helper for Mobile & iPad browsers --}}
        <div 
            x-data="{
                showA2HS: false,
                isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
                showInstructions: false,
                init() {
                    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                    const dismissed = localStorage.getItem('juwita_a2hs_dismissed');
                    if (!isStandalone && !dismissed && (window.innerWidth < 1024)) {
                        setTimeout(() => { this.showA2HS = true; }, 1500);
                    }
                },
                dismiss() {
                    this.showA2HS = false;
                    localStorage.setItem('juwita_a2hs_dismissed', '1');
                }
            }"
            x-show="showA2HS"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
            class="fixed bottom-20 md:bottom-6 left-4 right-4 max-w-md mx-auto z-50 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border border-emerald-500/30 dark:border-emerald-500/20 rounded-2xl shadow-xl p-4 text-xs select-none"
            style="display: none;"
        >
            <div class="flex items-start gap-3">
                <div class="size-10 rounded-xl overflow-hidden shrink-0 border border-zinc-200 dark:border-zinc-700 bg-white p-1 shadow-2xs">
                    <img src="{{ asset('assets/icons/icon-192.png') }}" alt="SIM SDM" class="size-full object-contain">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm">Pasang SIM SDM di Layar Utama</p>
                    <p class="text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">
                        Akses cepat &amp; tampilan layar penuh seperti aplikasi mobile tanpa bilah browser.
                    </p>
                    
                    <div x-show="showInstructions" x-cloak class="mt-2.5 pt-2.5 border-t border-zinc-100 dark:border-zinc-800 text-zinc-600 dark:text-zinc-300 space-y-1.5 bg-zinc-50 dark:bg-zinc-800/60 p-2.5 rounded-lg">
                        <template x-if="isIOS">
                            <div class="space-y-1 text-[11px]">
                                <p class="font-medium text-emerald-700 dark:text-emerald-400">Cara pasang di iPhone / iPad (Safari):</p>
                                <p>1. Tekan tombol <strong>Share</strong> (ikon kotak dengan panah ke atas <flux:icon name="arrow-up-tray" class="inline size-3.5 text-zinc-600 dark:text-zinc-300" />) di bilah Safari.</p>
                                <p>2. Gulir ke bawah lalu pilih <strong>Add to Home Screen / Tambah ke Layar Utama</strong>.</p>
                            </div>
                        </template>
                        <template x-if="!isIOS">
                            <div class="space-y-1 text-[11px]">
                                <p class="font-medium text-emerald-700 dark:text-emerald-400">Cara pasang di Android (Chrome):</p>
                                <p>1. Tekan menu titik tiga (<flux:icon name="ellipsis-vertical" class="inline size-3.5 text-zinc-600 dark:text-zinc-300" />) di sudut kanan atas.</p>
                                <p>2. Pilih <strong>Instal aplikasi / Tambahkan ke Layar Utama</strong>.</p>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-2 mt-3">
                        <button 
                            type="button" 
                            x-on:click="showInstructions = !showInstructions"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-xs transition-colors cursor-pointer"
                        >
                            <span x-text="showInstructions ? 'Tutup Petunjuk' : 'Lihat Cara Pasang'"></span>
                        </button>
                        <button 
                            type="button" 
                            x-on:click="dismiss()"
                            class="px-2.5 py-1.5 rounded-lg text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                        >
                            Nanti saja
                        </button>
                    </div>
                </div>
                <button 
                    type="button" 
                    x-on:click="dismiss()"
                    class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 -mr-1 -mt-1 cursor-pointer"
                    aria-label="Tutup"
                >
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        </div>

        {{-- Desktop & Global Command Palette (⌘K) for Instant Navigation --}}
        @php
            $cmdCanMaster = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll') || auth()->user()?->hasPermission('system.config_view');
            $cmdCanEmp = auth()->user()?->can('employee.view');
            $cmdCanDoc = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->can('doctor.manage_credentials');
            $cmdCanOrg = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager') || auth()->user()?->can('org.create') || auth()->user()?->can('org.update');
            $cmdCanPayroll = auth()->user()?->can('payroll.view_all');
            $cmdCanDocs = auth()->user()?->can('document.view_own') || auth()->user()?->can('document.view_all');
            $cmdCanReports = $canSeeReports ?? false;
            $cmdCanAudit = auth()->user()?->can('system.audit_view');
            $cmdCanSettings = auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasPermission('system.config_view');
        @endphp

        <div 
            x-data="{
                open: false,
                query: '',
                items: [
                    { name: 'Dashboard', category: 'Navigasi', url: '{{ route('dashboard') }}', keywords: 'beranda home dashboard ringkasan' },
                    { name: 'Pusat Notifikasi & Pemberitahuan', category: 'Navigasi', url: '{{ route('notifications.index') }}', keywords: 'notifikasi pemberitahuan peringatan alert pengumuman pesan tindakan' },
                    @if(auth()->user()?->can('attendance.view_summary') || auth()->user()?->can('attendance.check_in_out') || auth()->user()?->can('attendance.view_own') || auth()->user()?->can('attendance.view_all'))
                        { name: 'Presensi & Kehadiran', category: 'Waktu', url: '{{ route('attendance.index') }}', keywords: 'presensi absen check-in check-out kehadiran clock in' },
                    @endif
                    { name: 'Profil Pengguna / Kelola Akun', category: 'Akun', url: '{{ route('profile') }}', keywords: 'profil saya detail profil akun biodata identitas user kelola akun' },
                    @can('schedule.view')
                        { name: 'Jadwal & Shift Roster', category: 'Waktu', url: '{{ route('schedules.index') }}', keywords: 'jadwal shift roster kalender dinas kerja' },
                    @endcan
                    @if(auth()->user()?->can('leave.view_own') || auth()->user()?->can('leave.create_own') || auth()->user()?->can('leave.view_all'))
                        { name: 'Pengajuan Cuti & Izin', category: 'Waktu', url: '{{ route('leaves.index') }}', keywords: 'cuti izin permohonan sakit cuti tahunan approval' },
                    @endif
                    @if(auth()->user()?->employee)
                        { name: 'Slip Gaji', category: 'Keuangan', url: '{{ route('payroll.my-payslips') }}', keywords: 'slip gaji take home pay transfer gaji penghasilan' },
                    @endif
                    @if($cmdCanPayroll)
                        { name: 'Penggajian (Payroll)', category: 'Keuangan', url: '{{ route('payroll.index') }}', keywords: 'payroll gaji perhitungan run salary kompensasi' },
                    @endif
                    @if($cmdCanEmp)
                        { name: 'Pegawai & Staf', category: 'SDM', url: '{{ route('employees.index') }}', keywords: 'pegawai staf karyawan sdm direktori nik nip' },
                    @endif
                    @if($cmdCanDoc)
                        { name: 'Tenaga Medis & Dokter', category: 'SDM', url: '{{ route('doctors.index') }}', keywords: 'dokter spesialis str sip kredensial medis tarif' },
                    @endif
                    @if($cmdCanOrg)
                        { name: 'Struktur Organisasi', category: 'SDM', url: '{{ route('org.structure') }}', keywords: 'organisasi departemen hierarki direksi bagan' },
                    @endif
                    @if($cmdCanDocs)
                        { name: 'Arsip Dokumen', category: 'Dokumen', url: '{{ route('documents.index') }}', keywords: 'dokumen berkas ktp kk ijazah str sip kontrak spk' },
                    @endif
                    @if($cmdCanReports)
                        @if($canSeeEmployeeReport)
                            { name: 'Laporan Kepegawaian', category: 'Laporan', url: '{{ route('reports.employees') }}', keywords: 'laporan pegawai staf demografi' },
                        @endif
                        @if($canSeeAttendanceReport)
                            { name: 'Laporan Kehadiran', category: 'Laporan', url: '{{ route('reports.attendance') }}', keywords: 'laporan presensi kehadiran terlambat' },
                        @endif
                        @if($canSeeLeaveReport)
                            { name: 'Laporan Cuti & Izin', category: 'Laporan', url: '{{ route('reports.leaves') }}', keywords: 'laporan cuti saldo cuti izin' },
                        @endif
                        @if($canSeePayrollReport)
                            { name: 'Laporan Payroll', category: 'Laporan', url: '{{ route('reports.payroll') }}', keywords: 'laporan penggajian rekapitulasi gaji' },
                        @endif
                        @if($canSeePph21Report)
                            { name: 'Laporan PPh 21', category: 'Laporan', url: '{{ route('reports.pph21') }}', keywords: 'pajak pph21 ter bukti potong' },
                        @endif
                        @if($canSeeDoctorReport)
                            { name: 'Laporan Dokter', category: 'Laporan', url: '{{ route('reports.doctors') }}', keywords: 'laporan dokter str sip' },
                        @endif
                    @endif
                    @if($cmdCanMaster)
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin'))
                            { name: 'Unit Kerja', category: 'Master', url: '{{ route('master.units') }}', keywords: 'unit kerja instalasi ruangan bagian' },
                            { name: 'Standar Jabatan', category: 'Master', url: '{{ route('master.positions') }}', keywords: 'jabatan posisi grade level' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->can('schedule.manage_shifts'))
                            { name: 'Shift Kerja', category: 'Master', url: '{{ route('master.shifts') }}', keywords: 'shift pagi sore malam' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->can('leave.manage_policy'))
                            { name: 'Jenis Cuti', category: 'Master', url: '{{ route('master.leave-types') }}', keywords: 'jenis cuti kuota tahunan' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('hr_manager'))
                            { name: 'Tingkat & Jenis SP', category: 'Master', url: '{{ route('master.warning-letter-types') }}', keywords: 'surat peringatan sp 1 2 3 sanksi disiplin' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->can('system.config_view'))
                            { name: 'Titik Geofence', category: 'Master', url: '{{ route('master.geofences') }}', keywords: 'geofence radius gps koordinat' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll') || auth()->user()?->can('payroll.calculate'))
                            { name: 'Periode Payroll', category: 'Master', url: '{{ route('master.payroll-periods') }}', keywords: 'periode payroll cutoff' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll'))
                            { name: 'Komponen Penghasilan', category: 'Master', url: '{{ route('master.earnings') }}', keywords: 'gaji pokok tunjangan penghasilan' },
                            { name: 'Komponen Potongan', category: 'Master', url: '{{ route('master.deductions') }}', keywords: 'potongan kas bon pph21 bpjs' },
                        @endif
                        @if(auth()->user()?->hasRole('super_admin') || auth()->user()?->hasRole('hr_admin') || auth()->user()?->hasRole('payroll') || auth()->user()?->can('statutory.view'))
                            { name: 'Regulasi BPJS', category: 'Master', url: '{{ route('master.bpjs') }}', keywords: 'bpjs kesehatan ketenagakerjaan' },
                            { name: 'Parameter PPh 21', category: 'Master', url: '{{ route('master.pph21') }}', keywords: 'pph 21 ptkp tarif ter' },
                        @endif
                    @endif
                    @if($cmdCanAudit)
                        { name: 'Audit Trail', category: 'Sistem', url: '{{ route('audit-logs.index') }}', keywords: 'audit log rekaman jejak user' },
                    @endif
                    @if($cmdCanSettings)
                        { name: 'Pengaturan Sistem', category: 'Sistem', url: '{{ route('system.settings') }}', keywords: 'pengaturan konfigurasi aplikasi' },
                    @endif
                    @if(auth()->user()?->isSuperAdmin() || auth()->user()?->hasRole('super_admin'))
                        { name: 'Penyimpanan Cloud (Google Drive)', category: 'Sistem', url: '{{ route('system.google-drive-storage') }}', keywords: 'google drive cloud storage kapasitas sisa kuota folder juwita-storage' },
                    @endif
                ],
                get filteredItems() {
                    if (!this.query.trim()) return this.items;
                    const q = this.query.toLowerCase().trim();
                    return this.items.filter(item => 
                        item.name.toLowerCase().includes(q) || 
                        item.category.toLowerCase().includes(q) || 
                        item.keywords.toLowerCase().includes(q)
                    );
                }
            }"
            x-on:open-command-palette.window="open = true; $nextTick(() => $refs.commandInput.focus())"
            x-on:keydown.window.prevent.cmd.k="open = true; $nextTick(() => $refs.commandInput.focus())"
            x-on:keydown.window.prevent.ctrl.k="open = true; $nextTick(() => $refs.commandInput.focus())"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20"
            role="dialog"
            aria-modal="true"
        >
            {{-- Backdrop --}}
            <div 
                x-show="open" 
                x-transition:enter="ease-out duration-200" 
                x-transition:enter-start="opacity-0" 
                x-transition:enter-end="opacity-100" 
                x-transition:leave="ease-in duration-150" 
                x-transition:leave-start="opacity-100" 
                x-transition:leave-end="opacity-0" 
                x-on:click="open = false" 
                class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity"
            ></div>

            {{-- Modal Panel --}}
            <div 
                x-show="open" 
                x-transition:enter="ease-out duration-200" 
                x-transition:enter-start="opacity-0 scale-95" 
                x-transition:enter-end="opacity-100 scale-100" 
                x-transition:leave="ease-in duration-150" 
                x-transition:leave-start="opacity-100 scale-100" 
                x-transition:leave-end="opacity-0 scale-95" 
                x-on:keydown.escape.window="open = false" 
                class="relative mx-auto max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800"
            >
                {{-- Search Header --}}
                <div class="relative border-b border-zinc-200 dark:border-zinc-800 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <flux:icon name="magnifying-glass" class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <input 
                            x-ref="commandInput"
                            x-model="query" 
                            type="text" 
                            placeholder="Ketik nama menu, modul, atau halaman..." 
                            class="w-full bg-transparent text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-hidden dark:text-zinc-100 font-medium"
                        />
                        <button 
                            type="button" 
                            x-on:click="open = false" 
                            class="rounded-lg p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                        >
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono border border-zinc-200 dark:border-zinc-700 rounded bg-zinc-50 dark:bg-zinc-800">ESC</kbd>
                        </button>
                    </div>
                </div>

                {{-- Results List --}}
                <div class="max-h-80 overflow-y-auto p-2">
                    <template x-if="filteredItems.length > 0">
                        <div class="space-y-1">
                            <template x-for="(item, idx) in filteredItems" :key="idx">
                                <a 
                                    :href="item.url" 
                                    class="group flex items-center justify-between rounded-xl px-3 py-2 text-xs transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-zinc-800 dark:text-zinc-200 hover:text-emerald-900 dark:hover:text-emerald-300"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 group-hover:bg-emerald-600 group-hover:text-white dark:bg-zinc-800 dark:text-zinc-400 transition-colors">
                                            <flux:icon name="arrow-top-right-on-square" class="size-3.5" />
                                        </div>
                                        <span class="font-semibold text-sm truncate" x-text="item.name"></span>
                                    </div>
                                    <span 
                                        class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/60 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 shrink-0" 
                                        x-text="item.category"
                                    ></span>
                                </a>
                            </template>
                        </div>
                    </template>
                    <template x-if="filteredItems.length === 0">
                        <div class="py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="magnifying-glass" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600 mb-2" />
                            <p>Tidak ada modul yang cocok dengan "<span class="font-semibold text-zinc-700 dark:text-zinc-300" x-text="query"></span>"</p>
                        </div>
                    </template>
                </div>

                {{-- Footer Hints --}}
                <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/60 px-4 py-2 text-[11px] text-zinc-400">
                    <span>Tekan <kbd class="px-1.5 py-0.5 rounded font-mono border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">Enter</kbd> untuk membuka</span>
                    <span>Pencarian Cepat SIM SDM RS Permata Bunda</span>
                </div>
            </div>
        </div>

        {{-- Flash Session Alerts via SweetAlert2 --}}
        @if (session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    @if (session()->has('success'))
                        window.swalToast?.(@json(session('success')), 'success');
                    @endif
                    @if (session()->has('error'))
                        window.swalToast?.(@json(session('error')), 'danger');
                    @endif
                    @if (session()->has('warning'))
                        window.swalToast?.(@json(session('warning')), 'warning');
                    @endif
                    @if (session()->has('info'))
                        window.swalToast?.(@json(session('info')), 'info');
                    @endif
                });
            </script>
        @endif

        @fluxScripts
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

        <title>{{ $title ?? config('app.name', 'SIM SDM RS Juwita') }}</title>

        <!-- Web App Manifest & Mobile Capabilities -->
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="RS Juwita">
        <link rel="apple-touch-icon" href="{{ asset('assets/icons/apple-touch-icon.png') }}">
        <meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

        <link rel="icon" type="image/png" href="{{ asset('assets/icons/icon-192.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 antialiased selection:bg-emerald-500/20">
        {{-- Collapsible Sidebar according to Flux UI official demos --}}
        <flux:sidebar sticky collapsible class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 w-72 sm:w-80 max-w-[85vw] lg:w-64 max-lg:shadow-2xl pt-safe">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-1 min-w-0">
                    <img 
                        src="{{ asset('assets/logo-rs-juwita.png') }}" 
                        alt="SIM SDM RS Juwita" 
                        class="h-8.5 w-auto max-w-[160px] object-contain in-data-flux-sidebar-collapsed-desktop:hidden" 
                    />
                    <div class="size-8 rounded-lg overflow-hidden shrink-0 hidden in-data-flux-sidebar-collapsed-desktop:flex items-center justify-center bg-white dark:bg-zinc-800 p-0.5 border border-zinc-200 dark:border-zinc-700 shadow-2xs">
                        <img 
                            src="{{ asset('assets/logo-rs-juwita.png') }}" 
                            alt="SIM SDM RS Juwita" 
                            class="w-full h-full object-contain" 
                        />
                    </div>
                </a>

                <flux:sidebar.collapse class="max-lg:hidden in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
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
                @endphp

                @if($canSeeAudit || $canSeeSettings)
                    <flux:sidebar.group heading="ADMINISTRASI & SISTEM" class="mt-3">
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
                <div class="px-1 py-1.5 border-t border-zinc-200 dark:border-zinc-800 pb-safe max-lg:pb-5">
                    <flux:dropdown position="top" align="start" class="w-full">
                        <flux:profile 
                            name="{{ auth()->user()?->name ?? 'User' }}" 
                            initials="{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}"
                            class="w-full justify-start cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800/50 rounded-lg p-1.5"
                        />

                        <flux:menu class="w-60">
                            <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <p class="text-xs text-zinc-400 font-medium">Masuk sebagai:</p>
                                <p class="text-sm font-semibold truncate">{{ auth()->user()?->email }}</p>
                                @if(auth()->user()?->roles->isNotEmpty())
                                    <flux:badge size="sm" color="emerald" class="mt-1">
                                        {{ auth()->user()->roles->pluck('name')->join(', ') }}
                                    </flux:badge>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-600 dark:text-red-400">
                                    Keluar (Logout)
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{-- Top App Header --}}
        <flux:header sticky class="border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95 pt-safe">
            <flux:sidebar.toggle class="me-3 lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 lg:hidden me-3">
                <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="SIM SDM RS Juwita" class="h-7 w-auto max-w-[110px] object-contain" />
            </a>

            <flux:breadcrumbs class="max-sm:hidden">
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">SIM SDM RS Juwita</flux:breadcrumbs.item>
                @if(isset($title))
                    <flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item>
                @endif
            </flux:breadcrumbs>

            <flux:spacer />

            <flux:button
                x-data
                x-on:click="$flux.dark = ! $flux.dark"
                variant="subtle"
                size="sm"
                icon="moon"
                class="me-2"
                aria-label="Ganti mode gelap"
            />

            <flux:dropdown position="bottom" align="end" class="max-md:hidden">
                <flux:profile 
                    name="{{ auth()->user()?->name ?? 'User' }}" 
                    initials="{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}"
                    class="cursor-pointer"
                />

                <flux:menu class="w-56">
                    <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                        <p class="text-xs text-zinc-400 font-medium">Masuk sebagai:</p>
                        <p class="text-sm font-semibold truncate">{{ auth()->user()?->email }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-600 dark:text-red-400">
                            Keluar (Logout)
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Main Content (full width with mobile bottom clearance) --}}
        <flux:main class="p-4! sm:p-6! lg:px-8! pb-24! md:pb-6!">
            {{ $slot }}
        </flux:main>

        {{-- Mobile Bottom App Navigation Bar (Phone Only: hidden on md/lg tablet and desktop) --}}
        <nav class="fixed bottom-0 inset-x-0 z-40 md:hidden bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-t border-zinc-200/80 dark:border-zinc-800 pb-safe select-none shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
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

        @fluxScripts
    </body>
</html>

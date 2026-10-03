<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name', 'RS Juwita') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

        <link rel="icon" type="image/png" href="{{ asset('assets/logo-rs-juwita.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 antialiased">
        {{-- Collapsible Sidebar according to Flux UI official demos --}}
        <flux:sidebar sticky collapsible class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-1 min-w-0">
                    <img 
                        src="{{ asset('assets/logo-rs-juwita.png') }}" 
                        alt="RS Juwita" 
                        class="h-8.5 w-auto max-w-[160px] object-contain in-data-flux-sidebar-collapsed-desktop:hidden" 
                    />
                    <div class="size-8 rounded-lg overflow-hidden shrink-0 hidden in-data-flux-sidebar-collapsed-desktop:flex items-center justify-center bg-white dark:bg-zinc-800 p-0.5 border border-zinc-200 dark:border-zinc-700 shadow-2xs">
                        <img 
                            src="{{ asset('assets/logo-rs-juwita.png') }}" 
                            alt="RS Juwita" 
                            class="w-full h-full object-contain" 
                        />
                    </div>
                </a>

                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
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
                            Presensi & Geofence
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
                                Slip Gaji Saya
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
                <div class="px-1 py-1.5 border-t border-zinc-200 dark:border-zinc-800">
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
        <flux:header sticky class="border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/90">
            <flux:sidebar.toggle class="me-3 lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 lg:hidden me-3">
                <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="RS Juwita" class="h-7 w-auto max-w-[110px] object-contain" />
            </a>

            <flux:breadcrumbs class="max-sm:hidden">
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">RS Juwita</flux:breadcrumbs.item>
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

            <flux:dropdown position="bottom" align="end">
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

        {{-- Main Content (full width) --}}
        <flux:main class="p-4! sm:p-6! lg:px-8!">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>

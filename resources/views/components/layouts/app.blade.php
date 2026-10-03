<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-full bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 antialiased">
        <flux:sidebar sticky stashable class="bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <div class="flex items-center gap-3 px-3 py-2">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-600 text-white font-bold shadow-md shadow-emerald-500/20">
                    <flux:icon name="building-office-2" class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-base leading-tight tracking-tight text-zinc-900 dark:text-zinc-100">RS Juwita</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Portal SDM & Medis</span>
                </div>
            </div>

            <flux:navlist variant="outline">
                <flux:navlist.item icon="home" href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')">
                    Dashboard
                </flux:navlist.item>

                <flux:navlist.group heading="Kepegawaian & Medis" class="mt-4">
                    @if(auth()->user()?->can('employee.view'))
                        <flux:navlist.item icon="users" href="{{ route('employees.index') }}" :current="request()->routeIs('employees.*')">
                            Pegawai & Staf
                        </flux:navlist.item>
                    @endif

                    @if(auth()->user()?->can('doctor.view'))
                        <flux:navlist.item icon="user-group" href="{{ route('doctors.index') }}" :current="request()->routeIs('doctors.*')">
                            Tenaga Medis & Dokter
                        </flux:navlist.item>
                    @endif

                    @can('org.view')
                        <flux:navlist.item icon="building-office" href="{{ route('dashboard') }}">
                            Struktur Organisasi
                        </flux:navlist.item>
                    @endcan
                </flux:navlist.group>

                <flux:navlist.group heading="Waktu & Operasional" class="mt-4">
                    @can('schedule.view')
                        <flux:navlist.item icon="calendar" href="{{ route('schedules.index') }}" :current="request()->routeIs('schedules.*')">
                            Jadwal & Shift Roster
                        </flux:navlist.item>
                    @endcan

                    @if(auth()->user()?->can('attendance.view_summary') || auth()->user()?->can('attendance.check_in_out'))
                        <flux:navlist.item icon="clock" href="{{ route('attendance.index') }}" :current="request()->routeIs('attendance.*')">
                            Presensi & Geofence
                        </flux:navlist.item>
                    @endif

                    @if(auth()->user()?->can('leave.view_requests') || auth()->user()?->can('leave.apply'))
                        <flux:navlist.item icon="document-text" href="{{ route('dashboard') }}">
                            Pengajuan Cuti & Izin
                        </flux:navlist.item>
                    @endif
                </flux:navlist.group>

                <flux:navlist.group heading="Keuangan & Dokumen" class="mt-4">
                    @if(auth()->user()?->can('payroll.view_run') || auth()->user()?->can('payroll.view_payslip_own'))
                        <flux:navlist.item icon="banknotes" href="{{ route('dashboard') }}">
                            Penggajian (Payroll)
                        </flux:navlist.item>
                    @endif

                    @can('document.view_metadata')
                        <flux:navlist.item icon="folder" href="{{ route('dashboard') }}">
                            Arsip Dokumen Kepatuhan
                        </flux:navlist.item>
                    @endcan
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:dropdown position="top" align="start" class="w-full">
                    <flux:profile 
                        name="{{ auth()->user()?->name ?? 'User' }}" 
                        initials="{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}"
                        class="w-full justify-start cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800/50 rounded-lg p-1.5"
                    />

                    <flux:menu class="w-56">
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
        </flux:sidebar>

        <flux:header class="lg:hidden bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium">{{ auth()->user()?->name }}</span>
            </div>
        </flux:header>

        <flux:main class="p-6 lg:p-8 max-w-7xl mx-auto">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>

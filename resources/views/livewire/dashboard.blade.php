<div class="space-y-8">
    {{-- Header Section --}}
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
                    <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                        Rp {{ number_format((float) ($payroll['total_gross_pay'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium text-zinc-500">Total Potongan & Pajak</div>
                    <div class="text-lg font-bold text-red-600">
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
                    <div class="text-xl font-extrabold text-emerald-600">
                        Rp {{ number_format((float) ($payroll['total_net_pay'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </flux:card>
        </div>
    @endif
</div>

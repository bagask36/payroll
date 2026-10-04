@php
    $user = auth()->user();
    $alerts = collect();

    if ($user) {
        // 1. Pending Leave Requests Alert
        try {
            if ($user->hasRole('supervisor') && $user->employee?->unit_id) {
                $pendingCount = \App\Models\LeaveRequest::where('status', 'pending_tier1')
                    ->whereHas('employee', fn($q) => $q->where('unit_id', $user->employee->unit_id))
                    ->count();
                if ($pendingCount > 0) {
                    $alerts->push([
                        'id' => 'pending_tier1',
                        'type' => 'warning',
                        'title' => 'Verifikasi Cuti Staf (Tier 1)',
                        'message' => "Ada {$pendingCount} pengajuan cuti staf unit Anda yang menunggu persetujuan supervisor.",
                        'time' => 'Perlu tindakan',
                        'url' => route('leaves.index'),
                        'icon' => 'document-magnifying-glass',
                        'badge' => "{$pendingCount} Pengajuan",
                        'badge_color' => 'amber',
                    ]);
                }
            } elseif ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager')) {
                $pendingCount = \App\Models\LeaveRequest::whereIn('status', ['pending_tier1', 'pending_tier2'])->count();
                if ($pendingCount > 0) {
                    $alerts->push([
                        'id' => 'pending_leaves_admin',
                        'type' => 'warning',
                        'title' => 'Antrean Persetujuan Cuti',
                        'message' => "Ada {$pendingCount} pengajuan cuti karyawan menunggu verifikasi HR / Manajemen.",
                        'time' => 'Perlu tindakan',
                        'url' => route('leaves.index'),
                        'icon' => 'document-magnifying-glass',
                        'badge' => "{$pendingCount} Antrean",
                        'badge_color' => 'amber',
                    ]);
                }
            } elseif ($user->employee) {
                $myPendingLeaves = \App\Models\LeaveRequest::where('employee_id', $user->employee->id)
                    ->whereIn('status', ['pending_tier1', 'pending_tier2'])
                    ->count();
                if ($myPendingLeaves > 0) {
                    $alerts->push([
                        'id' => 'my_pending_leaves',
                        'type' => 'info',
                        'title' => 'Pengajuan Cuti Dalam Proses',
                        'message' => "Anda memiliki {$myPendingLeaves} pengajuan cuti yang sedang dalam proses verifikasi berjenjang.",
                        'time' => 'Diproses',
                        'url' => route('leaves.index'),
                        'icon' => 'calendar',
                        'badge' => 'Menunggu',
                        'badge_color' => 'blue',
                    ]);
                }
            }
        } catch (\Throwable $e) {}

        // 2. Doctor Credential Alerts (STR / SIP Expiring)
        try {
            if ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager')) {
                $expiringDoctors = \App\Models\Doctor::where('status', 'active')
                    ->where(function ($q) {
                        $q->where('str_valid_until', '<=', now()->addDays(30))
                          ->orWhere('sip_valid_until', '<=', now()->addDays(30));
                    })->count();
                if ($expiringDoctors > 0) {
                    $alerts->push([
                        'id' => 'doctor_credentials',
                        'type' => 'danger',
                        'title' => 'Kepatuhan Izin Praktik Medis',
                        'message' => "{$expiringDoctors} dokter memiliki masa berlaku STR / SIP yang mendekati atau telah habis.",
                        'time' => 'Penting',
                        'url' => route('doctors.index'),
                        'icon' => 'exclamation-triangle',
                        'badge' => "{$expiringDoctors} Dokter",
                        'badge_color' => 'rose',
                    ]);
                }
            } elseif ($user->hasRole('doctor')) {
                $myDoc = \App\Models\Doctor::where('user_id', $user->id)->first();
                if ($myDoc) {
                    $strExp = $myDoc->str_valid_until && \Carbon\Carbon::parse($myDoc->str_valid_until)->diffInDays(now(), false) >= -30;
                    $sipExp = $myDoc->sip_valid_until && \Carbon\Carbon::parse($myDoc->sip_valid_until)->diffInDays(now(), false) >= -30;
                    if ($strExp || $sipExp) {
                        $alerts->push([
                            'id' => 'my_credentials',
                            'type' => 'danger',
                            'title' => 'Peringatan STR / SIP Dokter',
                            'message' => 'Masa berlaku izin praktik (STR/SIP) Anda mendekati batas akhir. Harap serahkan pembaruan berkas ke HR.',
                            'time' => 'Wajib',
                            'url' => route('doctors.index'),
                            'icon' => 'exclamation-triangle',
                            'badge' => 'Segera Perbarui',
                            'badge_color' => 'rose',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 3. Attendance Check-in Reminder
        try {
            if ($user->employee) {
                $checkedIn = \App\Models\Attendance::where('employee_id', $user->employee->id)
                    ->whereDate('date', now()->toDateString())
                    ->whereNotNull('clock_in')
                    ->exists();
                if (!$checkedIn && now()->hour >= 7) {
                    $alerts->push([
                        'id' => 'attendance_reminder',
                        'type' => 'info',
                        'title' => 'Presensi Hari Ini Belum Masuk',
                        'message' => 'Anda belum melakukan clock-in hari ini. Lakukan presensi saat berada dalam radius geofence rumah sakit.',
                        'time' => 'Hari ini',
                        'url' => route('attendance.index'),
                        'icon' => 'clock',
                        'badge' => 'Presensi Masuk',
                        'badge_color' => 'blue',
                    ]);
                }
            }
        } catch (\Throwable $e) {}

        // 4. Payroll finalized slip notification
        try {
            if ($user->employee) {
                $latestRun = \App\Models\PayrollRun::where('status', 'finalized')->latest('run_date')->first();
                if ($latestRun && $latestRun->created_at->diffInDays(now()) <= 14) {
                    $alerts->push([
                        'id' => 'payslip_ready',
                        'type' => 'success',
                        'title' => 'Slip Gaji Diterbitkan',
                        'message' => "Slip gaji periode {$latestRun->period?->name} telah difinalisasi dan dapat Anda unduh secara aman.",
                        'time' => 'Terbit',
                        'url' => route('payroll.my-payslips'),
                        'icon' => 'receipt-percent',
                        'badge' => 'Slip Gaji',
                        'badge_color' => 'emerald',
                    ]);
                }
            }
        } catch (\Throwable $e) {}

        // 5. System operational status
        $alerts->push([
            'id' => 'system_status',
            'type' => 'neutral',
            'title' => 'Sistem RS Juwita Aktif',
            'message' => 'Layanan geofence, validasi shift medis, dan sinkronisasi presensi operasional normal.',
            'time' => now()->translatedFormat('H:i') . ' WIB',
            'url' => route('dashboard'),
            'icon' => 'shield-check',
            'badge' => 'Siaga 24 Jam',
            'badge_color' => 'emerald',
        ]);
    }

    $urgentCount = $alerts->where('type', '!=', 'neutral')->count();
@endphp

<div 
    x-data="{ 
        open: false,
        dismissed: false
    }" 
    class="relative"
    @keydown.escape.window="open = false"
>
    {{-- Bell Trigger Button --}}
    <button 
        type="button" 
        @click="open = !open" 
        :aria-expanded="open"
        title="Pusat Peringatan & Pemberitahuan"
        class="relative flex items-center justify-center size-8.5 rounded-xl border border-zinc-200/80 bg-zinc-50/80 hover:bg-zinc-100 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-800/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-all cursor-pointer shadow-2xs group focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30"
    >
        <flux:icon name="bell" class="size-4 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" />

        @if($urgentCount > 0)
            <span class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-mono font-bold text-white shadow-xs ring-2 ring-white dark:ring-zinc-900 animate-pulse">
                {{ $urgentCount }}
            </span>
        @else
            <span class="absolute top-1.5 right-1.5 size-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"></span>
        @endif
    </button>

    {{-- Mobile Backdrop Overlay (dims the screen nicely on phone) --}}
    <div 
        x-show="open" 
        x-cloak
        @click="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/40 backdrop-blur-xs z-40 sm:hidden"
        style="display: none;"
    ></div>

    {{-- Dropdown Alert Popover Panel --}}
    <div 
        x-show="open" 
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="fixed inset-x-3.5 top-17 z-50 max-h-[82vh] sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2.5 sm:w-96 sm:max-w-md rounded-3xl border border-zinc-200/90 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden flex flex-col"
        style="display: none;"
    >
        {{-- Popover Header --}}
        <div class="relative shrink-0 overflow-hidden border-b border-zinc-100 p-4 sm:p-5 dark:border-zinc-800 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                        <flux:icon name="bell" class="size-4.5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-zinc-900 dark:text-zinc-100 truncate">
                            Pemberitahuan &amp; Peringatan
                        </h4>
                        <p class="text-[11px] text-zinc-400 mt-0.5 truncate">Aktivitas &amp; tugas penting SIM SDM</p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    @if($urgentCount > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/40">
                            <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $urgentCount }} Tindakan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            Semua Beres
                        </span>
                    @endif

                    {{-- Close Button on Mobile --}}
                    <button 
                        type="button" 
                        @click="open = false" 
                        class="flex sm:hidden size-8 items-center justify-center rounded-xl text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                        aria-label="Tutup"
                    >
                        <flux:icon name="x-mark" class="size-4.5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Alerts Scrollable List --}}
        <div class="flex-1 overflow-y-auto overscroll-contain divide-y divide-zinc-100 dark:divide-zinc-800/80">
            @forelse($alerts as $alert)
                <a 
                    href="{{ $alert['url'] }}" 
                    @click="open = false"
                    class="group flex items-start gap-3 p-3.5 sm:p-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50 active:bg-zinc-100 dark:active:bg-zinc-800"
                >
                    {{-- Alert Icon Pill --}}
                    @php
                        $iconStyle = match($alert['type']) {
                            'danger' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
                            'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
                            'info' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
                            'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                            default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                        };
                    @endphp
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-2xl {{ $iconStyle }} shadow-2xs group-hover:scale-105 transition-transform mt-0.5">
                        <flux:icon :name="$alert['icon']" class="size-4.5" />
                    </div>

                    {{-- Alert Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                {{ $alert['title'] }}
                            </span>
                            <flux:badge size="xs" :color="$alert['badge_color']" class="shrink-0 font-bold text-[10px]">
                                {{ $alert['badge'] }}
                            </flux:badge>
                        </div>
                        <p class="mt-1 text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed line-clamp-2">
                            {{ $alert['message'] }}
                        </p>
                        <div class="mt-2 flex items-center justify-between text-[10px] sm:text-[11px] text-zinc-400">
                            <span class="font-medium">{{ $alert['time'] }}</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400 group-hover:underline">Buka &rarr;</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center">
                    <flux:icon name="check-circle" class="mx-auto size-10 text-emerald-500" />
                    <p class="mt-2 text-xs font-bold text-zinc-800 dark:text-zinc-200">Tidak ada peringatan baru</p>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Seluruh administrasi dan permohonan telah terselesaikan.</p>
                </div>
            @endforelse
        </div>

        {{-- Popover Footer --}}
        <div class="shrink-0 border-t border-zinc-100 bg-zinc-50/80 p-3 text-center dark:border-zinc-800 dark:bg-zinc-850">
            <a 
                href="{{ route('leaves.index') }}" 
                @click="open = false"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
            >
                <span>Kelola Pengajuan &amp; Notifikasi Lengkap</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
</div>

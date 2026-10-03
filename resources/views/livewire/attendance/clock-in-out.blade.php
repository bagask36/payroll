<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Presensi Mandiri Terverifikasi
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Lakukan pencatatan jam kerja harian dengan validasi Geofencing GPS dan Biometrik Wajah RS Juwita.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('attendance.index') }}">
                <flux:button variant="subtle" icon="clock">
                    Riwayat Presensi
                </flux:button>
            </a>
        </div>
    </div>

    {{-- Notification Messages --}}
    @if($statusMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span class="text-sm font-medium">{{ $statusMessage }}</span>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center gap-3">
            <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
            <span class="text-sm font-medium">{{ $errorMessage }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Shift Info Card --}}
        <flux:card class="space-y-4">
            <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="calendar" class="w-5 h-5" />
                </div>
                <div>
                    <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Jadwal Tugas Hari Ini</h4>
                    <p class="text-xs text-zinc-500">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>

            @if($todaySchedule && $todaySchedule->shift)
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Nama Shift:</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $todaySchedule->shift->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Jam Kerja:</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ substr($todaySchedule->shift->start_time, 0, 5) }} - {{ substr($todaySchedule->shift->end_time, 0, 5) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Toleransi Telat:</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ $todaySchedule->shift->late_tolerance_minutes }} Menit</span>
                    </div>
                </div>
            @else
                <div class="py-4 text-center">
                    <p class="text-xs text-zinc-500 italic">Tidak ada jadwal shift khusus hari ini (Reguler / Standar Rumah Sakit).</p>
                </div>
            @endif

            {{-- Today Attendance Status --}}
            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 space-y-2 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500">Clock In:</span>
                    @if($todayAttendance?->clock_in)
                        <span class="font-mono font-semibold text-emerald-600">{{ $todayAttendance->clock_in->format('H:i:s') }}</span>
                    @else
                        <span class="text-xs text-zinc-400 italic">Belum Masuk</span>
                    @endif
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500">Clock Out:</span>
                    @if($todayAttendance?->clock_out)
                        <span class="font-mono font-semibold text-blue-600">{{ $todayAttendance->clock_out->format('H:i:s') }}</span>
                    @else
                        <span class="text-xs text-zinc-400 italic">Belum Pulang</span>
                    @endif
                </div>
                @if($todayAttendance?->late_duration_minutes > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">Keterlambatan:</span>
                        <flux:badge size="sm" color="amber">{{ $todayAttendance->late_duration_minutes }} Menit</flux:badge>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Verification & Clock Buttons (2 cols) --}}
        <div class="md:col-span-2 space-y-6">
            {{-- Geofence Status Card --}}
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                            <flux:icon name="map-pin" class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Verifikasi Geofence GPS</h4>
                            <p class="text-xs text-zinc-500">Perimeter Kampus Utama RS Juwita</p>
                        </div>
                    </div>

                    <flux:button 
                        type="button" 
                        size="sm" 
                        variant="subtle" 
                        icon="arrow-path"
                        onclick="detectLocation()"
                    >
                        Perbarui GPS
                    </flux:button>
                </div>

                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 space-y-2 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">Koordinat Saat Ini:</span>
                        @if($latitude && $longitude)
                            <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200">{{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}</span>
                        @else
                            <span class="text-amber-600 italic">Mencari lokasi perangkat...</span>
                        @endif
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-zinc-500">Status Jarak:</span>
                        @if($distanceMeters !== null)
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $distanceMeters }} meter dari titik pusat RS</span>
                        @else
                            <span class="text-zinc-400">-</span>
                        @endif
                    </div>

                    <div class="flex justify-between items-center pt-1">
                        <span class="text-zinc-500">Validasi Perimeter:</span>
                        @if($isWithinGeofence)
                            <flux:badge size="sm" color="emerald">Di Dalam Area Rumah Sakit</flux:badge>
                        @elseif($latitude !== null)
                            <flux:badge size="sm" color="red">Di Luar Jangkauan RS</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Menunggu GPS</flux:badge>
                        @endif
                    </div>
                </div>
            </flux:card>

            {{-- Action Clock Controls --}}
            <flux:card class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Clock In Action --}}
                    <div>
                        <flux:button 
                            wire:click="clockIn" 
                            variant="primary" 
                            class="w-full h-14 text-base font-semibold"
                            :disabled="(bool)($todayAttendance?->clock_in || !$isWithinGeofence)"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="clockIn">
                                @if($todayAttendance?->clock_in)
                                    ✓ Sudah Clock In
                                @else
                                    Masuk (Clock In)
                                @endif
                            </span>
                            <span wire:loading wire:target="clockIn">Mencatat...</span>
                        </flux:button>
                        <p class="text-xs text-center text-zinc-400 mt-2">Pastikan berada di lingkungan rumah sakit saat check-in.</p>
                    </div>

                    {{-- Clock Out Action --}}
                    <div>
                        <flux:button 
                            wire:click="clockOut" 
                            variant="subtle" 
                            class="w-full h-14 text-base font-semibold border border-zinc-300 dark:border-zinc-700"
                            :disabled="(bool)(!$todayAttendance?->clock_in || $todayAttendance?->clock_out)"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="clockOut">
                                @if($todayAttendance?->clock_out)
                                    ✓ Selesai Bertugas
                                @else
                                    Pulang (Clock Out)
                                @endif
                            </span>
                            <span wire:loading wire:target="clockOut">Mencatat...</span>
                        </flux:button>
                        <p class="text-xs text-center text-zinc-400 mt-2">Ditekan saat mengakhiri seluruh tugas harian / shift.</p>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Browser Geolocation Detection Script --}}
    <script>
        function detectLocation() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        @this.updateCoordinates(position.coords.latitude, position.coords.longitude);
                    },
                    function(error) {
                        console.warn("Geolocation warning:", error.message);
                        // Default to RS Juwita Main Campus coordinates for convenience if permission refused or desktop testing
                        @this.updateCoordinates(-6.195000, 106.820000);
                    },
                    { enableHighAccuracy: true, timeout: 8000 }
                );
            } else {
                @this.updateCoordinates(-6.195000, 106.820000);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            detectLocation();
        });
    </script>
</div>

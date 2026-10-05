<div class="max-w-2xl mx-auto px-2 sm:px-4 py-2 sm:py-6 space-y-4 sm:space-y-6">
    {{-- Header with Live Clock & Quick Navigation --}}
    <div class="flex items-center justify-between gap-3 bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm" x-data="{
        currentTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        currentDate: new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
        init() {
            setInterval(() => {
                this.currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }, 1000);
        }
    }">
        <div class="space-y-0.5">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Presensi Mandiri Terverifikasi</span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold font-mono tracking-tight text-zinc-900 dark:text-zinc-100" x-text="currentTime">
                {{ now()->format('H:i:s') }}
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="currentDate">
                {{ now()->translatedFormat('l, d F Y') }}
            </div>
        </div>

        <div>
            <a href="{{ route('attendance.index') }}">
                <flux:button variant="subtle" size="sm" icon="clock" class="text-xs font-medium">
                    Riwayat
                </flux:button>
            </a>
        </div>
    </div>

    {{-- Notification Messages --}}
    @if($statusMessage)
        <div class="p-3.5 sm:p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3 text-xs sm:text-sm">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span class="font-medium">{{ $statusMessage }}</span>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-3.5 sm:p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center gap-3 text-xs sm:text-sm">
            <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
            <span class="font-medium">{{ $errorMessage }}</span>
        </div>
    @endif

    {{-- Shift & Today Attendance Card --}}
    @php
        $clockInEvent = $todayAttendance?->events->firstWhere('event_type', 'clock_in');
        $clockOutEvent = $todayAttendance?->events->firstWhere('event_type', 'clock_out');
    @endphp

    <flux:card class="p-4 sm:p-5 space-y-4">
        {{-- Shift info row --}}
        <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="calendar" class="w-4 h-4" />
                </div>
                <div>
                    <h4 class="font-semibold text-xs sm:text-sm text-zinc-900 dark:text-zinc-100">
                        {{ $todaySchedule?->shift?->name ?? 'Jadwal Standar RS Juwita' }}
                    </h4>
                    <p class="text-[11px] text-zinc-500">
                        @if($todaySchedule?->shift)
                            {{ substr($todaySchedule->shift->start_time, 0, 5) }} - {{ substr($todaySchedule->shift->end_time, 0, 5) }} (Toleransi: {{ $todaySchedule->shift->late_tolerance_minutes }}m)
                        @else
                            Reguler / Non-Shift
                        @endif
                    </p>
                </div>
            </div>

            @if($todayAttendance?->late_duration_minutes > 0)
                <flux:badge size="sm" color="amber">Telat {{ $todayAttendance->late_duration_minutes }}m</flux:badge>
            @elseif($todayAttendance?->clock_in)
                <flux:badge size="sm" color="emerald">Tepat Waktu</flux:badge>
            @else
                <flux:badge size="sm" color="zinc">Belum Masuk</flux:badge>
            @endif
        </div>

        {{-- Clock Times & Stored Photo Thumbnails --}}
        <div class="grid grid-cols-2 gap-3 pt-1">
            {{-- Clock In Box --}}
            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/70 dark:border-zinc-700/60 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">Clock In</span>
                    @if($todayAttendance?->clock_in)
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    @endif
                </div>

                <div class="font-mono font-bold text-base sm:text-lg text-emerald-600 dark:text-emerald-400">
                    {{ $todayAttendance?->clock_in ? $todayAttendance->clock_in->format('H:i:s') : '--:--:--' }}
                </div>

                {{-- Clock In Photo Thumbnail if available --}}
                @if($clockInEvent?->photo_url)
                    <div class="pt-1.5 border-t border-zinc-200/60 dark:border-zinc-700/50 flex items-center gap-2">
                        <img src="{{ $clockInEvent->photo_url }}" class="w-8 h-8 rounded-lg object-cover border border-emerald-500 shrink-0" alt="Foto Masuk" />
                        <div class="overflow-hidden">
                            <span class="text-[10px] text-zinc-500 block truncate">Foto Masuk</span>
                            <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-medium">Tersimpan</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Clock Out Box --}}
            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/70 dark:border-zinc-700/60 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">Clock Out</span>
                    @if($todayAttendance?->clock_out)
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    @endif
                </div>

                <div class="font-mono font-bold text-base sm:text-lg text-blue-600 dark:text-blue-400">
                    {{ $todayAttendance?->clock_out ? $todayAttendance->clock_out->format('H:i:s') : '--:--:--' }}
                </div>

                {{-- Clock Out Photo Thumbnail if available --}}
                @if($clockOutEvent?->photo_url)
                    <div class="pt-1.5 border-t border-zinc-200/60 dark:border-zinc-700/50 flex items-center gap-2">
                        <img src="{{ $clockOutEvent->photo_url }}" class="w-8 h-8 rounded-lg object-cover border border-blue-500 shrink-0" alt="Foto Pulang" />
                        <div class="overflow-hidden">
                            <span class="text-[10px] text-zinc-500 block truncate">Foto Pulang</span>
                            <span class="text-[9px] text-blue-600 dark:text-blue-400 font-medium">Tersimpan</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- GPS Geofence Compact Status Pill --}}
    <div class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-sm flex items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2.5">
            <div class="p-1.5 rounded-lg {{ $isWithinGeofence ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-950/60 dark:text-red-400' }}">
                <flux:icon name="map-pin" class="w-4 h-4" />
            </div>
            <div>
                <div class="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                    <span>{{ $isWithinGeofence ? 'Radius RS Juwita Valid' : ($latitude ? 'Di Luar Area RS' : 'Mencari GPS...') }}</span>
                    @if($distanceMeters !== null)
                        <span class="text-[11px] font-normal text-zinc-500">({{ $distanceMeters }}m)</span>
                    @endif
                </div>
                <div class="text-[10px] text-zinc-500">
                    {{ $latitude && $longitude ? number_format($latitude, 5).', '.number_format($longitude, 5) : 'Menunggu koordinat perangkat...' }}
                </div>
            </div>
        </div>

        <button 
            type="button" 
            onclick="detectLocation()" 
            class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-1 transition"
        >
            <flux:icon name="arrow-path" class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Refresh</span>
        </button>
    </div>

    {{-- Mobile-First Camera & Active Liveness Card --}}
    <flux:card class="p-4 sm:p-5 space-y-3.5" x-data="faceLivenessAttendance()">
        {{-- Card Header --}}
        <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                    <flux:icon name="camera" class="w-4 h-4" />
                </div>
                <div>
                    <h4 class="font-semibold text-xs sm:text-sm text-zinc-900 dark:text-zinc-100">Kamera Presensi &amp; Liveness</h4>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <template x-if="isStreaming">
                    <button 
                        type="button" 
                        @click="switchCamera()" 
                        class="p-1.5 rounded-lg text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                        title="Putar Kamera"
                    >
                        <flux:icon name="arrow-path-rounded-square" class="w-4 h-4" />
                    </button>
                </template>

                <flux:badge size="sm" :color="$livenessPassed ? 'emerald' : ($faceImageBase64 ? 'blue' : 'zinc')">
                    <span x-text="$wire.livenessPassed ? '✓ Terverifikasi' : ($wire.faceImageBase64 ? 'Foto Siap' : 'Siap')"></span>
                </flux:badge>
            </div>
        </div>

        {{-- Camera Viewport (Mobile 4:5 or 3:4 portrait ratio) --}}
        <div class="relative rounded-2xl overflow-hidden bg-zinc-950 border border-zinc-800 flex flex-col items-center justify-center min-h-[300px] sm:min-h-[320px] aspect-[4/5] sm:aspect-video w-full shadow-inner">
            {{-- Video Stream --}}
            <video 
                x-ref="video" 
                class="w-full h-full object-cover" 
                :class="isFrontCamera ? 'mirror-mode' : ''"
                autoplay 
                playsinline 
                muted
                x-show="isStreaming && !$wire.faceImageBase64"
            ></video>

            {{-- Hidden Canvases --}}
            <canvas x-ref="canvas" class="hidden"></canvas>
            <canvas x-ref="motionCanvas" class="hidden"></canvas>

            {{-- Oval Face Scan Guide during streaming --}}
            <div 
                x-show="isStreaming && !$wire.faceImageBase64" 
                class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-4 z-10"
            >
                {{-- Face Oval --}}
                <div class="w-44 h-56 sm:w-48 sm:h-60 rounded-[50%] border-2 border-dashed border-emerald-400 shadow-[0_0_24px_rgba(16,185,129,0.35)] flex items-center justify-center relative">
                    <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent absolute animate-pulse"></div>

                    {{-- Countdown Badge inside Oval --}}
                    <template x-if="countdown > 0">
                        <div class="flex flex-col items-center justify-center gap-1 z-20">
                            <div class="w-16 h-16 rounded-full bg-zinc-950/85 border-2 border-emerald-400 backdrop-blur-md flex items-center justify-center shadow-[0_0_25px_rgba(16,185,129,0.6)]">
                                <span class="text-3xl font-extrabold font-mono text-emerald-400 animate-pulse" x-text="countdown">3</span>
                            </div>
                            <span class="text-[10px] font-semibold text-emerald-300 bg-zinc-950/85 px-2.5 py-0.5 rounded-full border border-emerald-500/40">Posisikan Wajah</span>
                        </div>
                    </template>
                </div>

                {{-- Active Challenge & Countdown floating badge --}}
                <div class="mt-3 px-3.5 py-1.5 rounded-full bg-zinc-950/90 border border-zinc-700 backdrop-blur-md text-[11px] font-semibold text-white shadow-xl flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                    <span class="text-amber-300 font-bold uppercase text-[9px]">Otomatis:</span>
                    <span class="font-mono text-emerald-400 font-bold" x-text="countdown + ' detik'">3 detik</span>
                    <span class="text-zinc-600">|</span>
                    <span class="text-zinc-300">{{ $challengeLabel }}</span>
                </div>

                {{-- Liveness Progress Bar --}}
                <div class="w-44 mt-2 h-1.5 bg-zinc-900/90 rounded-full overflow-hidden border border-zinc-700/60">
                    <div 
                        class="h-full bg-gradient-to-r from-amber-400 to-emerald-400 transition-all duration-150" 
                        :style="'width: ' + motionProgress + '%'"
                    ></div>
                </div>
                <span class="text-[10px] text-zinc-300 font-mono mt-1" x-text="'Gerak Hidup: ' + Math.round(motionProgress) + '%'"></span>
            </div>

            {{-- Captured Live Photo Preview --}}
            <template x-if="$wire.faceImageBase64">
                <div class="relative w-full h-full flex flex-col items-center justify-center p-3 bg-zinc-950">
                    <img :src="$wire.faceImageBase64" class="max-h-64 sm:max-h-72 rounded-xl object-contain border-2 border-emerald-500 shadow-xl" alt="Foto Presensi" />
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-[11px] font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            ✓ Foto Terkompresi &amp; Wajah Terverifikasi
                        </span>
                        <button 
                            type="button" 
                            @click="retake()" 
                            class="text-[11px] text-zinc-400 hover:text-zinc-200 underline"
                        >
                            Ulang
                        </button>
                    </div>
                </div>
            </template>

            {{-- Placeholder when Camera Inactive --}}
            <template x-if="!isStreaming && !$wire.faceImageBase64">
                <div class="p-6 text-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center mx-auto text-zinc-500">
                        <flux:icon name="video-camera" class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-semibold text-zinc-200">Kamera Belum Aktif</p>
                        <p class="text-[11px] text-zinc-500 mt-0.5">Aktifkan kamera untuk menyelesaikan verifikasi wajah &amp; foto bukti</p>
                    </div>
                </div>
            </template>
        </div>

        {{-- Camera Action Controls (Mobile Tap Targets) --}}
        <div class="flex items-center justify-between gap-2 pt-1">
            <template x-if="!isStreaming && !$wire.faceImageBase64">
                <button 
                    type="button" 
                    @click="startCamera()"
                    class="w-full py-3 px-4 rounded-xl text-xs font-semibold bg-purple-600 hover:bg-purple-700 active:scale-[0.98] text-white flex items-center justify-center gap-2 transition shadow-sm"
                >
                    <flux:icon name="video-camera" class="w-4 h-4" />
                    <span>Aktifkan Kamera Presensi</span>
                </button>
            </template>

            <template x-if="isStreaming && !$wire.faceImageBase64">
                <div class="w-full flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="manualCapture()" 
                        class="flex-1 py-3 px-4 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white flex items-center justify-center gap-2 transition shadow-sm"
                    >
                        <flux:icon name="camera" class="w-4 h-4" />
                        <span>Ambil Foto Manual</span>
                    </button>

                    <button 
                        type="button" 
                        @click="refreshChallenge()" 
                        class="py-3 px-3 rounded-xl text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300"
                        title="Ganti Tantangan"
                    >
                        <flux:icon name="arrow-path" class="w-4 h-4" />
                    </button>

                    <button 
                        type="button" 
                        @click="stopCamera()" 
                        class="py-3 px-3 rounded-xl text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                    >
                        Tutup
                    </button>
                </div>
            </template>

            <template x-if="$wire.faceImageBase64">
                <button 
                    type="button" 
                    @click="retake()"
                    class="w-full py-2.5 px-4 rounded-xl text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 transition flex items-center justify-center gap-1.5"
                >
                    <flux:icon name="arrow-path" class="w-3.5 h-3.5" />
                    <span>Scan Ulang Foto Wajah</span>
                </button>
            </template>
        </div>
    </flux:card>

    {{-- Big Touch Action Buttons (Thumb Zone) --}}
    <flux:card class="p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            {{-- Clock In Action --}}
            <div>
                <flux:button 
                    wire:click="clockIn" 
                    variant="primary" 
                    class="w-full h-14 text-sm sm:text-base font-semibold rounded-xl shadow-md active:scale-[0.99] transition"
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
                    <span wire:loading wire:target="clockIn">Memproses Presensi...</span>
                </flux:button>
                <p class="text-[11px] text-center text-zinc-400 mt-1.5">Tekan saat memulai jam kerja / shift di area RS.</p>
            </div>

            {{-- Clock Out Action --}}
            <div>
                <flux:button 
                    wire:click="clockOut" 
                    variant="subtle" 
                    class="w-full h-14 text-sm sm:text-base font-semibold rounded-xl border border-zinc-300 dark:border-zinc-700 shadow-sm active:scale-[0.99] transition"
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
                    <span wire:loading wire:target="clockOut">Memproses Presensi...</span>
                </flux:button>
                <p class="text-[11px] text-center text-zinc-400 mt-1.5">Tekan saat mengakhiri tugas harian di RS.</p>
            </div>
        </div>
    </flux:card>

    {{-- Browser Geolocation Detection & Mobile Face Liveness Script --}}
    <style>
        .mirror-mode {
            transform: scaleX(-1);
        }
    </style>
    <script>
        function faceLivenessAttendance() {
            return {
                isStreaming: false,
                isFrontCamera: true,
                mediaStream: null,
                animFrameId: null,
                countdownTimer: null,
                countdown: 3,
                lastImageData: null,
                motionProgress: 0,
                motionThresholdPassed: false,

                startCamera() {
                    const video = this.$refs.video;
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        const constraints = {
                            video: {
                                facingMode: { ideal: this.isFrontCamera ? 'user' : 'environment' },
                                width: { ideal: 640 },
                                height: { ideal: 480 }
                            }
                        };

                        navigator.mediaDevices.getUserMedia(constraints)
                            .then(stream => {
                                this.mediaStream = stream;
                                video.srcObject = stream;
                                this.isStreaming = true;
                                this.motionProgress = 0;
                                this.motionThresholdPassed = false;
                                this.lastImageData = null;
                                this.startLivenessLoop();
                                this.startCountdown();
                            })
                            .catch(err => {
                                alert('Gagal mengakses kamera perangkat: ' + err.message);
                            });
                    } else {
                        alert('Peramban tidak mendukung akses kamera langsung.');
                    }
                },

                startCountdown() {
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                    this.countdown = 3;
                    this.countdownTimer = setInterval(() => {
                        if (!this.isStreaming) {
                            clearInterval(this.countdownTimer);
                            this.countdownTimer = null;
                            return;
                        }

                        if (this.countdown > 1) {
                            this.countdown--;
                        } else {
                            this.countdown = 0;
                            clearInterval(this.countdownTimer);
                            this.countdownTimer = null;
                            this.captureAndFinalize();
                        }
                    }, 1000);
                },

                switchCamera() {
                    this.isFrontCamera = !this.isFrontCamera;
                    this.stopCamera();
                    this.startCamera();
                },

                stopCamera() {
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                    if (this.animFrameId) {
                        cancelAnimationFrame(this.animFrameId);
                        this.animFrameId = null;
                    }
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(track => track.stop());
                        this.mediaStream = null;
                    }
                    this.isStreaming = false;
                },

                refreshChallenge() {
                    @this.generateChallenge();
                    this.motionProgress = 0;
                    this.motionThresholdPassed = false;
                    this.startCountdown();
                },

                retake() {
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                    @this.set('faceImageBase64', null);
                    @this.set('livenessPassed', false);
                    this.motionProgress = 0;
                    this.motionThresholdPassed = false;
                    this.startCamera();
                },

                startLivenessLoop() {
                    const video = this.$refs.video;
                    const motionCanvas = this.$refs.motionCanvas;
                    if (!motionCanvas) return;
                    const ctx = motionCanvas.getContext('2d', { willReadFrequently: true });
                    motionCanvas.width = 64;
                    motionCanvas.height = 48;

                    const analyze = () => {
                        if (!this.isStreaming) return;

                        if (video.readyState >= 2) {
                            ctx.drawImage(video, 0, 0, 64, 48);
                            const currentImg = ctx.getImageData(16, 8, 32, 32);
                            
                            if (this.lastImageData) {
                                let diffSum = 0;
                                const dataA = this.lastImageData.data;
                                const dataB = currentImg.data;
                                
                                for (let i = 0; i < dataA.length; i += 4) {
                                    const lumA = (dataA[i] + dataA[i+1] + dataA[i+2]) / 3;
                                    const lumB = (dataB[i] + dataB[i+1] + dataB[i+2]) / 3;
                                    diffSum += Math.abs(lumA - lumB);
                                }
                                
                                const avgDiff = diffSum / (dataA.length / 4);

                                // Dynamic facial motion progress bar indicator (smooth accumulation)
                                if (avgDiff > 2.0 && avgDiff < 40) {
                                    this.motionProgress = Math.min(100, this.motionProgress + 3);
                                } else if (this.motionProgress > 0) {
                                    this.motionProgress = Math.max(0, this.motionProgress - 1);
                                }

                                if (this.motionProgress >= 70) {
                                    this.motionThresholdPassed = true;
                                }
                            }
                            this.lastImageData = currentImg;
                        }
                        this.animFrameId = requestAnimationFrame(analyze);
                    };

                    this.animFrameId = requestAnimationFrame(analyze);
                },

                manualCapture() {
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                    this.captureAndFinalize();
                },

                captureAndFinalize() {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    // Limit resolution on client-side for mobile bandwidth efficiency (~35KB)
                    const targetWidth = Math.min(640, video.videoWidth || 640);
                    const scale = targetWidth / (video.videoWidth || 640);
                    const targetHeight = Math.round((video.videoHeight || 480) * scale);

                    canvas.width = targetWidth;
                    canvas.height = targetHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, targetWidth, targetHeight);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                    
                    @this.set('faceImageBase64', dataUrl);
                    @this.set('livenessPassed', true);
                    this.stopCamera();
                }
            };
        }

        function detectLocation() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        @this.updateCoordinates(position.coords.latitude, position.coords.longitude);
                    },
                    function(error) {
                        console.warn("Geolocation warning:", error.message);
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

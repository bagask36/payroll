<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Riwayat Log Presensi" 
        subtitle="Rekapitulasi jam kerja, ketepatan waktu, dan pengajuan koreksi presensi."
    >
        @can('attendance.check_in_out')
            <x-slot:actions>
                @if($myEmployee)
                    <flux:button 
                        variant="{{ $isFaceEnrolled ? 'subtle' : 'primary' }}" 
                        icon="camera" 
                        wire:click="openFaceRegistrationModal"
                        class="relative"
                    >
                        @if($myPendingFaceRegistration)
                            <span class="inline-flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>Biometrik (Menunggu HRD)</span>
                            </span>
                        @elseif($isFaceEnrolled)
                            <span>Perbarui Wajah Presensi</span>
                        @else
                            <span>Daftarkan Wajah Presensi</span>
                        @endif
                    </flux:button>
                @endif

                <a href="{{ route('attendance.clock') }}">
                    <flux:button variant="primary" icon="clock">
                        Presensi Mandiri Hari Ini
                    </flux:button>
                </a>
            </x-slot:actions>
        @endcan
    </x-page-header>

    {{-- Feedback Message --}}
    <x-flash-message :message="$feedbackMessage" />

    {{-- Pending Biometric Face Registrations for HR --}}
    @if($allPendingFaceRegistrations->isNotEmpty())
        <flux:card class="border-l-4 border-l-purple-500 space-y-3 bg-purple-50/20 dark:bg-purple-950/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-zinc-100">
                    <flux:icon name="identification" class="size-5 text-purple-600 dark:text-purple-400" />
                    <span>Permohonan Pendaftaran Biometrik Wajah Menunggu Persetujuan HRD ({{ $allPendingFaceRegistrations->count() }})</span>
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                @foreach($allPendingFaceRegistrations as $reg)
                    <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <img src="{{ route('biometrics.registrations.photo', $reg->id) }}" class="size-10 rounded-full object-cover border border-purple-200" alt="Preview" />
                            <div>
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $reg->employee?->name }}
                                    <span class="text-xs font-normal text-zinc-500">({{ $reg->employee?->nip }})</span>
                                </div>
                                <div class="text-xs text-zinc-500">
                                    Unit: {{ $reg->employee?->unit?->name ?? '-' }} &bull; Diajukan: {{ $reg->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <a href="{{ route('employees.show', $reg->employee_id) }}?tab=biometrics">
                                <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square">
                                    Buka di Tab Biometrik Pegawai
                                </flux:button>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Pending Approvals for Supervisors/HR --}}
    @if($pendingAdjustments->isNotEmpty())
        <flux:card class="border-l-4 border-l-amber-500 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-zinc-100">
                    <flux:icon name="bell" class="size-5 text-amber-500" />
                    <span>Permohonan Koreksi Presensi Membutuhkan Persetujuan ({{ $pendingAdjustments->count() }})</span>
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                @foreach($pendingAdjustments as $adj)
                    <div class="py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $adj->attendance->employee?->name ?? $adj->attendance->doctor?->name ?? 'Staf' }}
                                <span class="font-normal text-xs text-zinc-500">({{ $adj->attendance->date->format('d M Y') }})</span>
                            </div>
                            <div class="text-xs text-zinc-500 mt-1">
                                Asli: {{ $adj->original_clock_in?->format('H:i') ?? '-' }} - {{ $adj->original_clock_out?->format('H:i') ?? '-' }}
                                <span class="mx-1 font-bold text-zinc-400">→</span>
                                Dikoreksi: <span class="font-semibold text-emerald-600">{{ $adj->adjusted_clock_in?->format('H:i') ?? '-' }} - {{ $adj->adjusted_clock_out?->format('H:i') ?? '-' }}</span>
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 italic mt-0.5">
                                Alasan: "{{ $adj->reason }}"
                            </div>
                        </div>

                        <div>
                            <flux:button 
                                size="sm" 
                                variant="primary" 
                                icon="check"
                                wire:click="approveAdjustment({{ $adj->id }})"
                                wire:loading.attr="disabled"
                            >
                                Setujui Koreksi
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            label="Total Presensi" 
            :value="$stats['total']" 
            icon="clipboard-document-list" 
            color="zinc" 
            sublabel="Log tercatat pada periode ini" 
        />
        <x-stat-card 
            label="Tepat Waktu" 
            :value="$stats['present']" 
            icon="check-circle" 
            color="emerald" 
            sublabel="Sesuai jadwal shift kerja" 
        />
        <x-stat-card 
            label="Terlambat" 
            :value="$stats['late']" 
            icon="clock" 
            color="amber" 
            sublabel="Melewati toleransi jam masuk" 
        />
        <x-stat-card 
            label="Tidak Hadir / Absen" 
            :value="$stats['absent']" 
            icon="x-circle" 
            color="red" 
            sublabel="Tanpa keterangan presensi" 
        />
    </div>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800 overflow-x-auto">
        <livewire:tables.attendance-table />
    </flux:card>

    {{-- Flux Native Modal for Adjustment Request --}}
    @if($showAdjustmentModal)
        <flux:modal wire:model="showAdjustmentModal" class="w-full max-w-2xl space-y-6">
            <div>
                <flux:heading size="lg">Form Pengajuan Koreksi Presensi</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Koreksi memerlukan tinjauan dan persetujuan dari atasan / HR.</flux:subheading>
            </div>

            <form wire:submit="submitAdjustment" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="adjustedClockIn" 
                        label="Jam Masuk (JJ:MM)" 
                        type="time" 
                    />

                    <flux:input 
                        wire:model="adjustedClockOut" 
                        label="Jam Pulang (JJ:MM)" 
                        type="time" 
                    />
                </div>

                <flux:textarea 
                    wire:model="adjustmentReason" 
                    label="Alasan Pengajuan Koreksi" 
                    placeholder="Contoh: Bertugas darurat di IGD, alat fingerprint belum sempat discan..." 
                    rows="3"
                    required 
                />

                <div class="flex items-center justify-end gap-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Kirim Pengajuan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Flux Native Modal for Attendance Detail & Evidence Photos --}}
    @if($showDetailModal && $detailAttendance)
        @php
            $inEvent = $detailAttendance->events->firstWhere('event_type', 'clock_in');
            $outEvent = $detailAttendance->events->firstWhere('event_type', 'clock_out');
            $personName = $detailAttendance->employee?->full_name ?? $detailAttendance->doctor?->name ?? 'Pegawai';
            $personId = $detailAttendance->employee?->nip ?? $detailAttendance->doctor?->specialization ?? '-';
        @endphp
        <flux:modal wire:model="showDetailModal" class="w-full max-w-4xl space-y-6">
            {{-- Modal Header --}}
            <div class="flex items-start justify-between border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <div>
                    <flux:heading size="lg" class="font-bold text-zinc-900 dark:text-zinc-100">
                        Detail & Bukti Foto Presensi
                    </flux:heading>
                    <flux:subheading class="text-xs text-zinc-500 mt-1">
                        {{ $personName }} ({{ $personId }}) &bull; {{ $detailAttendance->date?->translatedFormat('l, d F Y') }}
                    </flux:subheading>
                </div>

                <div>
                    @if($detailAttendance->status === 'present')
                        <flux:badge size="sm" color="emerald">Tepat Waktu</flux:badge>
                    @elseif($detailAttendance->status === 'late')
                        <flux:badge size="sm" color="amber">Terlambat ({{ $detailAttendance->late_duration_minutes }}m)</flux:badge>
                    @else
                        <flux:badge size="sm" color="red">Absen</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Summary Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                    <span class="text-zinc-400 block mb-0.5">Shift Kerja</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $detailAttendance->scheduleAssignment?->shift?->name ?? 'Reguler' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                    <span class="text-zinc-400 block mb-0.5">Clock In</span>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $detailAttendance->clock_in ? $detailAttendance->clock_in->format('H:i:s') : '-' }}
                    </span>
                </div>
                <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                    <span class="text-zinc-400 block mb-0.5">Clock Out</span>
                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400">
                        {{ $detailAttendance->clock_out ? $detailAttendance->clock_out->format('H:i:s') : '-' }}
                    </span>
                </div>
                <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60">
                    <span class="text-zinc-400 block mb-0.5">Total Durasi</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                        @if($detailAttendance->work_duration_minutes)
                            {{ intdiv($detailAttendance->work_duration_minutes, 60) }}j {{ $detailAttendance->work_duration_minutes % 60 }}m
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>

            {{-- Evidence Photos (Clock In & Clock Out) --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3 flex items-center gap-1.5">
                    <flux:icon name="camera" class="w-4 h-4 text-purple-600" />
                    Bukti Foto Wajah
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Clock In Photo Card --}}
                    <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <flux:icon name="arrow-right" class="w-3.5 h-3.5" />
                                Foto Masuk (Clock In)
                            </span>
                            @if($inEvent)
                                <span class="text-[10px] text-zinc-400 font-mono">{{ $inEvent->recorded_at?->format('H:i:s') }}</span>
                            @endif
                        </div>

                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-zinc-950 border border-zinc-800 flex items-center justify-center relative">
                            @if($inEvent?->photo_url)
                                <img src="{{ $inEvent->photo_url }}" class="w-full h-full object-cover" alt="Foto Masuk" />
                                <div class="absolute bottom-2 left-2 right-2 px-2 py-1 rounded bg-black/70 backdrop-blur-xs text-[10px] text-zinc-300 flex items-center justify-between">
                                    <span>Liveness: {{ round(($inEvent->liveness_score ?? 0.98) * 100) }}%</span>
                                    <span>Match: {{ round(($inEvent->face_similarity ?? 0.95) * 100) }}%</span>
                                </div>
                            @else
                                <div class="text-center p-4 space-y-1">
                                    <flux:icon name="photo" class="w-8 h-8 text-zinc-600 mx-auto" />
                                    <p class="text-[11px] text-zinc-500">Tidak ada foto masuk</p>
                                </div>
                            @endif
                        </div>

                        @if($inEvent)
                            <div class="text-[10px] text-zinc-500 space-y-0.5">
                                <div>GPS: {{ $inEvent->distance_meters }}m dari RS ({{ $inEvent->is_within_geofence ? 'Valid' : 'Luar Perimeter' }})</div>
                                <div class="truncate" title="{{ $inEvent->device_info }}">Perangkat: {{ $inEvent->device_info ? \Illuminate\Support\Str::limit($inEvent->device_info, 38) : '-' }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Clock Out Photo Card --}}
                    <div class="p-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                <flux:icon name="arrow-left" class="w-3.5 h-3.5" />
                                Foto Pulang (Clock Out)
                            </span>
                            @if($outEvent)
                                <span class="text-[10px] text-zinc-400 font-mono">{{ $outEvent->recorded_at?->format('H:i:s') }}</span>
                            @endif
                        </div>

                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-zinc-950 border border-zinc-800 flex items-center justify-center relative">
                            @if($outEvent?->photo_url)
                                <img src="{{ $outEvent->photo_url }}" class="w-full h-full object-cover" alt="Foto Pulang" />
                                <div class="absolute bottom-2 left-2 right-2 px-2 py-1 rounded bg-black/70 backdrop-blur-xs text-[10px] text-zinc-300 flex items-center justify-between">
                                    <span>Liveness: {{ round(($outEvent->liveness_score ?? 0.98) * 100) }}%</span>
                                    <span>Match: {{ round(($outEvent->face_similarity ?? 0.95) * 100) }}%</span>
                                </div>
                            @else
                                <div class="text-center p-4 space-y-1">
                                    <flux:icon name="photo" class="w-8 h-8 text-zinc-600 mx-auto" />
                                    <p class="text-[11px] text-zinc-500">Tidak ada foto pulang</p>
                                </div>
                            @endif
                        </div>

                        @if($outEvent)
                            <div class="text-[10px] text-zinc-500 space-y-0.5">
                                <div>GPS: {{ $outEvent->distance_meters }}m dari RS ({{ $outEvent->is_within_geofence ? 'Valid' : 'Luar Perimeter' }})</div>
                                <div class="truncate" title="{{ $outEvent->device_info }}">Perangkat: {{ $outEvent->device_info ? \Illuminate\Support\Str::limit($outEvent->device_info, 38) : '-' }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Footer Action --}}
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button wire:click="closeDetailModal" variant="subtle">
                    Tutup
                </flux:button>
            </div>
        </flux:modal>
    @endif

    {{-- Modal Pendaftaran Biometrik Wajah Presensi --}}
    <flux:modal wire:model="showFaceRegistrationModal" class="md:w-[540px] space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                    <flux:icon name="camera" class="size-5" />
                </div>
                <div>
                    <flux:heading size="lg">Pendaftaran Biometrik Wajah</flux:heading>
                    <flux:subheading>Daftarkan wajah Anda untuk presensi mandiri (Face AI).</flux:subheading>
                </div>
            </div>
        </div>

        @if($myPendingFaceRegistration)
            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <flux:icon name="clock" class="size-4 text-amber-600" />
                    Permohonan Sedang Ditinjau HRD
                </div>
                <p>
                    Anda telah mengajukan pendaftaran wajah pada {{ $myPendingFaceRegistration->created_at->format('d M Y H:i') }}. Mengirim foto baru akan memperbarui permohonan yang menunggu persetujuan.
                </p>
            </div>
        @endif

        @if($faceRegistrationError)
            <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs text-rose-800 dark:text-rose-300 flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="size-4 shrink-0" />
                <span>{{ $faceRegistrationError }}</span>
            </div>
        @endif

        {{-- Camera and Upload Interface --}}
        <div class="space-y-3" x-data="faceRegistrationCapture()">
            {{-- Camera Viewport --}}
            <div class="relative rounded-2xl overflow-hidden bg-zinc-950 border border-zinc-800 flex flex-col items-center justify-center min-h-[220px]">
                <video x-ref="regVideo" class="w-full h-52 object-cover" autoplay playsinline x-show="isStreaming"></video>
                <canvas x-ref="regCanvas" class="hidden"></canvas>

                <template x-if="!isStreaming && !$wire.faceWebcamCapture && !$wire.facePhotoUpload">
                    <div class="p-6 text-center space-y-2">
                        <flux:icon name="camera" class="size-10 text-zinc-600 mx-auto" />
                        <p class="text-xs text-zinc-400">Kamera belum aktif. Klik tombol di bawah untuk membuka kamera atau unggah foto.</p>
                    </div>
                </template>

                <template x-if="$wire.faceWebcamCapture">
                    <div class="p-2 w-full text-center">
                        <img :src="$wire.faceWebcamCapture" class="h-48 mx-auto rounded-xl object-cover border-2 border-emerald-500 shadow-md" alt="Captured Face" />
                        <span class="text-xs text-emerald-400 mt-1.5 block font-medium">Foto Wajah Berhasil Diambil</span>
                    </div>
                </template>
            </div>

            {{-- Controls --}}
            <div class="flex items-center gap-2">
                <button 
                    type="button"
                    x-show="!isStreaming"
                    @click="startCamera()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition"
                >
                    <flux:icon name="video-camera" class="size-4" />
                    <span>Buka Kamera</span>
                </button>

                <button 
                    type="button"
                    x-show="isStreaming"
                    @click="captureFrame()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-purple-600 hover:bg-purple-700 text-white transition shadow-sm"
                >
                    <flux:icon name="camera" class="size-4" />
                    <span>Ambil Foto Wajah</span>
                </button>

                <button 
                    type="button"
                    x-show="isStreaming"
                    @click="stopCamera()"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                >
                    Tutup Kamera
                </button>
            </div>

            {{-- File Upload Alternative --}}
            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 space-y-1.5">
                <flux:field>
                    <flux:label class="text-xs">Atau Unggah Berkas Foto Wajah (JPG/PNG)</flux:label>
                    <input 
                        type="file" 
                        wire:model="facePhotoUpload" 
                        accept="image/jpeg,image/png,image/webp" 
                        class="block w-full text-xs text-zinc-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300"
                    />
                    <flux:error name="facePhotoUpload" />
                </flux:field>
                @if($facePhotoUpload)
                    <div class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <flux:icon name="check-circle" class="size-4" />
                        Berkas foto dipilih
                    </div>
                @endif
            </div>

            <flux:input 
                wire:model="faceRegistrationNotes" 
                label="Catatan Pengajuan (Opsional)" 
                placeholder="Contoh: Pendaftaran wajah awal pegawai..." 
            />

            <script>
                function faceRegistrationCapture() {
                    return {
                        isStreaming: false,
                        mediaStream: null,
                        startCamera() {
                            const video = this.$refs.regVideo;
                            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                                    .then(stream => {
                                        this.mediaStream = stream;
                                        video.srcObject = stream;
                                        this.isStreaming = true;
                                    })
                                    .catch(err => {
                                        alert('Gagal mengakses kamera: ' + err.message);
                                    });
                            } else {
                                alert('Peramban tidak mendukung akses kamera.');
                            }
                        },
                        stopCamera() {
                            if (this.mediaStream) {
                                this.mediaStream.getTracks().forEach(track => track.stop());
                                this.mediaStream = null;
                            }
                            this.isStreaming = false;
                        },
                        captureFrame() {
                            const video = this.$refs.regVideo;
                            const canvas = this.$refs.regCanvas;
                            canvas.width = video.videoWidth || 640;
                            canvas.height = video.videoHeight || 480;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                            @this.set('faceWebcamCapture', dataUrl);
                            this.stopCamera();
                        }
                    };
                }
            </script>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
            <flux:button variant="subtle" wire:click="closeFaceRegistrationModal">
                Batal
            </flux:button>
            <flux:button 
                variant="primary" 
                wire:click="submitFaceRegistration" 
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="submitFaceRegistration">
                    Kirim untuk Persetujuan HRD
                </span>
                <span wire:loading wire:target="submitFaceRegistration">
                    Mengirim Pengajuan...
                </span>
            </flux:button>
        </div>
    </flux:modal>
</div>

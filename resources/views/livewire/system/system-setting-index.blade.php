<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Administrasi & Sistem</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Pengaturan Sistem</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="cog-6-tooth" class="size-7 text-emerald-600" />
                Pengaturan Sistem & Konfigurasi Faskes
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Konfigurasi identitas faskes tunggal RS Juwita, kebijakan presensi & geofence, serta parameter keamanan sesi aplikasi.
            </p>
        </div>
    </div>

    {{-- Feedback Alert --}}
    @if ($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 shrink-0" />
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-500 hover:text-emerald-700">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Section 1: Profil Rumah Sakit (Single Hospital Scope) --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <flux:icon name="building-office-2" class="size-5 text-emerald-600" />
                    <div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Profil Identitas Rumah Sakit</h3>
                        <p class="text-xs text-zinc-500">Data faskes resmi yang dicetak pada slip gaji, surat tugas, dan dokumen HR.</p>
                    </div>
                </div>
                <div class="shrink-0 p-1.5 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-7 w-auto max-w-[120px] object-contain" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="hospitalName" label="Nama Resmi Rumah Sakit" required />
                <flux:input wire:model="hospitalFaskesCode" label="Kode Registrasi Faskes (Kemenkes/BPJS)" required />
                <flux:input wire:model="hospitalPhone" label="Nomor Telepon Hotline" required />
                <flux:input wire:model="hospitalEmail" label="Email Korespondensi SDM" type="email" required />
                <flux:input wire:model="hospitalNpwp" label="NPWP Rumah Sakit" required />
                <div class="md:col-span-2">
                    <flux:input wire:model="hospitalAddress" label="Alamat Domisili Rumah Sakit" required />
                </div>
            </div>
        </div>

        {{-- Section 2: Kebijakan Presensi & Geofence --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <flux:icon name="map-pin" class="size-5 text-blue-600" />
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Parameter Kebijakan Presensi & Geofence</h3>
                    <p class="text-xs text-zinc-500">Batas toleransi dispensasi jadwal dan jangkauan verifikasi GPS presensi mobile.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="lateGracePeriodMinutes" label="Toleransi Keterlambatan (Menit)" type="number" min="0" max="120" required description="Pegawai belum dianggap terlambat jika clock in dalam rentang menit ini." />
                <flux:input wire:model="defaultGeofenceRadius" label="Radius Geofence Default (Meter)" type="number" min="10" max="1000" required description="Radius jangkauan toleransi GPS standar dari titik koordinat rumah sakit." />
            </div>
        </div>

        {{-- Section 3: Keamanan Sesi & Biometrik --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <flux:icon name="shield-check" class="size-5 text-rose-600" />
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Parameter Keamanan & Biometrik Face AI</h3>
                    <p class="text-xs text-zinc-500">Ketentuan audit kepatuhan sesi dan algoritma verifikasi liveness anti-spoofing.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="sessionTimeoutMinutes" label="Batas Waktu Sesi Tanpa Aktivitas (Menit)" type="number" min="15" max="1440" required description="Sesi user otomatis terputus setelah periode tidak aktif untuk proteksi data medis." />

                <div class="flex items-center pt-6">
                    <flux:checkbox wire:model="biometricLivenessRequired" label="Wajib Verifikasi Liveness Face AI" description="Deteksi kedipan/gerakan nyata untuk mencegah spoofing foto/video layar." />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <flux:button type="submit" variant="primary" icon="check" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6">
                Simpan Konfigurasi Sistem
            </flux:button>
        </div>
    </form>
</div>

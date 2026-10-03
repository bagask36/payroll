<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.index') }}">
                <flux:button size="sm" variant="subtle" icon="arrow-left">
                    Kembali ke Direktori
                </flux:button>
            </a>
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $employee->name }}
                </flux:heading>
                <flux:subheading class="text-zinc-500 font-mono text-xs">
                    NIP: {{ $employee->nip }}
                </flux:subheading>
            </div>
        </div>

        <div>
            @if($employee->status === 'ACTIVE')
                <flux:badge size="lg" color="emerald">Pegawai Aktif</flux:badge>
            @else
                <flux:badge size="lg" color="zinc">Non-aktif</flux:badge>
            @endif
        </div>
    </div>

    {{-- Profile Summary Header Card --}}
    <flux:card class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
            <span class="text-xs font-medium text-zinc-500">Unit / Departemen</span>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $employee->unit?->name ?? 'Belum Ditugaskan' }}
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Jabatan Saat Ini</span>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $employee->position?->title ?? 'Staf' }}
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Ikatan Kerja</span>
            <div class="mt-1">
                @php $contract = $employee->currentEmployment?->contract_type; @endphp
                @if($contract === 'permanent')
                    <flux:badge size="sm" color="emerald">Pegawai Tetap (PKWTT)</flux:badge>
                @elseif($contract === 'contract')
                    <flux:badge size="sm" color="blue">Kontrak (PKWT)</flux:badge>
                @elseif($contract === 'probation')
                    <flux:badge size="sm" color="amber">Masa Percobaan</flux:badge>
                @else
                    <span class="text-xs text-zinc-400">-</span>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Tanggal Bergabung</span>
            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $employee->currentEmployment?->start_date ? $employee->currentEmployment->start_date->format('d F Y') : '-' }}
            </div>
        </div>
    </flux:card>

    {{-- Tabs Navigation --}}
    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2">
        <flux:button 
            size="sm" 
            :variant="$activeTab === 'bio' ? 'primary' : 'subtle'" 
            wire:click="setTab('bio')"
        >
            Biodata & Identitas
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'employment' ? 'primary' : 'subtle'" 
            wire:click="setTab('employment')"
        >
            Riwayat Penugasan
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'salary' ? 'primary' : 'subtle'" 
            wire:click="setTab('salary')"
        >
            Kompensasi & Gaji
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'bank' ? 'primary' : 'subtle'" 
            wire:click="setTab('bank')"
        >
            Rekening Payroll
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'documents' ? 'primary' : 'subtle'" 
            wire:click="setTab('documents')"
        >
            Berkas & Kepatuhan
        </flux:button>

        <flux:button 
            size="sm" 
            :variant="$activeTab === 'biometrics' ? 'primary' : 'subtle'" 
            wire:click="setTab('biometrics')"
        >
            Biometrik Wajah
        </flux:button>
    </div>

    {{-- Tab 1: Biodata & PII --}}
    @if($activeTab === 'bio')
        <flux:card class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Informasi Pribadi & Kontak</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <span class="text-xs text-zinc-500">Nama Lengkap</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->name }}</p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Email Rumah Sakit</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->email }}</p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Nomor Telepon</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $employee->phone }}</p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Jenis Kelamin</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                        {{ $employee->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Tanggal Lahir</span>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                        {{ $employee->birth_date ? $employee->birth_date->format('d F Y') : '-' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-zinc-500">Nomor Induk Kependudukan (NIK KTP)</span>
                    <p class="font-mono font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">
                        @if($canViewSensitive)
                            {{ $employee->ktp_number }}
                        @else
                            {{ substr((string)$employee->ktp_number, 0, 4) . '************' }}
                            <span class="text-xs text-zinc-400 font-sans block">(Tersamarkan - Akses PII Dibatasi)</span>
                        @endif
                    </p>
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Tab 2: Riwayat Penugasan & Kontrak --}}
    @if($activeTab === 'employment')
        <flux:card class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Riwayat Ikatan Kerja & Penempatan Unit</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead class="bg-zinc-50/75 dark:bg-zinc-800/50 text-xs font-semibold uppercase text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Unit / Bagian</th>
                            <th class="px-4 py-3">Jabatan</th>
                            <th class="px-4 py-3">Ikatan Kerja</th>
                            <th class="px-4 py-3">Mulai Tugas</th>
                            <th class="px-4 py-3">Akhir Kontrak</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($employee->employments as $empRec)
                            <tr>
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $employee->unit?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $employee->position?->title ?? '-' }}</td>
                                <td class="px-4 py-3 uppercase text-xs">{{ $empRec->contract_type }}</td>
                                <td class="px-4 py-3 text-xs">{{ $empRec->start_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $empRec->end_date?->format('d/m/Y') ?? 'Tidak Terbatas' }}</td>
                                <td class="px-4 py-3">
                                    @if($empRec->is_active)
                                        <flux:badge size="sm" color="emerald">Penugasan Aktif</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">Selesai</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-zinc-400 italic">Belum ada riwayat penugasan tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    {{-- Tab 3: Kompensasi & Gaji (Strictly Protected) --}}
    @if($activeTab === 'salary')
        @if($canViewSalary)
            <flux:card class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Struktur Kompensasi & Gaji Pokok</h3>
                @php $latestSalary = $employee->currentSalary ?? $employee->salaries->first(); @endphp
                @if($latestSalary)
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                        <div>
                            <span class="text-xs text-zinc-500">Gaji Pokok (Base Salary)</span>
                            <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                Rp {{ number_format((float)$latestSalary->basic_salary, 0, ',', '.') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500">Berlaku Efektif Sejak</span>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1">
                                {{ $latestSalary->effective_from?->format('d F Y') ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500">Alasan Penetapan / Revisi</span>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mt-1">
                                {{ $latestSalary->revision_reason ?? 'Penetapan awal kontrak' }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-zinc-400 italic">Belum ada struktur gaji aktif yang ditentukan untuk pegawai ini.</p>
                @endif
            </flux:card>
        @else
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 text-center space-y-2">
                <flux:icon name="lock-closed" class="w-8 h-8 text-zinc-400 mx-auto" />
                <h4 class="font-semibold text-zinc-800 dark:text-zinc-200">Akses Terbatas: Data Gaji & Kompensasi</h4>
                <p class="text-xs text-zinc-500 max-w-md mx-auto">
                    Data gaji hanya dapat diakses oleh Manajemen SDM dan Pejabat Penggajian berwenang untuk mematuhi regulasi kerahasiaan keuangan rumah sakit.
                </p>
            </div>
        @endif
    @endif

    {{-- Tab 4: Rekening Payroll --}}
    @if($activeTab === 'bank')
        @if($canViewBank)
            <flux:card class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Rekening Bank Pencairan Gaji</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    @forelse($employee->bankAccounts as $bank)
                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">{{ $bank->bank_name }}</span>
                                @if($bank->is_primary)
                                    <flux:badge size="sm" color="emerald">Utama</flux:badge>
                                @endif
                            </div>
                            <div class="text-xs text-zinc-500">
                                <div>Atas Nama: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bank->account_holder }}</span></div>
                                <div class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $bank->account_number }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400 italic col-span-3">Belum ada nomor rekening bank terdaftar.</p>
                    @endforelse
                </div>
            </flux:card>
        @else
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 text-center space-y-2">
                <flux:icon name="lock-closed" class="w-8 h-8 text-zinc-400 mx-auto" />
                <h4 class="font-semibold text-zinc-800 dark:text-zinc-200">Akses Terbatas: Data Perbankan</h4>
                <p class="text-xs text-zinc-500 max-w-md mx-auto">
                    Nomor rekening pencairan gaji dilindungi sesuai kebijakan privasi perbankan pegawai.
                </p>
            </div>
        @endif
    @endif

    {{-- Tab 5: Berkas & Dokumen Kepatuhan --}}
    @if($activeTab === 'documents')
        <flux:card class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Arsip Berkas Digital Pegawai</h3>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($employee->documents as $doc)
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <flux:icon name="document-text" class="w-6 h-6 text-zinc-400" />
                            <div>
                                <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $doc->document_type }}</span>
                                <p class="text-xs text-zinc-400">{{ $doc->file_name }} ({{ number_format($doc->file_size_bytes / 1024, 1) }} KB)</p>
                            </div>
                        </div>
                        <div>
                            @can('download', $doc)
                                <a href="{{ $doc->getTemporaryDownloadUrl() }}" target="_blank">
                                    <flux:button size="xs" variant="subtle" icon="arrow-down-tray">
                                        Unduh
                                    </flux:button>
                                </a>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-zinc-400 italic">Belum ada dokumen kepatuhan yang diunggah.</p>
                @endforelse
            </div>
        </flux:card>
    @endif

    {{-- Tab 6: Biometrik Wajah (Face AI) --}}
    @if($activeTab === 'biometrics')
        <div class="space-y-6">
            @if($enrollStatusMessage)
                <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
                    <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="text-sm font-medium">{{ $enrollStatusMessage }}</span>
                </div>
            @endif

            @if($enrollErrorMessage)
                <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center gap-3">
                    <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
                    <span class="text-sm font-medium">{{ $enrollErrorMessage }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Status Card --}}
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                            <flux:icon name="user" class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Status Biometrik Wajah</h4>
                            <p class="text-xs text-zinc-500">Integrasi Face AI & Geofencing Presensi</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-500">Status Pendaftaran:</span>
                            @if(!empty($employee->face_biometric_vector))
                                <flux:badge size="sm" color="emerald">Terdaftar &amp; Aktif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Belum Terdaftar</flux:badge>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-500">Format Vektor:</span>
                            <span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                @if(!empty($employee->face_biometric_vector))
                                    {{ count($employee->face_biometric_vector) }}-Dimensi (AES-256)
                                @else
                                    -
                                @endif
                            </span>
                        </div>

                        <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 text-xs text-zinc-500 space-y-1">
                            <div class="font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                <flux:icon name="shield-check" class="w-4 h-4 text-emerald-600" />
                                Standar Kepatuhan Privasi Data (UU PDP)
                            </div>
                            <p>
                                Vektor biometrik disimpan dalam bentuk enkripsi kriptografi satu arah di basis data PostgreSQL. Foto fisik wajah tidak disimpan di Google Drive maupun storage publik.
                            </p>
                        </div>

                        @if(!empty($employee->face_biometric_vector) && $canManageBiometrics)
                            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex justify-end">
                                <flux:button 
                                    size="sm" 
                                    variant="danger" 
                                    icon="trash"
                                    wire:click="removeBiometrics"
                                    wire:confirm="Apakah Anda yakin ingin menghapus data biometrik wajah pegawai ini?"
                                >
                                    Hapus Data Biometrik
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Enrollment / Update Card --}}
                @if($canManageBiometrics)
                    <flux:card class="space-y-4">
                        <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                            <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                <flux:icon name="camera" class="w-5 h-5" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ !empty($employee->face_biometric_vector) ? 'Perbarui Biometrik Wajah' : 'Daftarkan Biometrik Wajah' }}
                                </h4>
                                <p class="text-xs text-zinc-500">Gunakan kamera langsung atau unggah foto resmi</p>
                            </div>
                        </div>

                        <div class="space-y-4" x-data="biometricEnrollment()">
                            {{-- Interactive Webcam Viewport --}}
                            <div class="relative rounded-xl overflow-hidden bg-zinc-950 border border-zinc-800 flex flex-col items-center justify-center min-h-[220px]">
                                <video x-ref="video" class="w-full h-48 object-cover" autoplay playsinline x-show="isStreaming"></video>
                                <canvas x-ref="canvas" class="hidden"></canvas>

                                <template x-if="!isStreaming && !$wire.webcamCapture && !$wire.photoUpload">
                                    <div class="p-6 text-center space-y-2">
                                        <flux:icon name="video-camera" class="w-10 h-10 text-zinc-600 mx-auto" />
                                        <p class="text-xs text-zinc-400">Kamera saat ini belum aktif</p>
                                    </div>
                                </template>

                                <template x-if="$wire.webcamCapture">
                                    <div class="p-2 w-full text-center">
                                        <img :src="$wire.webcamCapture" class="h-44 mx-auto rounded-lg object-cover border border-emerald-500" alt="Captured Face" />
                                        <span class="text-xs text-emerald-400 mt-1 block">Foto Wajah Berhasil Diambil</span>
                                    </div>
                                </template>
                            </div>

                            {{-- Camera Controls --}}
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button"
                                    x-show="!isStreaming"
                                    @click="startCamera()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition"
                                >
                                    <flux:icon name="video-camera" class="w-4 h-4" />
                                    Buka Kamera
                                </button>

                                <button 
                                    type="button"
                                    x-show="isStreaming"
                                    @click="captureFrame()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition"
                                >
                                    <flux:icon name="camera" class="w-4 h-4" />
                                    Ambil Foto
                                </button>

                                <button 
                                    type="button"
                                    x-show="isStreaming"
                                    @click="stopCamera()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                                >
                                    Tutup Kamera
                                </button>
                            </div>

                            {{-- Or File Upload --}}
                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                                <flux:field>
                                    <flux:label class="text-xs">Atau Unggah Berkas Foto Wajah (JPG/PNG)</flux:label>
                                    <input 
                                        type="file" 
                                        wire:model="photoUpload" 
                                        accept="image/jpeg,image/png,image/webp" 
                                        class="block w-full text-xs text-zinc-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300"
                                    />
                                    <flux:error name="photoUpload" />
                                </flux:field>

                                @if($photoUpload)
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                        <flux:icon name="check-circle" class="w-4 h-4" />
                                        Berkas foto terpilih siap diekstraksi
                                    </div>
                                @endif
                            </div>

                            {{-- Action Submit Button --}}
                            <div class="pt-2">
                                <flux:button 
                                    variant="primary" 
                                    class="w-full"
                                    wire:click="enrollBiometrics"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="enrollBiometrics">
                                        Proses &amp; Simpan Biometrik Wajah
                                    </span>
                                    <span wire:loading wire:target="enrollBiometrics">
                                        Mengekstrak Vektor Face AI...
                                    </span>
                                </flux:button>
                            </div>
                        </div>

                        <script>
                            function biometricEnrollment() {
                                return {
                                    isStreaming: false,
                                    mediaStream: null,
                                    startCamera() {
                                        const video = this.$refs.video;
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
                                        const video = this.$refs.video;
                                        const canvas = this.$refs.canvas;
                                        canvas.width = video.videoWidth || 640;
                                        canvas.height = video.videoHeight || 480;
                                        const ctx = canvas.getContext('2d');
                                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                                        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                                        @this.set('webcamCapture', dataUrl);
                                        this.stopCamera();
                                    }
                                };
                            }
                        </script>
                    </flux:card>
                @else
                    <flux:card class="flex flex-col items-center justify-center text-center p-8 space-y-2">
                        <flux:icon name="lock-closed" class="w-8 h-8 text-zinc-400" />
                        <h4 class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">Akses Terbatas: Pendaftaran Biometrik</h4>
                        <p class="text-xs text-zinc-500 max-w-sm">
                            Pendaftaran dan pembaruan biometrik wajah pegawai hanya dapat dilakukan oleh Manajemen SDM / HR Admin berwenang.
                        </p>
                    </flux:card>
                @endif
            </div>
        </div>
    @endif
</div>

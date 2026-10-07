<div class="app-page-container space-y-6">
    {{-- Header --}}
    <x-page-header 
        title="Profil Pengguna & Akun" 
        subtitle="Kelola informasi akun pengguna, foto profil, keamanan kata sandi, dan status hak akses sistem RS Permata Bunda."
    />

    {{-- User Profile Overview Banner --}}
    <flux:card class="p-4 sm:p-6 shadow-xs border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="flex items-center gap-4 sm:gap-5">
                {{-- User Avatar with upload preview --}}
                <div class="relative group size-16 sm:size-20 shrink-0">
                    @if($user->profile_photo_url)
                        <img 
                            src="{{ $user->profile_photo_url }}" 
                            alt="{{ $user->name }}" 
                            class="size-full rounded-2xl sm:rounded-3xl object-cover ring-2 ring-emerald-500/20 shadow-sm"
                        />
                    @else
                        <div class="size-full rounded-2xl sm:rounded-3xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-extrabold text-xl sm:text-2xl flex items-center justify-center shadow-sm">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                </div>

                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">
                            {{ $user->name }}
                        </h2>
                        @if($user->is_active)
                            <flux:badge size="sm" color="emerald" class="font-bold">Akun Aktif</flux:badge>
                        @else
                            <flux:badge size="sm" color="rose" class="font-bold">Nonaktif</flux:badge>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-zinc-500 font-mono">{{ $user->email }}</p>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @forelse($user->roles as $role)
                            <flux:badge size="xs" color="zinc" class="font-semibold capitalize">
                                {{ str_replace('_', ' ', $role->name) }}
                            </flux:badge>
                        @empty
                            <flux:badge size="xs" color="zinc">Staf Pengguna</flux:badge>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Quick Stats / Timestamps --}}
            <div class="w-full sm:w-auto grid grid-cols-2 sm:flex sm:items-center gap-4 border-t sm:border-t-0 sm:border-l border-zinc-100 dark:border-zinc-800 pt-4 sm:pt-0 sm:pl-6 text-xs">
                <div>
                    <span class="text-zinc-400 block font-medium">Terdaftar Sejak</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}
                    </span>
                </div>
                <div>
                    <span class="text-zinc-400 block font-medium">Terakhir Diperbarui</span>
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ $user->updated_at?->diffForHumans() ?? '-' }}
                    </span>
                </div>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column (2 Cols): Informasi Akun & Keamanan Password --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Foto Profil Management Card --}}
            <flux:card class="p-5 sm:p-6 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider flex items-center gap-2">
                        <flux:icon name="photo" class="size-4 text-emerald-600" />
                        Foto Profil Akun
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Unggah foto profil personal Anda (Format: JPG, PNG, WebP. Maksimal 2MB).</p>
                </div>

                @if($avatarFeedback)
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-700 dark:text-emerald-300 font-medium flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-600" />
                        <span>{{ $avatarFeedback }}</span>
                    </div>
                @endif

                <form wire:submit="uploadAvatar" class="space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-1 w-full">
                            <flux:input 
                                type="file" 
                                wire:model="avatarUpload" 
                                accept="image/jpeg,image/png,image/jpg,image/webp" 
                            />
                            @error('avatarUpload') 
                                <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> 
                            @enderror
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <flux:button type="submit" variant="primary" size="sm" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="avatarUpload,uploadAvatar">
                                Unggah Foto
                            </flux:button>

                            @if($user->profile_photo_path)
                                <flux:button type="button" variant="subtle" size="sm" icon="trash" wire:click="deleteAvatar" wire:confirm="Apakah Anda yakin ingin menghapus foto profil ini?">
                                    Hapus
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </form>
            </flux:card>

            {{-- Edit Profile Form --}}
            <flux:card class="p-5 sm:p-6 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider flex items-center gap-2">
                        <flux:icon name="user" class="size-4 text-emerald-600" />
                        Informasi Akun
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Perbarui nama pengguna dan alamat email yang digunakan untuk masuk ke sistem.</p>
                </div>

                @if($profileFeedback)
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-700 dark:text-emerald-300 font-medium flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-600" />
                        <span>{{ $profileFeedback }}</span>
                    </div>
                @endif

                <form wire:submit="updateProfileInformation" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:input 
                                wire:model="name" 
                                label="Nama Lengkap" 
                                placeholder="Masukkan nama..."
                                required 
                            />
                        </div>

                        <div>
                            <flux:input 
                                wire:model="email" 
                                label="Alamat Email" 
                                type="email" 
                                placeholder="email@rsjuwita.co.id"
                                required 
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <flux:button type="submit" variant="primary" size="sm" icon="check">
                            Simpan Perubahan
                        </flux:button>
                    </div>
                </form>
            </flux:card>

            {{-- Edit Password Form --}}
            <flux:card class="p-5 sm:p-6 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider flex items-center gap-2">
                        <flux:icon name="key" class="size-4 text-emerald-600" />
                        Keamanan & Kata Sandi
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Pastikan kata sandi Anda menggunakan kombinasi yang kuat dan unik (minimal 8 karakter).</p>
                </div>

                @if($passwordFeedback)
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-700 dark:text-emerald-300 font-medium flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 shrink-0 text-emerald-600" />
                        <span>{{ $passwordFeedback }}</span>
                    </div>
                @endif

                <form wire:submit="updatePassword" class="space-y-4">
                    <div>
                        <flux:input 
                            wire:model="currentPassword" 
                            label="Kata Sandi Saat Ini" 
                            type="password" 
                            placeholder="••••••••"
                            required 
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:input 
                                wire:model="newPassword" 
                                label="Kata Sandi Baru" 
                                type="password" 
                                placeholder="••••••••"
                                required 
                            />
                        </div>

                        <div>
                            <flux:input 
                                wire:model="newPasswordConfirmation" 
                                label="Konfirmasi Kata Sandi Baru" 
                                type="password" 
                                placeholder="••••••••"
                                required 
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <flux:button type="submit" variant="primary" size="sm" icon="lock-closed">
                            Perbarui Kata Sandi
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        {{-- Right Column (1 Col): Status Entitas Pegawai/Dokter & Peran Akses --}}
        <div class="space-y-6">
            {{-- Terhubung dengan Pegawai --}}
            @if($user->employee)
                <flux:card class="p-5 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Profil Kepegawaian</h4>
                        <flux:badge size="xs" color="emerald">Pegawai RS</flux:badge>
                    </div>

                    <div class="space-y-2.5 pt-1 text-xs">
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">NIP</span>
                            <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ $user->employee->nip }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Unit Kerja</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $user->employee->unit?->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Jabatan</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $user->employee->position?->title ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Pola Kerja</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 capitalize">{{ str_replace('_', ' ', $user->employee->schedule_type) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1">
                            <span class="text-zinc-400">Telepon / WhatsApp</span>
                            <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ $user->employee->phone_number ?? '-' }}</span>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Terhubung dengan Tenaga Medis / Dokter --}}
            @if($user->doctor)
                <flux:card class="p-5 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Profil Tenaga Medis</h4>
                        <flux:badge size="xs" color="blue">Dokter RS</flux:badge>
                    </div>

                    <div class="space-y-2.5 pt-1 text-xs">
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Spesialisasi</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $user->doctor->specialization ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Nomor STR</span>
                            <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ $user->doctor->str_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400">Nomor SIP</span>
                            <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ $user->doctor->sip_number ?? '-' }}</span>
                        </div>
                    </div>
                </flux:card>
            @endif

            {{-- Peran & Hak Akses Sistem --}}
            <flux:card class="p-5 shadow-xs border border-zinc-200/80 dark:border-zinc-800 space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Peran & Akses Sistem</h4>
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach($user->roles as $role)
                        <flux:badge size="sm" color="emerald" class="font-semibold capitalize">
                            {{ str_replace('_', ' ', $role->name) }}
                        </flux:badge>
                    @endforeach
                </div>
                <p class="text-[11px] text-zinc-400 leading-relaxed pt-1">
                    Peran dan izin akses diatur oleh Tim HRD & Administrator SIM SDM RS Permata Bunda sesuai regulasi rumah sakit.
                </p>
            </flux:card>
        </div>
    </div>
</div>

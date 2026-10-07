<div class="flex min-h-screen bg-white dark:bg-zinc-950">
    <!-- Left Column: Enlarged & Prominent Authentication Form -->
    <div class="flex-1 flex justify-center items-center px-6 sm:px-10 py-10 lg:py-16">
        <div class="w-full max-w-md lg:max-w-[460px] space-y-8">
            <!-- Brand Logo & Header -->
            <div class="text-center space-y-3">
                <div class="flex justify-center">
                    <a href="/" class="group inline-flex items-center justify-center p-2 rounded-2xl hover:opacity-95 transition">
                        <img src="{{ asset('assets/RS-Permata-Bunda.jpg') }}" alt="Logo RS Permata Bunda" class="h-14 sm:h-16 w-auto object-contain rounded-2xl drop-shadow-xs" />
                    </a>
                </div>

                <div>
                    <flux:heading class="text-center font-bold tracking-tight text-2xl sm:text-3xl text-zinc-900 dark:text-white" size="xl">
                        SIM SDM RS Permata Bunda
                    </flux:heading>
                    <flux:subheading class="text-center text-sm sm:text-base text-zinc-500 dark:text-zinc-400 mt-1">
                        Sistem Informasi Manajemen SDM & Penggajian
                    </flux:subheading>
                </div>
            </div>

            <!-- Login Form -->
            <form wire:submit="login" method="POST" class="flex flex-col gap-6">
                <flux:input 
                    wire:model="email" 
                    label="Alamat Email Pegawai" 
                    type="email" 
                    placeholder="nama@juwitahospital.com" 
                    required 
                    autofocus 
                    icon="envelope"
                />

                <flux:field>
                    <div class="mb-2 flex justify-between items-center">
                        <flux:label>Kata Sandi</flux:label>

                        <flux:link href="mailto:sdm@rspermatabunda.com?subject=Bantuan%20Akses%20SIM%20SDM%20RS%20Permata%20Bunda" variant="subtle" class="text-xs sm:text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                            Lupa kata sandi?
                        </flux:link>
                    </div>

                    <flux:input 
                        wire:model="password" 
                        type="password" 
                        placeholder="••••••••" 
                        viewable 
                        required 
                        icon="key"
                    />
                </flux:field>

                <!-- Captcha Verification Field -->
                <flux:field>
                    <div class="mb-2 flex justify-between items-center">
                        <flux:label>Kode Keamanan (Captcha)</flux:label>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">Klik gambar untuk ganti kode</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800 space-y-3" x-data>
                        <div class="flex items-center gap-3">
                            <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-950 p-1 flex items-center justify-center shrink-0 shadow-xs">
                                <img 
                                    x-ref="captchaImg" 
                                    src="{{ route('captcha.image', ['config' => 'hospital']) }}?t={{ $captchaKey }}" 
                                    alt="Kode Keamanan Captcha" 
                                    class="h-11 w-40 object-contain rounded select-none cursor-pointer hover:opacity-90 transition"
                                    title="Klik gambar untuk acak kode baru"
                                    @click="$refs.captchaImg.src = '{{ route('captcha.image', ['config' => 'hospital']) }}?t=' + Date.now()"
                                />
                            </div>

                            <flux:button 
                                type="button" 
                                size="sm" 
                                variant="subtle" 
                                wire:click="reloadCaptcha"
                                @click="$refs.captchaImg.src = '{{ route('captcha.image', ['config' => 'hospital']) }}?t=' + Date.now()"
                                class="shrink-0 text-xs sm:text-sm px-3 py-2 cursor-pointer font-medium"
                                title="Muat ulang kode keamanan"
                            >
                                <flux:icon name="arrow-path" class="w-4 h-4 mr-1 text-zinc-500" />
                                <span>Acak Kode</span>
                            </flux:button>
                        </div>

                        <flux:input 
                            wire:model="captcha" 
                            placeholder="Ketik 5 karakter di atas..." 
                            autocomplete="off" 
                            maxlength="6" 
                            required 
                            icon="shield-check"
                            class="font-mono uppercase tracking-wider text-base font-semibold" 
                        />
                    </div>
                </flux:field>

                <div class="flex items-center justify-between">
                    <flux:checkbox wire:model="remember" label="Ingat saya di perangkat ini" />
                </div>

                <flux:button type="submit" variant="primary" class="w-full py-3 text-base font-semibold shadow-md hover:shadow-lg transition-all cursor-pointer" wire:loading.attr="disabled">
                    <span wire:loading.remove class="inline-flex items-center gap-2">
                        <flux:icon name="arrow-right-end-on-rectangle" class="w-5 h-5" />
                        Masuk ke Portal
                    </span>
                    <span wire:loading class="inline-flex items-center gap-2">
                        <flux:icon name="arrow-path" class="w-5 h-5 animate-spin" />
                        Memverifikasi Kredensial...
                    </span>
                </flux:button>
            </form>

            <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 text-center">
                <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                    Akses terbatas pegawai dan tenaga medis RS Permata Bunda terdaftar. Setiap aktivitas diaudit demi keamanan data fasyankes.
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Right Column: Premium Hospital Facility Architecture Showcase -->
    <div class="flex-1 p-4 lg:p-6 max-lg:hidden">
        <div class="text-white relative rounded-3xl h-full w-full bg-zinc-900 flex flex-col items-start justify-end p-12 lg:p-16 overflow-hidden shadow-2xl border border-zinc-800/80" style="background-image: url('{{ asset('assets/auth-hero.jpg') }}'); background-size: cover; background-position: center;">
            <!-- Dark aesthetic gradient overlay for perfect contrast & readability -->
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/95 via-zinc-950/45 to-transparent pointer-events-none"></div>

            <div class="relative z-10 flex gap-1 mb-4">
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
            </div>

            <div class="relative z-10 mb-6 font-medium text-2xl xl:text-3xl leading-snug tracking-tight text-white max-w-xl drop-shadow-sm">
                "Pelayanan kesehatan bermutu tinggi berakar dari tata kelola SDM yang profesional, akurat, dan berintegritas."
            </div>

            <div class="relative z-10 flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-white/20 border border-white/30 flex items-center justify-center p-1.5 backdrop-blur-md shadow-lg overflow-hidden">
                    <img src="{{ asset('assets/RS-Permata-Bunda.jpg') }}" alt="Logo RS Permata Bunda" class="h-full w-auto object-contain rounded-xl" />
                </div>

                <div class="flex flex-col justify-center font-medium">
                    <div class="text-lg text-white font-semibold tracking-tight">RS Permata Bunda</div>
                    <div class="text-xs sm:text-sm text-zinc-300">Standar Pelayanan Paripurna • SIM SDM & Medis</div>
                </div>
            </div>
        </div>
    </div>
</div>

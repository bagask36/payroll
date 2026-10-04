<div class="flex min-h-screen bg-white dark:bg-zinc-950">
    <div class="flex-1 flex justify-center items-center px-4 py-8">
        <div class="w-full max-w-sm sm:w-80 sm:max-w-80 space-y-6">
            <div class="flex justify-center">
                <a href="/" class="group flex items-center gap-3">
                    <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-10 w-auto object-contain" />
                </a>
            </div>

            <flux:heading class="text-center" size="xl">SIM SDM RS Juwita</flux:heading>

            <form wire:submit="login" method="POST" class="flex flex-col gap-5">
                <flux:input 
                    wire:model="email" 
                    label="Email" 
                    type="email" 
                    placeholder="nama@juwitahospital.com" 
                    required 
                    autofocus 
                />

                <flux:field>
                    <div class="mb-2 flex justify-between items-center">
                        <flux:label>Kata Sandi</flux:label>

                        <flux:link href="mailto:sdm@juwitahospital.com?subject=Bantuan%20Akses%20SIM%20SDM%20RS%20Juwita" variant="subtle" class="text-xs">
                            Lupa kata sandi?
                        </flux:link>
                    </div>

                    <flux:input 
                        wire:model="password" 
                        type="password" 
                        placeholder="••••••••" 
                        viewable 
                        required 
                    />
                </flux:field>

                <!-- Captcha Field -->
                <flux:field>
                    <flux:label>Captcha</flux:label>
                    <div class="space-y-2 mt-1.5" x-data>
                        <div class="flex items-center gap-2">
                            <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-1 flex items-center justify-center shrink-0 shadow-2xs">
                                <img 
                                    x-ref="captchaImg" 
                                    src="{{ captcha_src('hospital') }}&t={{ $captchaKey }}" 
                                    alt="Captcha" 
                                    class="h-9 w-32 object-contain rounded select-none cursor-pointer"
                                    title="Klik gambar untuk acak kode"
                                    @click="$refs.captchaImg.src = '{{ captcha_src('hospital') }}&t=' + Date.now()"
                                />
                            </div>

                            <flux:button 
                                type="button" 
                                size="sm" 
                                variant="subtle" 
                                wire:click="reloadCaptcha"
                                @click="$refs.captchaImg.src = '{{ captcha_src('hospital') }}&t=' + Date.now()"
                                class="shrink-0 text-xs"
                                title="Muat ulang kode keamanan"
                            >
                                <flux:icon name="arrow-path" class="w-3.5 h-3.5" />
                                <span>Acak</span>
                            </flux:button>
                        </div>

                        <flux:input 
                            wire:model="captcha" 
                            placeholder="Ketik kode di atas" 
                            autocomplete="off" 
                            maxlength="6" 
                            required 
                            class="font-mono uppercase tracking-wider" 
                        />
                    </div>
                </flux:field>

                <flux:checkbox wire:model="remember" label="Ingat saya" />

                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Masuk ke Portal</span>
                    <span wire:loading>Memverifikasi...</span>
                </flux:button>
            </form>

            <flux:text class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                Akses terbatas pegawai & tenaga medis RS Juwita
            </flux:text>
        </div>
    </div>

    <div class="flex-1 p-4 max-lg:hidden">
        <div class="text-white relative rounded-2xl h-full w-full bg-zinc-900 flex flex-col items-start justify-end p-12 lg:p-16 overflow-hidden shadow-xl" style="background-image: url('https://fluxui.dev/img/demo/auth_aurora_2x.png'); background-size: cover; background-position: center;">
            <!-- Dark overlay gradient for contrast & readability -->
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/90 via-zinc-950/40 to-transparent pointer-events-none"></div>

            <div class="relative z-10 flex gap-1 mb-4">
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
                <flux:icon name="star" variant="solid" class="w-5 h-5 text-amber-400" />
            </div>

            <div class="relative z-10 mb-6 font-medium text-2xl xl:text-3xl leading-snug tracking-tight text-white max-w-xl">
                "Pelayanan kesehatan bermutu tinggi berakar dari tata kelola SDM yang profesional, akurat, dan berintegritas."
            </div>

            <div class="relative z-10 flex items-center gap-4">
                <div class="h-12 w-12 rounded-full bg-white/10 border border-white/20 flex items-center justify-center p-2 backdrop-blur-xs">
                    <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-full w-auto object-contain brightness-0 invert" />
                </div>

                <div class="flex flex-col justify-center font-medium">
                    <div class="text-base text-white font-semibold">Rumah Sakit Juwita</div>
                    <div class="text-xs text-zinc-300">Sistem Informasi Manajemen SDM & Penggajian</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
    <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
            <div class="p-3 bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200/80 dark:border-zinc-700/80 inline-flex items-center justify-center">
                <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-14 w-auto max-w-[200px] object-contain" />
            </div>
        </div>
        <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
            SIM SDM RS Juwita
        </flux:heading>
        <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
            Sistem Informasi Manajemen SDM, Presensi, dan Penggajian
        </flux:subheading>
    </div>

    <flux:card class="space-y-6 shadow-xl border-zinc-200/80 dark:border-zinc-800">
        <form wire:submit="login" method="POST" class="space-y-5">
            <flux:input 
                wire:model="email" 
                label="Alamat Email" 
                type="email" 
                placeholder="nama@juwitahospital.com"
                required 
                autofocus 
                icon="envelope"
            />

            <flux:input 
                wire:model="password" 
                label="Kata Sandi" 
                type="password" 
                placeholder="••••••••"
                required 
                viewable
                icon="key"
            />

            <div class="flex items-center justify-between">
                <flux:checkbox wire:model="remember" label="Ingat saya" />
                <span class="text-xs text-zinc-400">Hubungi SDM bila lupa akses</span>
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Masuk ke Portal</span>
                <span wire:loading>Memverifikasi...</span>
            </flux:button>
        </form>

        <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 text-center">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Akses terbatas hanya untuk pegawai dan tenaga medis RS Juwita terdaftar. Semua aktivitas login diaudit demi keamanan data.
            </p>
        </div>
    </flux:card>
</div>

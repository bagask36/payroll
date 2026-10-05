<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Administrasi & Sistem</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Penyimpanan Cloud</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="cloud" class="size-7 text-emerald-600" />
                Kapasitas & Limitasi Google Drive
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Monitoring ruang penyimpanan cloud, sisa kuota folder <span class="font-semibold text-zinc-700 dark:text-zinc-300">JUWITA-STORAGE</span>, dan konfigurasi ambang batas maksimal.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if(!empty($metrics['folder']['web_view_link']))
                <flux:button 
                    href="{{ $metrics['folder']['web_view_link'] }}" 
                    target="_blank" 
                    icon="arrow-top-right-on-square" 
                    variant="subtle"
                    class="cursor-pointer"
                >
                    Buka Google Drive
                </flux:button>
            @endif

            <flux:button 
                wire:click="refreshMetrics" 
                icon="arrow-path" 
                variant="primary" 
                wire:loading.attr="disabled"
                class="cursor-pointer"
            >
                <span wire:loading.remove wire:target="refreshMetrics">Perbarui Data</span>
                <span wire:loading wire:target="refreshMetrics">Memeriksa Cloud...</span>
            </flux:button>
        </div>
    </div>

    {{-- Feedback & Error Alerts --}}
    @if ($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 shrink-0" />
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-500 hover:text-emerald-700 cursor-pointer">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <flux:icon name="exclamation-circle" class="size-5 text-rose-600 shrink-0" />
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-rose-500 hover:text-rose-700 cursor-pointer">
                <flux:icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif

    @if ($isFolderExceeded)
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-sm flex items-start gap-3 shadow-xs">
            <flux:icon name="exclamation-triangle" class="size-6 text-rose-600 shrink-0 mt-0.5" />
            <div class="flex-1">
                <p class="font-bold">Peringatan Kritis: Batas Maksimal Folder Terlampaui!</p>
                <p class="text-xs text-rose-700 dark:text-rose-300 mt-0.5">
                    Penggunaan folder saat ini ({{ self::formatBytes($folderUsedBytes) }}) telah mencapai atau melebihi batas yang ditentukan ({{ self::formatBytes($folderMaxBytes) }}). Harap segera lakukan pengarsipan, pembersihan file usang, atau naikkan batas limit folder di bawah ini.
                </p>
            </div>
        </div>
    @elseif ($isFolderWarning)
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-sm flex items-start gap-3 shadow-xs">
            <flux:icon name="bell-alert" class="size-6 text-amber-600 shrink-0 mt-0.5" />
            <div class="flex-1">
                <p class="font-bold">Peringatan: Kapasitas Mendekati Batas Maksimal ({{ $folderUsagePercent }}%)</p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                    Penggunaan folder telah melewati ambang batas peringatan {{ $warningThresholdPercent }}%. Sisa ruang penyimpanan folder adalah {{ self::formatBytes($folderRemainingBytes) }}.
                </p>
            </div>
        </div>
    @endif

    {{-- 3 Main Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        {{-- Card 1: Sisa Kapasitas Folder --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Sisa Kuota Folder
                    </span>
                    @if($isFolderExceeded)
                        <flux:badge size="xs" color="rose" class="font-bold">Penuh</flux:badge>
                    @elseif($isFolderWarning)
                        <flux:badge size="xs" color="amber" class="font-bold">Waspada</flux:badge>
                    @else
                        <flux:badge size="xs" color="emerald" class="font-bold">Aman</flux:badge>
                    @endif
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ self::formatBytes($folderRemainingBytes) }}
                    </span>
                    <span class="text-xs text-zinc-500">tersisa</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    Batas limit: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ self::formatBytes($folderMaxBytes) }}</span>
                </p>
            </div>

            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <div class="flex justify-between text-[11px] font-medium text-zinc-500 mb-1.5">
                    <span>Terpakai: {{ self::formatBytes($folderUsedBytes) }}</span>
                    <span class="font-bold {{ $isFolderExceeded ? 'text-rose-600' : ($isFolderWarning ? 'text-amber-600' : 'text-emerald-600') }}">
                        {{ $folderUsagePercent }}%
                    </span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden">
                    <div 
                        class="h-full rounded-full transition-all duration-500 {{ $isFolderExceeded ? 'bg-rose-500' : ($isFolderWarning ? 'bg-amber-500' : 'bg-emerald-500') }}"
                        style="width: {{ min(100, $folderUsagePercent) }}%"
                    ></div>
                </div>
            </div>
        </div>

        {{-- Card 2: Penggunaan Folder JUWITA-STORAGE --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Folder Target
                    </span>
                    <flux:badge size="xs" color="teal" class="font-bold">JUWITA-STORAGE</flux:badge>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ self::formatBytes($folderUsedBytes) }}
                    </span>
                    <span class="text-xs text-zinc-500">ukuran file</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    Total <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($metrics['folder']['total_files'] ?? 0) }} file</span> tersimpan di cloud.
                </p>
            </div>

            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                <span class="truncate max-w-[170px]" title="{{ $metrics['folder']['id'] ?? '-' }}">
                    ID: {{ substr($metrics['folder']['id'] ?? '-', 0, 16) }}...
                </span>
                <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Aktif
                </span>
            </div>
        </div>

        {{-- Card 3: Storage Akun Google Drive Utama --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Akun Google Drive
                    </span>
                    <flux:badge size="xs" color="sky" class="font-bold">Cloud Quota</flux:badge>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ self::formatBytes($accountFreeBytes) }}
                    </span>
                    <span class="text-xs text-zinc-500">sisa akun</span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate" title="{{ $metrics['account']['email'] ?? '-' }}">
                    {{ $metrics['account']['email'] ?? 'Google Account' }} ({{ $metrics['account']['name'] ?? 'User' }})
                </p>
            </div>

            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <div class="flex justify-between text-[11px] font-medium text-zinc-500 mb-1.5">
                    <span>Terpakai: {{ self::formatBytes($accountUsedBytes) }} / {{ self::formatBytes($accountTotalBytes) }}</span>
                    <span class="font-bold text-sky-600">{{ $accountUsagePercent }}%</span>
                </div>
                <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden">
                    <div 
                        class="h-full rounded-full bg-sky-500 transition-all duration-500"
                        style="width: {{ min(100, $accountUsagePercent) }}%"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Konfigurasi Limitasi Folder --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4 mb-5">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 border border-emerald-200/60 dark:border-emerald-800">
                    <flux:icon name="adjustments-horizontal" class="size-5" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Konfigurasi Batas Limit Folder</h3>
                    <p class="text-xs text-zinc-500">Atur batas maksimal kapasitas penyimpanan folder dan threshold peringatan sistem.</p>
                </div>
            </div>

            @if(!empty($metrics['cached_at']))
                <span class="text-[11px] text-zinc-400">
                    Terakhir disinkronkan: {{ \Carbon\Carbon::parse($metrics['cached_at'])->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }} WIB
                </span>
            @endif
        </div>

        <form wire:submit.prevent="saveSettings" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div>
                    <flux:input 
                        wire:model="folderMaxLimitMb" 
                        type="number" 
                        label="Batas Maksimal Folder (MB)" 
                        description="Contoh: 5120 (5 GB), 10240 (10 GB), 51200 (50 GB)"
                        required 
                    />
                    <div class="text-[11px] text-zinc-400 mt-1 font-mono">
                        ≈ {{ round($folderMaxLimitMb / 1024, 2) }} GB
                    </div>
                </div>

                <div>
                    <flux:input 
                        wire:model="warningThresholdPercent" 
                        type="number" 
                        label="Ambang Batas Peringatan (%)" 
                        description="Kirim notifikasi waspada ketika terpakai mencapai nilai ini"
                        min="50"
                        max="99"
                        required 
                    />
                    <div class="text-[11px] text-zinc-400 mt-1 font-mono">
                        Peringatan aktif pada {{ round(($folderMaxLimitMb * ($warningThresholdPercent / 100)) / 1024, 2) }} GB
                    </div>
                </div>

                <div class="flex items-end">
                    <flux:button 
                        type="submit" 
                        variant="primary" 
                        class="w-full sm:w-auto cursor-pointer"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="saveSettings">Simpan Perubahan Limit</span>
                        <span wire:loading wire:target="saveSettings">Menyimpan...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </div>

    {{-- File Explorer: Daftar File di Folder JUWITA-STORAGE --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <flux:icon name="folder-open" class="size-5 text-emerald-600" />
                    File Tersimpan di JUWITA-STORAGE
                </h3>
                <p class="text-xs text-zinc-500 mt-0.5">Daftar file foto presensi dan dokumen yang tersimpan di Google Drive.</p>
            </div>

            <div class="w-full sm:w-72">
                <flux:input 
                    wire:model.live.debounce.300ms="searchQuery" 
                    placeholder="Cari nama file..." 
                    icon="magnifying-glass"
                    size="sm"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50/80 dark:bg-zinc-800/40 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Nama File</th>
                        <th class="py-3 px-4">Ukuran</th>
                        <th class="py-3 px-4">Format Mime</th>
                        <th class="py-3 px-4">Waktu Dibuat</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($folderFiles as $file)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center gap-2.5">
                                    <flux:icon name="photo" class="size-4 text-emerald-600 shrink-0" />
                                    <span class="truncate max-w-xs sm:max-w-md" title="{{ $file['name'] ?? '-' }}">
                                        {{ $file['name'] ?? '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300 font-mono text-xs">
                                {{ self::formatBytes((int) ($file['size'] ?? 0)) }}
                            </td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 text-xs">
                                {{ $file['mimeType'] ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 text-xs">
                                @if(!empty($file['createdTime']))
                                    {{ \Carbon\Carbon::parse($file['createdTime'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if(!empty($file['webViewLink']))
                                    <flux:button 
                                        href="{{ $file['webViewLink'] }}" 
                                        target="_blank" 
                                        icon="arrow-top-right-on-square" 
                                        size="xs" 
                                        variant="subtle"
                                        class="cursor-pointer"
                                    >
                                        Buka
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-zinc-400 text-sm">
                                <flux:icon name="inbox" class="size-8 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                                Tidak ada file yang ditemukan dalam folder ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

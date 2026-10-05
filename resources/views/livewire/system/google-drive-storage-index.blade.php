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
                Kapasitas & Direktori Google Drive
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Monitoring ruang penyimpanan cloud, sisa kuota folder <span class="font-semibold text-zinc-700 dark:text-zinc-300">JUWITA-STORAGE</span>, dan struktur direktori bersarang (*nested*).
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
                    Buka di Google Drive
                </flux:button>
            @endif

            <flux:button 
                wire:click="refreshMetrics" 
                wire:target="refreshMetrics"
                icon="arrow-path" 
                variant="primary" 
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
                    Penggunaan folder saat ini ({{ self::formatBytes($folderUsedBytes) }}) telah mencapai atau melebihi batas yang ditentukan ({{ self::formatBytes($folderMaxBytes) }}). Harap segera lakukan pengarsipan atau naikkan batas limit folder di bawah ini.
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
                    Total <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ number_format($metrics['folder']['total_files'] ?? 0) }} file</span> tersimpan di seluruh direktori.
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

    </div>

    {{-- Form Konfigurasi Limitasi Folder (Redesigned) --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 shadow-xs">
        {{-- Card Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800 flex items-center justify-center shrink-0">
                    <flux:icon name="adjustments-horizontal" class="size-5" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                        Kebijakan & Batas Kuota Penyimpanan
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Atur batas maksimal kuota folder <span class="font-semibold text-zinc-700 dark:text-zinc-300">JUWITA-STORAGE</span> serta ambang batas peringatan sistem.
                    </p>
                </div>
            </div>

            @if(!empty($metrics['cached_at']))
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200/80 dark:border-zinc-700">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    <span>Disinkronkan: {{ \Carbon\Carbon::parse($metrics['cached_at'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                </div>
            @endif
        </div>

        <form wire:submit.prevent="saveSettings" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {{-- Left: Inputs & Quick Presets (Col 7) --}}
                <div class="lg:col-span-7 space-y-6">
                    {{-- 1. Batas Maksimal Kuota Folder --}}
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wider">
                                Batas Maksimal Folder
                            </label>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 font-mono">
                                ≈ {{ round($folderMaxLimitMb / 1024, 2) }} GB ({{ number_format($folderMaxLimitMb) }} MB)
                            </span>
                        </div>

                        {{-- Quick Presets Chips --}}
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            <span class="text-[11px] font-medium text-zinc-400 mr-1">Pilihan Cepat:</span>
                            @foreach([10 => '10 GB', 50 => '50 GB', 100 => '100 GB', 500 => '500 GB', 1024 => '1 TB'] as $gbValue => $label)
                                @php
                                    $mbEquiv = $gbValue * 1024;
                                    $isActive = (int)$folderMaxLimitMb === $mbEquiv;
                                @endphp
                                <button 
                                    type="button" 
                                    wire:click="setPreset({{ $gbValue }})"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg border transition-all cursor-pointer {{ $isActive ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:border-emerald-400 hover:text-emerald-600' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="folderMaxLimitMb" 
                                type="number" 
                                placeholder="Masukkan nilai kuota (MB)..."
                                required
                            />
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Minimal 100 MB. Nilai disimpan dalam Megabytes (1 GB = 1024 MB).
                        </p>
                    </div>

                    {{-- 2. Ambang Batas Peringatan (%) --}}
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <label class="text-xs font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wider">
                                Ambang Batas Peringatan (Warning Threshold)
                            </label>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800 font-mono">
                                {{ $warningThresholdPercent }}% (Aktif pada {{ round(($folderMaxLimitMb * ($warningThresholdPercent / 100)) / 1024, 2) }} GB)
                            </span>
                        </div>

                        {{-- Quick Percentage Pills --}}
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            <span class="text-[11px] font-medium text-zinc-400 mr-1">Rekomendasi:</span>
                            @foreach([70 => '70%', 80 => '80% (Standar)', 90 => '90%', 95 => '95% (Kritis)'] as $pct => $label)
                                @php
                                    $isActive = (int)$warningThresholdPercent === $pct;
                                @endphp
                                <button 
                                    type="button" 
                                    wire:click="setThresholdPreset({{ $pct }})"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg border transition-all cursor-pointer {{ $isActive ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:border-amber-400 hover:text-amber-600' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="warningThresholdPercent" 
                                type="number" 
                                min="50" 
                                max="99" 
                                placeholder="Persentase peringatan..."
                                required
                            />
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Sistem akan menampilkan banner waspada jika penggunaan folder melewati persentase ini.
                        </p>
                    </div>
                </div>

                {{-- Right: Live Policy Summary Card (Col 5) --}}
                <div class="lg:col-span-5 bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-200/80 dark:border-zinc-700/60 pb-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="shield-check" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                Ringkasan Kebijakan
                            </h4>
                        </div>
                        <flux:badge size="xs" color="emerald" class="font-bold">Aktif</flux:badge>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Maksimal Folder:</span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 font-mono text-sm">
                                {{ round($folderMaxLimitMb / 1024, 2) }} GB
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Ambang Waspada ({{ $warningThresholdPercent }}%):</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 font-mono text-sm">
                                {{ round(($folderMaxLimitMb * ($warningThresholdPercent / 100)) / 1024, 2) }} GB
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Terpakai Saat Ini:</span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300 font-mono">
                                {{ self::formatBytes($folderUsedBytes) }}
                            </span>
                        </div>

                        {{-- Visual Indicator Bar --}}
                        <div class="pt-2">
                            <div class="flex justify-between text-[10px] text-zinc-400 font-mono mb-1">
                                <span>0 GB</span>
                                <span class="text-amber-600 font-bold">Waspada {{ $warningThresholdPercent }}%</span>
                                <span>Limit {{ round($folderMaxLimitMb / 1024, 2) }} GB</span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 h-3 rounded-full overflow-hidden flex relative">
                                <div 
                                    class="h-full bg-emerald-500 transition-all duration-300" 
                                    style="width: {{ $warningThresholdPercent }}%"
                                    title="Zona Aman (0 - {{ $warningThresholdPercent }}%)"
                                ></div>
                                <div 
                                    class="h-full bg-amber-500 transition-all duration-300" 
                                    style="width: {{ 100 - $warningThresholdPercent }}%"
                                    title="Zona Waspada ({{ $warningThresholdPercent }}% - 100%)"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Footer --}}
            <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="information-circle" class="size-4 text-zinc-400 shrink-0" />
                    <span>Perubahan limit akan otomatis disimpan ke database konfigurasi sistem dan dicatat pada audit log.</span>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <flux:button 
                        type="submit" 
                        variant="primary" 
                        icon="check"
                        wire:target="saveSettings"
                        class="cursor-pointer font-semibold shadow-xs"
                    >
                        <span wire:loading.remove wire:target="saveSettings">Simpan Perubahan Limit</span>
                        <span wire:loading wire:target="saveSettings">Menyimpan...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </div>

    {{-- Nested Directory Tree Explorer --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
        {{-- Explorer Header & Controls --}}
        <div class="p-5 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <flux:icon name="folder-open" class="size-5 text-emerald-600" />
                    Direktori Penyimpanan
                </h3>
                <p class="text-xs text-zinc-500 mt-0.5">
                    Klik nama atau panah folder untuk membuka dan menutup isi direktori secara bertingkat.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button 
                    wire:click="expandAll" 
                    wire:target="expandAll"
                    size="xs" 
                    variant="subtle" 
                    icon="arrows-pointing-out"
                    class="cursor-pointer"
                    title="Buka semua folder"
                >
                    <span wire:loading.remove wire:target="expandAll">Buka Semua</span>
                    <span wire:loading wire:target="expandAll">Membuka...</span>
                </flux:button>

                <flux:button 
                    wire:click="collapseAll" 
                    wire:target="collapseAll"
                    size="xs" 
                    variant="subtle" 
                    icon="arrows-pointing-in"
                    class="cursor-pointer"
                    title="Tutup semua folder"
                >
                    <span wire:loading.remove wire:target="collapseAll">Tutup Semua</span>
                    <span wire:loading wire:target="collapseAll">Menutup...</span>
                </flux:button>

                <div class="w-full sm:w-56">
                    <flux:input 
                        wire:model.live.debounce.300ms="searchQuery" 
                        placeholder="Saring folder / file..." 
                        icon="magnifying-glass"
                        size="xs"
                    />
                </div>
            </div>
        </div>

        {{-- Tree Root Badge Bar --}}
        <div class="bg-zinc-50/80 dark:bg-zinc-800/60 px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 font-semibold text-zinc-700 dark:text-zinc-200">
                <flux:icon name="home" class="size-4 text-emerald-600" />
                <span>Root: <strong>JUWITA-STORAGE</strong></span>
                <flux:badge size="xs" color="emerald" class="font-mono text-[10px]">Utama</flux:badge>
            </div>

            <div class="text-[11px] text-zinc-400 font-mono">
                Total Root: {{ count($rootItems) }} item
            </div>
        </div>

        {{-- Nested Tree Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50/50 dark:bg-zinc-800/30 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider font-semibold border-b border-zinc-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-2.5 pl-6 pr-4">Struktur Direktori & File</th>
                        <th class="py-2.5 px-3">Tipe</th>
                        <th class="py-2.5 px-3">Ukuran</th>
                        <th class="py-2.5 px-3">Waktu Modifikasi</th>
                        <th class="py-2.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    @if(count($rootItems) > 0)
                        @include('livewire.system.partials.directory-tree-node', [
                            'items' => $rootItems,
                            'level' => 0,
                            'expandedFolders' => $expandedFolders,
                            'folderItemsMap' => $folderItemsMap,
                            'loadingFolderId' => null,
                        ])
                    @else
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-400 text-sm">
                                <flux:icon name="folder-minus" class="size-9 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                                <p class="font-medium text-zinc-600 dark:text-zinc-300">Direktori ini kosong</p>
                                <p class="text-xs text-zinc-400 mt-0.5">Tidak ada item yang ditemukan pada tingkat direktori ini.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

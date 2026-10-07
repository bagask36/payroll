<div class="app-page-container">
    {{-- Header --}}
    <x-page-header 
        title="Pusat Pemberitahuan & Notifikasi" 
        subtitle="Pantauan terpadu aktivitas tugas, antrean persetujuan cuti, masa berlaku izin medis, dan status operasional RS Permata Bunda."
    >
        <x-slot:actions>
            <div class="flex items-center gap-2">
                @if($hasDismissed)
                    <flux:button wire:click="restoreDismissed" size="sm" variant="subtle" icon="arrow-path">
                        Tampilkan Notifikasi Tersembunyi
                    </flux:button>
                @endif
                <a href="{{ route('dashboard') }}">
                    <flux:button size="sm" variant="subtle" icon="home">
                        Kembali ke Dashboard
                    </flux:button>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats Summary Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Card 1: Total Notifikasi --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Notifikasi</span>
                <div class="flex size-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <flux:icon name="bell" class="size-4" />
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-zinc-100 font-mono">{{ $stats['total'] }}</span>
                <span class="text-xs text-zinc-400">Pemberitahuan aktif</span>
            </div>
        </div>

        {{-- Card 2: Perlu Tindakan Segera --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-rose-200/60 bg-white dark:border-rose-950/40 dark:bg-zinc-900 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Perlu Tindakan</span>
                <div class="flex size-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                    <flux:icon name="exclamation-circle" class="size-4" />
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 dark:text-rose-400 font-mono">{{ $stats['urgent'] }}</span>
                <span class="text-xs text-zinc-400">Prioritas tinggi</span>
            </div>
        </div>

        {{-- Card 3: Informasi & Payroll --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Informasi & Gaji</span>
                <div class="flex size-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                    <flux:icon name="information-circle" class="size-4" />
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-zinc-900 dark:text-zinc-100 font-mono">{{ $stats['info'] }}</span>
                <span class="text-xs text-zinc-400">Pemberitahuan berkala</span>
            </div>
        </div>

        {{-- Card 4: Operasional Sistem --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Layanan RS</span>
                <div class="flex size-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <flux:icon name="shield-check" class="size-4" />
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 mt-1.5">
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Operasional Normal
                </span>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar & Search --}}
    <div class="p-4 rounded-2xl border border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900 space-y-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            {{-- Search Bar --}}
            <div class="relative flex-1 max-w-md">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari kata kunci notifikasi, jenis berkas, atau tugas..." 
                    icon="magnifying-glass"
                    size="sm"
                    clearable
                />
            </div>

            {{-- Severity Dropdown --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-500 font-medium">Tingkat Urgensi:</span>
                <select 
                    wire:model.live="selectedType" 
                    class="text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5 text-zinc-700 dark:text-zinc-300 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30"
                >
                    <option value="all">Semua Tingkat</option>
                    <option value="danger">Penting / Kritis</option>
                    <option value="warning">Perlu Tindakan (Peringatan)</option>
                    <option value="info">Informasi & Pengingat</option>
                    <option value="success">Berhasil / Diterbitkan</option>
                </select>
            </div>
        </div>

        {{-- Category Tabs Horizontal Scroller --}}
        <div class="flex items-center gap-1.5 border-t border-zinc-100 dark:border-zinc-800/80 pt-3 overflow-x-auto no-scrollbar">
            @php
                $tabs = [
                    'all' => 'Semua',
                    'urgent' => 'Perlu Tindakan Segera',
                    'cuti' => 'Cuti & Izin',
                    'medis' => 'Tenaga Medis',
                    'kepegawaian' => 'Kepegawaian',
                    'presensi' => 'Presensi',
                    'payroll' => 'Slip Gaji',
                    'sistem' => 'Sistem',
                ];
            @endphp

            @foreach($tabs as $catKey => $catLabel)
                <button
                    type="button"
                    wire:click="setCategory('{{ $catKey }}')"
                    class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all shrink-0 cursor-pointer {{ $selectedCategory === $catKey ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' }}"
                >
                    @if($catKey === 'urgent')
                        <span class="inline-flex items-center gap-1">
                            <span class="size-1.5 rounded-full {{ $selectedCategory === 'urgent' ? 'bg-white' : 'bg-rose-500 animate-pulse' }}"></span>
                            {{ $catLabel }}
                        </span>
                    @else
                        {{ $catLabel }}
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Notification List --}}
    <div class="space-y-3">
        @forelse($notifications as $alert)
            @php
                $typeTheme = match($alert['type']) {
                    'danger' => [
                        'border' => 'border-rose-200/90 dark:border-rose-900/40',
                        'bg' => 'bg-rose-50/20 dark:bg-rose-950/10',
                        'icon_bg' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
                        'button' => 'variant="danger"',
                    ],
                    'warning' => [
                        'border' => 'border-amber-200/90 dark:border-amber-900/40',
                        'bg' => 'bg-amber-50/20 dark:bg-amber-950/10',
                        'icon_bg' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
                        'button' => 'variant="primary"',
                    ],
                    'info' => [
                        'border' => 'border-blue-200/80 dark:border-blue-900/30',
                        'bg' => 'bg-blue-50/15 dark:bg-blue-950/10',
                        'icon_bg' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
                        'button' => 'variant="subtle"',
                    ],
                    'success' => [
                        'border' => 'border-emerald-200/80 dark:border-emerald-900/30',
                        'bg' => 'bg-emerald-50/15 dark:bg-emerald-950/10',
                        'icon_bg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                        'button' => 'variant="primary"',
                    ],
                    default => [
                        'border' => 'border-zinc-200/80 dark:border-zinc-800',
                        'bg' => 'bg-white dark:bg-zinc-900',
                        'icon_bg' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                        'button' => 'variant="subtle"',
                    ],
                };
            @endphp

            <div class="relative overflow-hidden rounded-2xl border {{ $typeTheme['border'] }} {{ $typeTheme['bg'] }} p-4 sm:p-5 shadow-2xs transition-all hover:shadow-xs">
                <div class="flex items-start gap-3.5 sm:gap-4">
                    {{-- Alert Icon --}}
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl {{ $typeTheme['icon_bg'] }} shadow-2xs mt-0.5">
                        <flux:icon :name="$alert['icon']" class="size-5" />
                    </div>

                    {{-- Main Alert Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                    {{ $alert['category_label'] }}
                                </span>
                                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                                <flux:badge size="xs" :color="$alert['badge_color']" class="font-bold text-[10px]">
                                    {{ $alert['badge'] }}
                                </flux:badge>
                                @if(!empty($alert['is_urgent']))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.2 text-[10px] font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/40">
                                        <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        Prioritas Tinggi
                                    </span>
                                @endif
                            </div>

                            <span class="text-[11px] text-zinc-400 font-medium">
                                {{ $alert['date'] }}
                            </span>
                        </div>

                        <h3 class="mt-1 text-sm sm:text-base font-bold text-zinc-900 dark:text-zinc-100 leading-snug">
                            {{ $alert['title'] }}
                        </h3>

                        <p class="mt-1.5 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            {{ $alert['message'] }}
                        </p>

                        {{-- Action Buttons --}}
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
                            <div class="flex items-center gap-2">
                                <a href="{{ $alert['url'] }}">
                                    <flux:button size="sm" variant="primary" class="font-semibold cursor-pointer">
                                        <span>{{ $alert['action_label'] }}</span>
                                        <span class="ml-1">&rarr;</span>
                                    </flux:button>
                                </a>

                                <button 
                                    type="button" 
                                    wire:click="dismiss('{{ $alert['id'] }}')" 
                                    class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 px-2 py-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                    title="Sembunyikan notifikasi dari daftar sementara"
                                >
                                    Sembunyikan
                                </button>
                            </div>

                            <div class="text-[11px] text-zinc-400 flex items-center gap-1.5">
                                <flux:icon name="clock" class="size-3.5" />
                                <span>{{ $alert['time'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-16 text-center rounded-3xl border border-dashed border-zinc-200 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/50">
                <div class="flex size-14 mx-auto items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 mb-3 shadow-2xs">
                    <flux:icon name="check-circle" class="size-8" />
                </div>
                <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Tidak ada notifikasi yang ditemukan</h3>
                <p class="text-xs text-zinc-400 max-w-md mx-auto mt-1 leading-relaxed">
                    @if(!empty($search) || $selectedCategory !== 'all' || $selectedType !== 'all')
                        Tidak ditemukan pemberitahuan yang cocok dengan kriteria pencarian atau filter yang dipilih. Coba reset filter.
                    @else
                        Seluruh aktivitas, antrean permohonan, dan tugas operasional SIM SDM telah terselesaikan dengan baik.
                    @endif
                </p>
                @if(!empty($search) || $selectedCategory !== 'all' || $selectedType !== 'all')
                    <div class="mt-4">
                        <flux:button wire:click="$set('search', ''); $set('selectedCategory', 'all'); $set('selectedType', 'all')" size="sm" variant="subtle">
                            Reset Semua Filter
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>

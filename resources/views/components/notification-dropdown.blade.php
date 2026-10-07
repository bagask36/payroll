@php
    $user = auth()->user();
    $alerts = $user ? app(\App\Services\Notification\NotificationService::class)->getNotificationsForUser($user) : collect();
    $urgentCount = $alerts->where('is_urgent', true)->count();
@endphp

<div 
    x-data="{ 
        open: false,
        dismissed: false,
        topOffset: 72,
        calcPosition() {
            if (window.innerWidth < 640) {
                const header = this.$el.closest('header') || document.querySelector('[data-flux-header]');
                if (header) {
                    const rect = header.getBoundingClientRect();
                    this.topOffset = Math.round(rect.bottom + 8);
                }
            }
        }
    }" 
    class="relative"
    @keydown.escape.window="open = false"
    @resize.window="if (open) calcPosition()"
>
    {{-- Bell Trigger Button --}}
    <button 
        type="button" 
        @click="open = !open; if(open) { $nextTick(() => calcPosition()); }" 
        :aria-expanded="open"
        title="Pusat Peringatan & Pemberitahuan"
        class="relative flex items-center justify-center size-8.5 rounded-xl border border-zinc-200/80 bg-zinc-50/80 hover:bg-zinc-100 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-800/60 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-300 transition-all cursor-pointer shadow-2xs group focus:outline-hidden focus:ring-2 focus:ring-emerald-500/30"
    >
        <flux:icon name="bell" class="size-4 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" />

        @if($urgentCount > 0)
            <span class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-mono font-bold text-white shadow-xs ring-2 ring-white dark:ring-zinc-900 animate-pulse">
                {{ $urgentCount }}
            </span>
        @else
            <span class="absolute top-1.5 right-1.5 size-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"></span>
        @endif
    </button>

    {{-- Mobile Overlay (transparent tap-dismiss on phone) --}}
    <div 
        x-show="open" 
        x-cloak
        @click="open = false"
        class="fixed inset-0 z-40 sm:hidden"
        style="display: none;"
    ></div>

    {{-- Dropdown Alert Popover Panel --}}
    <div 
        x-show="open" 
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        :style="window.innerWidth < 640 ? { top: topOffset + 'px', maxHeight: 'calc(100dvh - ' + (topOffset + 80) + 'px)' } : {}"
        class="fixed inset-x-3.5 z-50 top-[calc(env(safe-area-inset-top,0px)+4.5rem)] max-h-[calc(100dvh-env(safe-area-inset-top,0px)-9rem)] sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2.5 sm:w-96 sm:max-w-md sm:max-h-[32rem] rounded-2xl sm:rounded-3xl border border-zinc-200/90 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden flex flex-col"
        style="display: none;"
    >
        {{-- Popover Header --}}
        <div class="relative shrink-0 overflow-hidden border-b border-zinc-100 px-3.5 py-3 sm:px-5 sm:py-4 dark:border-zinc-800 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent">
            <div class="flex items-center justify-between gap-2.5 sm:gap-3">
                <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                    <div class="flex size-8.5 sm:size-9 shrink-0 items-center justify-center rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                        <flux:icon name="bell" class="size-4 sm:size-4.5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
                            Pemberitahuan & Peringatan
                        </h4>
                        <p class="text-[10px] sm:text-[11px] text-zinc-400 sm:text-zinc-500 dark:text-zinc-400 leading-tight truncate mt-0.5">
                            Aktivitas & tugas penting
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    @if($urgentCount > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 sm:px-2.5 py-0.5 text-[10px] sm:text-[11px] font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200/60 dark:border-rose-900/40 whitespace-nowrap">
                            <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ $urgentCount }} Tindakan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 sm:px-2.5 py-0.5 text-[10px] sm:text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/40 whitespace-nowrap">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            Semua Beres
                        </span>
                    @endif

                    {{-- Close Button --}}
                    <button 
                        type="button" 
                        @click="open = false" 
                        class="flex size-7.5 sm:size-8 items-center justify-center rounded-lg sm:rounded-xl text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                        aria-label="Tutup"
                        title="Tutup"
                    >
                        <flux:icon name="x-mark" class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Alerts Scrollable List --}}
        <div class="flex-1 overflow-y-auto overscroll-contain divide-y divide-zinc-100 dark:divide-zinc-800/80">
            @forelse($alerts as $alert)
                <a 
                    href="{{ $alert['url'] }}" 
                    @click="open = false"
                    class="group flex items-start gap-3 p-3.5 sm:p-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50 active:bg-zinc-100 dark:active:bg-zinc-800"
                >
                    {{-- Alert Icon Pill --}}
                    @php
                        $iconStyle = match($alert['type']) {
                            'danger' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
                            'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
                            'info' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
                            'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                            default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                        };
                    @endphp
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-2xl {{ $iconStyle }} shadow-2xs group-hover:scale-105 transition-transform mt-0.5">
                        <flux:icon :name="$alert['icon']" class="size-4.5" />
                    </div>

                    {{-- Alert Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                {{ $alert['title'] }}
                            </span>
                            <flux:badge size="xs" :color="$alert['badge_color']" class="shrink-0 font-bold text-[10px]">
                                {{ $alert['badge'] }}
                            </flux:badge>
                        </div>
                        <p class="mt-1 text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed line-clamp-2">
                            {{ $alert['message'] }}
                        </p>
                        <div class="mt-2 flex items-center justify-between text-[10px] sm:text-[11px] text-zinc-400">
                            <span class="font-medium">{{ $alert['time'] }}</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400 group-hover:underline">Buka &rarr;</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center">
                    <flux:icon name="check-circle" class="mx-auto size-10 text-emerald-500" />
                    <p class="mt-2 text-xs font-bold text-zinc-800 dark:text-zinc-200">Tidak ada peringatan baru</p>
                    <p class="text-[11px] text-zinc-400 mt-0.5">Seluruh administrasi dan permohonan telah terselesaikan.</p>
                </div>
            @endforelse
        </div>

        {{-- Popover Footer --}}
        <div class="shrink-0 border-t border-zinc-100 bg-zinc-50/80 p-3 text-center dark:border-zinc-800 dark:bg-zinc-850">
            <a 
                href="{{ route('notifications.index') }}" 
                @click="open = false"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors"
            >
                <span>Buka Pusat Notifikasi Lengkap</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
</div>

@props([
    'unit',
    'canManage' => false,
])

<div 
    data-node-id="unit-{{ $unit->id }}"
    x-show="matchesSearch('{{ addslashes($unit->name) }}') || matchesSearch('{{ addslashes($unit->code) }}') || search.length === 0"
    class="rounded-xl transition-all duration-200 border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-850 shadow-2xs overflow-hidden"
    :class="activeDropTarget === 'unit-{{ $unit->id }}' ? 'ring-2 ring-sky-500 border-dashed border-sky-500 bg-sky-50/80 dark:bg-sky-950/40' : ''"
    @dragover="handleDragOver('unit', {{ $unit->id }}, $event)"
    @dragleave="handleDragLeave('unit', {{ $unit->id }}, $event)"
    @drop="handleDrop('unit', {{ $unit->id }}, $event)"
>
    {{-- Unit Header --}}
    <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            {{-- Drag Handle (to move unit to another department) --}}
            @if ($canManage)
                <div 
                    draggable="true"
                    @dragstart="handleDragStart('unit', {{ $unit->id }}, '{{ addslashes($unit->name) }}', $event)"
                    @dragend="handleDragEnd()"
                    class="cursor-grab active:cursor-grabbing text-zinc-400 hover:text-sky-600 dark:hover:text-sky-400 p-1 -ml-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0"
                    title="Tarik untuk memindahkan unit kerja ini ke departemen lain"
                >
                    <flux:icon name="bars-2" class="size-3.5 rotate-90" />
                </div>
            @endif

            {{-- Expand / Collapse Toggle Button --}}
            <button 
                type="button"
                @click="toggle('unit-{{ $unit->id }}')"
                class="size-6 rounded flex items-center justify-center text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-transform"
                title="Buka / Tutup formasi jabatan"
            >
                <flux:icon 
                    name="chevron-right" 
                    class="size-3.5 transition-transform duration-200" 
                    ::class="isExpanded('unit-{{ $unit->id }}') ? 'rotate-90 text-sky-600 dark:text-sky-400' : ''" 
                />
            </button>

            {{-- Unit Icon & Details --}}
            <div class="flex items-center gap-2 min-w-0">
                <div class="size-7 rounded-lg bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 flex items-center justify-center shrink-0 font-bold text-xs">
                    <flux:icon name="building-office-2" class="size-4" />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-mono font-semibold px-1.5 py-0.2 rounded bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800/80">
                            {{ $unit->code }}
                        </span>
                        <h4 class="text-xs sm:text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate">
                            {{ $unit->name }}
                        </h4>
                        @if (!$unit->is_active)
                            <flux:badge size="2xs" color="zinc">Nonaktif</flux:badge>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side Positions Badge & Drop Indicator --}}
        <div class="flex items-center gap-2 shrink-0">
            <template x-if="activeDropTarget === 'unit-{{ $unit->id }}'">
                <span class="text-xs font-semibold text-sky-600 dark:text-sky-400 animate-pulse hidden sm:inline-block">
                    &larr; Lepaskan Jabatan ke sini
                </span>
            </template>

            <flux:badge size="sm" color="sky" class="text-2xs font-medium">
                {{ $unit->positions->count() }} Jabatan
            </flux:badge>
        </div>
    </div>

    {{-- Positions List Container --}}
    <div 
        x-show="isExpanded('unit-{{ $unit->id }}')"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40 p-3 sm:p-4 space-y-2"
    >
        <div class="flex items-center justify-between text-2xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1">
            <span>Daftar Jabatan / Formasi Struktural & Fungsional:</span>
            @if ($canManage)
                <span class="lowercase font-normal">Tarik jabatan untuk memindahkan</span>
            @endif
        </div>

        @forelse ($unit->positions as $pos)
            @include('livewire.organization.partials.position-node', [
                'pos' => $pos,
                'canManage' => $canManage,
            ])
        @empty
            <div class="p-3 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 text-center text-xs text-zinc-400 bg-white/50 dark:bg-zinc-800/20">
                Belum ada formasi jabatan terdaftar di unit ini.
                @if ($canManage)
                    <span class="block text-2xs text-sky-600 dark:text-sky-400 mt-0.5">Tarik formasi jabatan dari unit lain dan lepaskan ke sini.</span>
                @endif
            </div>
        @endforelse
    </div>
</div>

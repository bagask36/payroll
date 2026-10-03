@props([
    'dept',
    'level' => 1,
    'canManage' => false,
])

<div 
    data-node-id="dept-{{ $dept->id }}"
    x-show="matchesSearch('{{ addslashes($dept->name) }}') || matchesSearch('{{ addslashes($dept->code) }}') || search.length === 0"
    class="rounded-2xl transition-all duration-200 border {{ $level === 1 ? 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs' : 'border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/70 dark:bg-zinc-800/40' }}"
    :class="activeDropTarget === 'department-{{ $dept->id }}' ? 'ring-2 ring-emerald-500 border-dashed border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40' : ''"
    @dragover="handleDragOver('department', {{ $dept->id }}, $event)"
    @dragleave="handleDragLeave('department', {{ $dept->id }}, $event)"
    @drop="handleDrop('department', {{ $dept->id }}, $event)"
>
    {{-- Node Header / Bar --}}
    <div class="p-3.5 sm:p-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            {{-- Drag Handle (for moving department itself) --}}
            @if ($canManage)
                <div 
                    draggable="true"
                    @dragstart="handleDragStart('department', {{ $dept->id }}, '{{ addslashes($dept->name) }}', $event)"
                    @dragend="handleDragEnd()"
                    class="cursor-grab active:cursor-grabbing text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 p-1 -ml-1 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors shrink-0"
                    title="Tarik untuk memindahkan departemen ini ke departemen lain atau tingkat utama"
                >
                    <flux:icon name="bars-2" class="size-4 rotate-90" />
                </div>
            @endif

            {{-- Expand / Collapse Toggle Button --}}
            <button 
                type="button"
                @click="toggle('dept-{{ $dept->id }}')"
                class="size-7 rounded-lg flex items-center justify-center text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-transform"
                title="Buka / Tutup cabang"
            >
                <flux:icon 
                    name="chevron-right" 
                    class="size-4 transition-transform duration-200" 
                    ::class="isExpanded('dept-{{ $dept->id }}') ? 'rotate-90 text-emerald-600 dark:text-emerald-400' : ''" 
                />
            </button>

            {{-- Department Icon & Title --}}
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="size-8 rounded-xl {{ $level === 1 ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300' }} flex items-center justify-center shrink-0 font-bold text-xs shadow-2xs">
                    <flux:icon name="building-office" class="size-4.5" />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded {{ $level === 1 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300 border border-teal-200 dark:border-teal-800' }}">
                            {{ $dept->code }}
                        </span>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">
                            {{ $dept->name }}
                        </h3>
                        @if (!$dept->is_active)
                            <flux:badge size="2xs" color="zinc">Nonaktif</flux:badge>
                        @endif
                    </div>
                    @if ($level === 1 && $dept->parent_id === null)
                        <span class="text-2xs text-emerald-600 dark:text-emerald-400 font-medium">Tingkat Tertinggi / Direktorat</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Side Badges & Drop Indicator --}}
        <div class="flex items-center gap-2 shrink-0">
            <template x-if="activeDropTarget === 'department-{{ $dept->id }}'">
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 animate-pulse hidden sm:inline-block">
                    &larr; Lepaskan Unit / Sub-Departemen ke sini
                </span>
            </template>

            @if ($dept->children->isNotEmpty())
                <flux:badge size="sm" color="teal">
                    {{ $dept->children->count() }} Sub-Dept
                </flux:badge>
            @endif

            <flux:badge size="sm" color="emerald">
                {{ $dept->units->count() }} Unit
            </flux:badge>
        </div>
    </div>

    {{-- Expandable Child Area --}}
    <div 
        x-show="isExpanded('dept-{{ $dept->id }}')"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="border-t border-zinc-100 dark:border-zinc-800/80 p-3.5 sm:p-5 space-y-4 bg-zinc-50/40 dark:bg-zinc-900/50 rounded-b-2xl"
    >
        {{-- Sub-Departments (if any) --}}
        @if ($dept->children->isNotEmpty())
            <div class="space-y-3 pl-3 sm:pl-6 border-l-2 border-teal-200 dark:border-teal-800/60">
                <div class="text-2xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400 flex items-center gap-1.5">
                    <flux:icon name="queue-list" class="size-3.5" />
                    Sub-Departemen / Divisi Turunan:
                </div>
                @foreach ($dept->children as $child)
                    @include('livewire.organization.partials.department-node', [
                        'dept' => $child,
                        'level' => $level + 1,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </div>
        @endif

        {{-- Units under this Department --}}
        <div class="space-y-3 pl-3 sm:pl-6 border-l-2 border-emerald-200 dark:border-emerald-800/60">
            <div class="flex items-center justify-between text-2xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                <span class="flex items-center gap-1.5">
                    <flux:icon name="building-office-2" class="size-3.5" />
                    Daftar Unit Kerja & Instalasi ({{ $dept->units->count() }}):
                </span>
                <span class="text-zinc-400 lowercase font-normal">Tarik unit untuk memindahkan</span>
            </div>

            @forelse ($dept->units as $unit)
                @include('livewire.organization.partials.unit-node', [
                    'unit' => $unit,
                    'canManage' => $canManage,
                ])
            @empty
                <div 
                    class="p-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 text-center text-xs text-zinc-400 flex flex-col items-center justify-center gap-1 py-6 bg-white/60 dark:bg-zinc-800/30"
                >
                    <flux:icon name="inbox-stack" class="size-5 text-zinc-300 dark:text-zinc-600" />
                    <span>Belum ada unit kerja terpasang pada departemen ini.</span>
                    @if ($canManage)
                        <span class="text-2xs text-emerald-600 dark:text-emerald-400 font-medium">Tarik unit dari departemen lain dan lepaskan ke kartu departemen ini.</span>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>

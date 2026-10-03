@props([
    'pos',
    'canManage' => false,
])

<div 
    x-show="matchesSearch('{{ addslashes($pos->title) }}') || matchesSearch('{{ addslashes($pos->grade_level) }}') || search.length === 0"
    class="flex items-center justify-between gap-3 p-2.5 rounded-lg border border-zinc-200/90 dark:border-zinc-700/70 bg-white dark:bg-zinc-800 transition-all hover:border-zinc-300 dark:hover:border-zinc-600 shadow-2xs group"
    :class="draggedItem && draggedItem.type === 'position' && draggedItem.id === {{ $pos->id }} ? 'opacity-40 scale-98 ring-2 ring-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/40' : ''"
>
    <div class="flex items-center gap-2.5 min-w-0">
        {{-- Drag Handle (to move position to another unit) --}}
        @if ($canManage)
            <div 
                draggable="true"
                @dragstart="handleDragStart('position', {{ $pos->id }}, '{{ addslashes($pos->title) }}', $event)"
                @dragend="handleDragEnd()"
                class="cursor-grab active:cursor-grabbing text-zinc-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors shrink-0"
                title="Tarik untuk memindahkan jabatan ini ke unit lain"
            >
                <flux:icon name="bars-2" class="size-3.5 rotate-90" />
            </div>
        @endif

        <div class="size-6 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
            <flux:icon name="briefcase" class="size-3.5" />
        </div>

        <div class="min-w-0">
            <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200 block truncate">
                {{ $pos->title }}
            </span>
        </div>
    </div>

    {{-- Grade Level Badge --}}
    <div class="shrink-0 flex items-center gap-2">
        @php
            $gradeColor = match($pos->grade_level) {
                'director' => 'purple',
                'manager' => 'amber',
                'supervisor' => 'teal',
                'specialist' => 'emerald',
                default => 'zinc',
            };
        @endphp
        <flux:badge size="2xs" color="{{ $gradeColor }}" class="uppercase font-mono tracking-wider font-semibold text-3xs">
            {{ $pos->grade_level }}
        </flux:badge>
    </div>
</div>

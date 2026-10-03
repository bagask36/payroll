@props([
    'pos',
    'canCreate' => false,
    'canUpdate' => false,
    'canDelete' => false,
    'canManage' => false,
])

<div 
    x-show="matchesSearch('{{ addslashes($pos->title) }}') || matchesSearch('{{ addslashes($pos->grade_level) }}') || search.length === 0"
    class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-zinc-200/70 dark:border-zinc-800 bg-white/80 dark:bg-zinc-800/80 hover:bg-zinc-50 dark:hover:bg-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors group w-full"
    :class="draggedItem && draggedItem.type === 'position' && draggedItem.id === {{ $pos->id }} ? 'opacity-40 ring-1 ring-zinc-400 bg-zinc-100' : ''"
>
    <div class="flex items-center gap-2 min-w-0">
        {{-- Drag Handle --}}
        @if ($canManage)
            <div 
                draggable="true"
                @dragstart="handleDragStart('position', {{ $pos->id }}, '{{ addslashes($pos->title) }}', $event)"
                @dragend="handleDragEnd()"
                class="cursor-grab active:cursor-grabbing text-zinc-300 group-hover:text-zinc-500 p-0.5 rounded transition-colors shrink-0"
                title="Seret untuk memindahkan jabatan ke unit lain"
            >
                <flux:icon name="bars-2" class="size-3 rotate-90" />
            </div>
        @endif

        <div class="size-4.5 rounded text-zinc-400 flex items-center justify-center shrink-0">
            <flux:icon name="briefcase" class="size-3.5" />
        </div>

        <span class="text-xs font-normal text-zinc-800 dark:text-zinc-200 truncate">
            {{ $pos->title }}
        </span>
    </div>

    {{-- Grade & Actions --}}
    <div class="flex items-center gap-2 shrink-0">
        <span class="text-3xs font-mono uppercase px-1.5 py-0.2 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-300">
            {{ $pos->grade_level }}
        </span>

        @if ($canUpdate || $canDelete)
            <div class="flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-opacity">
                @if ($canUpdate)
                    <button 
                        type="button"
                        wire:click="editPosition({{ $pos->id }})"
                        class="p-0.5 rounded text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                        title="Edit Jabatan"
                    >
                        <flux:icon name="pencil-square" class="size-3" />
                    </button>
                @endif

                @if ($canDelete)
                    <button 
                        type="button"
                        wire:click="confirmDelete('position', {{ $pos->id }}, '{{ addslashes($pos->title) }}')"
                        class="p-0.5 rounded text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"
                        title="Hapus Jabatan"
                    >
                        <flux:icon name="trash" class="size-3" />
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

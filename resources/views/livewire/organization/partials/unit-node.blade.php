@props([
    'unit',
    'canCreate' => false,
    'canUpdate' => false,
    'canDelete' => false,
    'canManage' => false,
])

<div 
    data-node-id="unit-{{ $unit->id }}"
    x-show="matchesSearch('{{ addslashes($unit->name) }}') || matchesSearch('{{ addslashes($unit->code) }}') || search.length === 0"
    class="space-y-1.5 w-full"
>
    {{-- Unit Row Card --}}
    <div 
        class="flex items-center justify-between p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-850 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all shadow-2xs group w-full"
        :class="activeDropTarget === 'unit-{{ $unit->id }}' ? 'ring-2 ring-sky-500 border-dashed border-sky-500 bg-sky-50/70 dark:bg-sky-950/40' : ''"
        @dragover="handleDragOver('unit', {{ $unit->id }}, $event)"
        @dragleave="handleDragLeave('unit', {{ $unit->id }}, $event)"
        @drop="handleDrop('unit', {{ $unit->id }}, $event)"
    >
        <div class="flex items-center gap-2 min-w-0">
            {{-- Drag Handle --}}
            @if ($canManage)
                <div 
                    draggable="true"
                    @dragstart="handleDragStart('unit', {{ $unit->id }}, '{{ addslashes($unit->name) }}', $event)"
                    @dragend="handleDragEnd()"
                    class="cursor-grab active:cursor-grabbing text-zinc-300 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 p-0.5 rounded transition-colors shrink-0"
                    title="Seret untuk memindahkan unit ke departemen lain"
                >
                    <flux:icon name="bars-2" class="size-3.5 rotate-90" />
                </div>
            @endif

            {{-- Toggle Button --}}
            <button 
                type="button"
                @click="toggle('unit-{{ $unit->id }}')"
                class="size-5 rounded flex items-center justify-center text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-transform"
                title="Buka / Tutup Formasi Jabatan"
            >
                <flux:icon 
                    name="chevron-right" 
                    class="size-3 transition-transform duration-200" 
                    ::class="isExpanded('unit-{{ $unit->id }}') ? 'rotate-90 text-sky-600' : ''" 
                />
            </button>

            {{-- Unit Details --}}
            <div 
                @click="toggle('unit-{{ $unit->id }}')" 
                class="flex items-center gap-2 min-w-0 cursor-pointer select-none"
            >
                <div class="size-6 rounded-md bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                    <flux:icon name="building-office-2" class="size-3.5" />
                </div>
                <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                    <span class="text-xs font-mono font-medium px-1.5 py-0.2 rounded bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        {{ $unit->code }}
                    </span>
                    <span class="text-xs sm:text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                        {{ $unit->name }}
                    </span>
                    @if (!$unit->is_active)
                        <span class="text-3xs font-mono px-1 py-0.2 rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">Nonaktif</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Side Counts & Actions --}}
        <div class="flex items-center gap-2 shrink-0">
            <template x-if="activeDropTarget === 'unit-{{ $unit->id }}'">
                <span class="text-xs font-semibold text-sky-600 dark:text-sky-400 animate-pulse hidden md:inline-block">
                    &larr; Lepaskan Jabatan di sini
                </span>
            </template>

            <span class="text-2xs text-zinc-400 font-medium">
                {{ $unit->positions->count() }} Jabatan
            </span>

            {{-- Actions --}}
            @if ($canCreate || $canUpdate || $canDelete)
                <div class="flex items-center gap-0.5 border-l border-zinc-200 dark:border-zinc-700 pl-1.5">
                    @if ($canCreate)
                        <button 
                            type="button"
                            wire:click="openCreatePosition({{ $unit->id }})" 
                            class="px-1.5 py-0.5 text-2xs font-medium rounded text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/50 hover:bg-sky-100 transition-colors"
                            title="Tambah Jabatan di unit ini"
                        >
                            + Jabatan
                        </button>
                    @endif

                    @if ($canUpdate || $canDelete)
                        <flux:dropdown align="end">
                            <button type="button" class="p-1 rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors" aria-label="Menu Aksi Unit">
                                <flux:icon name="ellipsis-vertical" class="size-3" />
                            </button>
                            <flux:menu class="w-36">
                                @if ($canUpdate)
                                    <flux:menu.item icon="pencil-square" wire:click="editUnit({{ $unit->id }})">
                                        Edit
                                    </flux:menu.item>
                                @endif
                                @if ($canDelete)
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('unit', {{ $unit->id }}, '{{ addslashes($unit->name) }}')">
                                        Hapus
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Positions List Container --}}
    <div 
        x-show="isExpanded('unit-{{ $unit->id }}')"
        class="border-l border-sky-200 dark:border-sky-900/60 ml-4 sm:ml-5 pl-3 sm:pl-4 space-y-1 py-1"
    >
        @forelse ($unit->positions as $pos)
            @include('livewire.organization.partials.position-node', [
                'pos' => $pos,
                'canCreate' => $canCreate,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
                'canManage' => $canManage,
            ])
        @empty
            <div class="py-2 px-3 rounded border border-dashed border-zinc-200 dark:border-zinc-800 text-xs text-zinc-400 bg-zinc-50/40 dark:bg-zinc-900/20 flex items-center justify-between">
                <span>Belum ada formasi jabatan di unit ini.</span>
                @if ($canCreate)
                    <button type="button" wire:click="openCreatePosition({{ $unit->id }})" class="text-sky-600 hover:underline text-2xs font-semibold">+ Tambah Jabatan</button>
                @endif
            </div>
        @endforelse
    </div>
</div>

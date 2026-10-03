@props([
    'dept',
    'level' => 1,
    'canCreate' => false,
    'canUpdate' => false,
    'canDelete' => false,
    'canManage' => false,
])

<div 
    data-node-id="dept-{{ $dept->id }}"
    x-show="matchesSearch('{{ addslashes($dept->name) }}') || matchesSearch('{{ addslashes($dept->code) }}') || search.length === 0"
    class="space-y-2 w-full"
>
    {{-- Department Row Card --}}
    <div 
        class="flex items-center justify-between p-3 rounded-xl border border-zinc-200/90 dark:border-zinc-800 bg-white dark:bg-zinc-850 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all shadow-2xs group w-full"
        :class="activeDropTarget === 'department-{{ $dept->id }}' ? 'ring-2 ring-emerald-500 border-dashed border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40' : ''"
        @dragover="handleDragOver('department', {{ $dept->id }}, $event)"
        @dragleave="handleDragLeave('department', {{ $dept->id }}, $event)"
        @drop="handleDrop('department', {{ $dept->id }}, $event)"
    >
        <div class="flex items-center gap-2.5 min-w-0">
            {{-- Drag Handle --}}
            @if ($canManage)
                <div 
                    draggable="true"
                    @dragstart="handleDragStart('department', {{ $dept->id }}, '{{ addslashes($dept->name) }}', $event)"
                    @dragend="handleDragEnd()"
                    class="cursor-grab active:cursor-grabbing text-zinc-300 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 p-0.5 rounded transition-colors shrink-0"
                    title="Seret untuk memindahkan departemen"
                >
                    <flux:icon name="bars-2" class="size-3.5 rotate-90" />
                </div>
            @endif

            {{-- Toggle Button --}}
            <button 
                type="button"
                @click="toggle('dept-{{ $dept->id }}')"
                class="size-6 rounded flex items-center justify-center text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-transform"
                title="Buka / Tutup"
            >
                <flux:icon 
                    name="chevron-right" 
                    class="size-3.5 transition-transform duration-200" 
                    ::class="isExpanded('dept-{{ $dept->id }}') ? 'rotate-90 text-emerald-600' : ''" 
                />
            </button>

            {{-- Department Icon & Title --}}
            <div 
                @click="toggle('dept-{{ $dept->id }}')" 
                class="flex items-center gap-2 min-w-0 cursor-pointer select-none"
            >
                <div class="size-6.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <flux:icon name="building-office" class="size-3.5" />
                </div>
                <div class="flex items-center gap-2 flex-wrap min-w-0">
                    <span class="text-xs font-mono font-semibold px-1.5 py-0.2 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                        {{ $dept->code }}
                    </span>
                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                        {{ $dept->name }}
                    </span>
                    @if (!$dept->is_active)
                        <span class="text-3xs font-mono px-1 py-0.2 rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">Nonaktif</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Side Counts & Actions --}}
        <div class="flex items-center gap-2 shrink-0">
            <template x-if="activeDropTarget === 'department-{{ $dept->id }}'">
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 animate-pulse hidden md:inline-block">
                    &larr; Lepaskan Unit / Jabatan di sini
                </span>
            </template>

            <span class="text-xs text-zinc-400 font-medium hidden sm:inline-block">
                @if($dept->positions->isNotEmpty())
                    {{ $dept->positions->count() }} Jabatan &bull;
                @endif
                @if($dept->children->isNotEmpty())
                    {{ $dept->children->count() }} Sub-Dept &bull;
                @endif
                {{ $dept->units->count() }} Unit
            </span>

            {{-- Actions --}}
            @if ($canCreate || $canUpdate || $canDelete)
                <div class="flex items-center gap-1 border-l border-zinc-200 dark:border-zinc-700 pl-2">
                    @if ($canCreate)
                        <button 
                            type="button"
                            wire:click="openCreatePosition(null, {{ $dept->id }})" 
                            class="px-2 py-0.8 text-2xs font-medium rounded text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 transition-colors"
                            title="Tambah Jabatan langsung di departemen ini"
                        >
                            + Jabatan
                        </button>
                        <button 
                            type="button"
                            wire:click="openCreateUnit({{ $dept->id }})" 
                            class="px-2 py-0.8 text-2xs font-medium rounded text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 transition-colors"
                            title="Tambah Unit Kerja di departemen ini"
                        >
                            + Unit
                        </button>
                    @endif

                    @if ($canUpdate || $canDelete)
                        <flux:dropdown align="end">
                            <button type="button" class="p-1 rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors" aria-label="Menu Aksi">
                                <flux:icon name="ellipsis-vertical" class="size-3.5" />
                            </button>
                            <flux:menu class="w-44">
                                @if ($canCreate)
                                    <flux:menu.item icon="plus" wire:click="openCreateDepartment({{ $dept->id }})">
                                        Sub-Departemen
                                    </flux:menu.item>
                                @endif
                                @if ($canUpdate)
                                    <flux:menu.item icon="pencil-square" wire:click="editDepartment({{ $dept->id }})">
                                        Edit Departemen
                                    </flux:menu.item>
                                @endif
                                @if ($canDelete)
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('department', {{ $dept->id }}, '{{ addslashes($dept->name) }}')">
                                        Hapus Departemen
                                    </flux:menu.item>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Expandable Children (Indented with clean vertical guide lines) --}}
    <div 
        x-show="isExpanded('dept-{{ $dept->id }}')"
        class="border-l-2 border-zinc-200 dark:border-zinc-800 ml-4 sm:ml-5 pl-3 sm:pl-4 space-y-2.5 py-1"
    >
        {{-- 1. Direct Department Positions (Leadership / Structural) --}}
        @if ($dept->positions->isNotEmpty())
            <div class="space-y-1.5 py-0.5">
                <span class="text-3xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                    <flux:icon name="briefcase" class="size-3" />
                    Jabatan Struktural Departemen ({{ $dept->positions->count() }}):
                </span>
                @foreach ($dept->positions as $deptPos)
                    @include('livewire.organization.partials.position-node', [
                        'pos' => $deptPos,
                        'canCreate' => $canCreate,
                        'canUpdate' => $canUpdate,
                        'canDelete' => $canDelete,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </div>
        @endif

        {{-- 2. Sub-departments --}}
        @if ($dept->children->isNotEmpty())
            @foreach ($dept->children as $child)
                @include('livewire.organization.partials.department-node', [
                    'dept' => $child,
                    'level' => $level + 1,
                    'canCreate' => $canCreate,
                    'canUpdate' => $canUpdate,
                    'canDelete' => $canDelete,
                    'canManage' => $canManage,
                ])
            @endforeach
        @endif

        {{-- 3. Units under this department --}}
        @forelse ($dept->units as $unit)
            @include('livewire.organization.partials.unit-node', [
                'unit' => $unit,
                'canCreate' => $canCreate,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
                'canManage' => $canManage,
            ])
        @empty
            @if ($dept->children->isEmpty() && $dept->positions->isEmpty())
                <div class="py-2.5 px-3 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 text-xs text-zinc-400 bg-zinc-50/50 dark:bg-zinc-900/30 flex items-center justify-between">
                    <span>Belum ada unit kerja atau jabatan langsung di departemen ini.</span>
                    @if ($canCreate)
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="openCreatePosition(null, {{ $dept->id }})" class="text-indigo-600 hover:underline text-2xs font-semibold">+ Jabatan</button>
                            <span>&bull;</span>
                            <button type="button" wire:click="openCreateUnit({{ $dept->id }})" class="text-emerald-600 hover:underline text-2xs font-semibold">+ Unit</button>
                        </div>
                    @endif
                </div>
            @endif
        @endforelse
    </div>
</div>

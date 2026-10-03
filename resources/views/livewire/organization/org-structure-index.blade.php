<div 
    x-data="{
        expanded: {},
        search: '',
        draggedItem: null,
        activeDropTarget: null,

        init() {
            // Expand all by default initially
            document.querySelectorAll('[data-node-id]').forEach(el => {
                const id = el.getAttribute('data-node-id');
                if (id) this.expanded[id] = true;
            });
        },

        toggle(key) {
            this.expanded[key] = !this.expanded[key];
        },

        expandAll() {
            document.querySelectorAll('[data-node-id]').forEach(el => {
                const id = el.getAttribute('data-node-id');
                if (id) this.expanded[id] = true;
            });
        },

        collapseAll() {
            this.expanded = {};
        },

        isExpanded(key) {
            if (this.search.trim().length > 0) return true;
            return !!this.expanded[key];
        },

        matchesSearch(text) {
            if (!this.search.trim()) return true;
            return text.toLowerCase().includes(this.search.toLowerCase());
        },

        handleDragStart(type, id, name, event) {
            this.draggedItem = { type, id, name };
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', JSON.stringify({ type, id, name }));
        },

        handleDragEnd() {
            this.draggedItem = null;
            this.activeDropTarget = null;
        },

        handleDragOver(targetType, targetId, event) {
            if (!this.draggedItem) return;

            // Only allow valid drop combinations:
            // 1. position -> unit
            // 2. unit -> department
            // 3. department -> department (or root)
            if (this.draggedItem.type === 'position' && targetType === 'unit') {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                this.activeDropTarget = `${targetType}-${targetId}`;
            } else if (this.draggedItem.type === 'unit' && targetType === 'department') {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                this.activeDropTarget = `${targetType}-${targetId}`;
            } else if (this.draggedItem.type === 'department' && targetType === 'department' && this.draggedItem.id !== targetId) {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                this.activeDropTarget = `${targetType}-${targetId}`;
            } else if (this.draggedItem.type === 'department' && targetType === 'root') {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                this.activeDropTarget = 'root';
            }
        },

        handleDragLeave(targetType, targetId, event) {
            if (this.activeDropTarget === `${targetType}-${targetId}`) {
                this.activeDropTarget = null;
            }
        },

        handleDrop(targetType, targetId, event) {
            event.preventDefault();
            const targetKey = `${targetType}-${targetId}`;
            this.activeDropTarget = null;

            if (!this.draggedItem) {
                try {
                    const data = JSON.parse(event.dataTransfer.getData('text/plain'));
                    this.draggedItem = data;
                } catch(e) {
                    return;
                }
            }

            const item = this.draggedItem;
            this.draggedItem = null;

            if (!item) return;

            if (item.type === 'position' && targetType === 'unit') {
                $wire.movePosition(item.id, targetId);
            } else if (item.type === 'unit' && targetType === 'department') {
                $wire.moveUnit(item.id, targetId);
            } else if (item.type === 'department' && targetType === 'department') {
                $wire.moveDepartment(item.id, targetId);
            } else if (item.type === 'department' && targetType === 'root') {
                $wire.moveDepartment(item.id, null);
            }
        }
    }"
    class="space-y-6"
>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Kepegawaian & Medis</span>
                <span class="text-xs text-zinc-400">/</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Struktur Organisasi</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="building-office" class="size-7 text-emerald-600" />
                Struktur & Bagan Organisasi RS Juwita
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Bagan hierarki faskes interaktif: Departemen, Unit Operasional, dan Posisi Jabatan. Dilengkapi kontrol lipat (expand/collapse) dan pengaturan tata letak via drag & drop.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <flux:badge size="md" color="emerald" class="px-3 py-1 font-mono shadow-2xs">
                {{ $totalDepartments }} Departemen &bull; {{ $totalUnits }} Unit &bull; {{ $totalPositions }} Jabatan
            </flux:badge>
        </div>
    </div>

    {{-- Feedback & Error Alerts --}}
    @if ($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-xs transition-all">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-xs transition-all">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-rose-600 dark:text-rose-400 shrink-0" />
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('errorMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Controls Toolbar & Instructions Banner --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Search Bar --}}
            <div class="relative flex-1 max-w-md">
                <flux:icon name="magnifying-glass" class="size-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400" />
                <input 
                    type="text" 
                    x-model="search" 
                    placeholder="Cari departemen, unit, atau jabatan..."
                    class="w-full pl-10 pr-4 py-2 bg-zinc-50 dark:bg-zinc-800/70 border border-zinc-200 dark:border-zinc-700/80 rounded-xl text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                />
                <button 
                    x-show="search.length > 0" 
                    @click="search = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                    title="Bersihkan pencarian"
                >
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            {{-- Expand / Collapse & Reorganize Controls --}}
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="filled" @click="expandAll()" icon="arrows-pointing-out">
                    Buka Semua
                </flux:button>
                <flux:button size="sm" variant="outline" @click="collapseAll()" icon="arrows-pointing-in">
                    Tutup Semua
                </flux:button>
            </div>
        </div>

        {{-- Guide Banner --}}
        @if ($canManage)
            <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 text-xs text-emerald-800 dark:text-emerald-300">
                <flux:icon name="arrows-up-down" class="size-4.5 text-emerald-600 shrink-0" />
                <div class="leading-relaxed">
                    <strong class="font-semibold">Fitur Drag & Drop Aktif:</strong>
                    Tarik ikon grip <span class="inline-block px-1 py-0.5 rounded bg-emerald-200/50 dark:bg-emerald-900/60 font-mono font-bold text-2xs">⋮⋮</span> pada suatu Posisi untuk memindahkannya ke Unit lain, atau tarik Unit ke Departemen lain untuk mengatur ulang struktur organisasi faskes.
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200/70 dark:border-zinc-700/50 text-xs text-zinc-600 dark:text-zinc-400">
                <flux:icon name="information-circle" class="size-4.5 text-zinc-400 shrink-0" />
                <div class="leading-relaxed">
                    Mode penampil (Read-Only). Anda dapat melipat dan memperluas hierarki atau menggunakan filter pencarian. Pengubahan struktur hanya dapat dilakukan oleh pengguna dengan hak akses manajerial.
                </div>
            </div>
        @endif
    </div>

    {{-- Nestable List Container --}}
    <div class="space-y-4">
        {{-- Root Drop Zone (to promote a department to root level) --}}
        @if ($canManage)
            <div 
                x-show="draggedItem && draggedItem.type === 'department'"
                @dragover="handleDragOver('root', 0, $event)"
                @dragleave="handleDragLeave('root', 0, $event)"
                @drop="handleDrop('root', 0, $event)"
                class="p-4 rounded-2xl border-2 border-dashed transition-all flex items-center justify-center gap-2 text-sm font-semibold"
                :class="activeDropTarget === 'root' ? 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 scale-[1.01]' : 'border-zinc-300 dark:border-zinc-700 text-zinc-400 hover:border-emerald-400'"
            >
                <flux:icon name="arrow-up-circle" class="size-5" />
                <span>Lepaskan di sini untuk memindahkan Departemen ke Tingkat Utama (Root)</span>
            </div>
        @endif

        {{-- Loop Root Departments --}}
        @forelse ($rootDepartments as $rootDept)
            @include('livewire.organization.partials.department-node', [
                'dept' => $rootDept,
                'level' => 1,
                'canManage' => $canManage,
            ])
        @empty
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-12 text-center text-zinc-400">
                <flux:icon name="building-office-2" class="size-12 mx-auto mb-3 text-zinc-300" />
                <h3 class="text-base font-bold text-zinc-700 dark:text-zinc-300">Belum Ada Struktur Organisasi</h3>
                <p class="text-sm mt-1">Data master departemen belum diatur dalam basis data faskes.</p>
            </div>
        @endforelse

        {{-- Standalone Departments (if any) --}}
        @if ($standaloneDepartments->isNotEmpty())
            <div class="mt-8 pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
                <div class="flex items-center gap-2 px-1">
                    <flux:icon name="exclamation-circle" class="size-4 text-amber-500" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                        Departemen Tanpa Induk Terdaftar
                    </h3>
                </div>
                @foreach ($standaloneDepartments as $standaloneDept)
                    @include('livewire.organization.partials.department-node', [
                        'dept' => $standaloneDept,
                        'level' => 1,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </div>
        @endif
    </div>

    {{-- Visual Legend --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-4 flex flex-wrap items-center justify-between gap-4 text-xs text-zinc-500 dark:text-zinc-400">
        <div class="flex flex-wrap items-center gap-6">
            <span class="font-bold text-zinc-700 dark:text-zinc-300">Keterangan Node:</span>
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-emerald-500 inline-block"></span>
                <span>Departemen / Divisi</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-sky-500 inline-block"></span>
                <span>Unit Kerja / Instalasi</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="size-3 rounded-full bg-indigo-500 inline-block"></span>
                <span>Posisi / Formasi Jabatan</span>
            </div>
        </div>
        <div class="text-2xs text-zinc-400 font-mono">
            RS Juwita Organization Engine &bull; Standard Compliant
        </div>
    </div>
</div>

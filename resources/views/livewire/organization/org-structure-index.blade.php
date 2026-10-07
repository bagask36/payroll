<div 
    x-data="{
        expanded: {},
        search: '',
        draggedItem: null,
        activeDropTarget: null,

        init() {
            // Expand all by default initially for immediate clarity
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

            if (this.draggedItem.type === 'position' && (targetType === 'unit' || targetType === 'department')) {
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

            if (item.type === 'position' && (targetType === 'unit' || targetType === 'department')) {
                $wire.movePosition(item.id, targetId, targetType);
            } else if (item.type === 'unit' && targetType === 'department') {
                $wire.moveUnit(item.id, targetId);
            } else if (item.type === 'department' && targetType === 'department') {
                $wire.moveDepartment(item.id, targetId);
            } else if (item.type === 'department' && targetType === 'root') {
                $wire.moveDepartment(item.id, null);
            }
        }
    }"
    class="space-y-6 w-full"
>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                <flux:icon name="building-office" class="size-6 text-emerald-600 dark:text-emerald-400" />
                Struktur Organisasi RS Permata Bunda
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Bagan hierarki faskes (Departemen &bull; Unit Kerja &bull; Jabatan). Klik baris untuk melipat/membuka, atau seret (drag & drop) untuk memindahkan.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-mono text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 hidden md:inline-block">
                {{ $totalDepartments }} Dept &bull; {{ $totalUnits }} Unit &bull; {{ $totalPositions }} Jabatan
            </span>

            @if ($canCreate)
                <flux:dropdown align="end">
                    <flux:button variant="primary" icon="plus" size="sm">
                        Tambah Baru
                    </flux:button>
                    <flux:menu class="w-48">
                        <flux:menu.item wire:click="openCreateDepartment()" icon="building-office">
                            Tambah Departemen
                        </flux:menu.item>
                        <flux:menu.item wire:click="openCreateUnit()" icon="building-office-2">
                            Tambah Unit Kerja
                        </flux:menu.item>
                        <flux:menu.item wire:click="openCreatePosition()" icon="briefcase">
                            Tambah Jabatan
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    </div>

    {{-- Feedback Messages --}}
    @if ($feedbackMessage)
        <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-center justify-between text-sm">
            <div class="flex items-center gap-2.5">
                <flux:icon name="check-circle" class="size-4.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-200 text-xs font-semibold">Tutup</button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 flex items-center justify-between text-sm">
            <div class="flex items-center gap-2.5">
                <flux:icon name="exclamation-triangle" class="size-4.5 text-rose-600 dark:text-rose-400 shrink-0" />
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-rose-600 hover:text-rose-800 dark:hover:text-rose-200 text-xs font-semibold">Tutup</button>
        </div>
    @endif

    {{-- Clean Minimalist Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-zinc-50 dark:bg-zinc-800/40 p-2.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800">
        {{-- Search Input --}}
        <div class="relative flex-1 max-w-sm">
            <flux:icon name="magnifying-glass" class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
            <input 
                type="text" 
                x-model="search" 
                placeholder="Cari departemen, unit, jabatan..."
                class="w-full pl-9 pr-8 py-1.5 bg-white dark:bg-zinc-850 border border-zinc-200 dark:border-zinc-700 rounded-lg text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-hidden focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
            <button 
                x-show="search.length > 0" 
                @click="search = ''"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600"
            >
                <flux:icon name="x-mark" class="size-3.5" />
            </button>
        </div>

        {{-- Expand / Collapse Buttons --}}
        <div class="flex items-center gap-2 self-end sm:self-auto">
            <button 
                type="button" 
                @click="expandAll()" 
                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-750 shadow-2xs"
            >
                <flux:icon name="arrows-pointing-out" class="size-3.5 text-zinc-400" />
                <span>Buka Semua</span>
            </button>
            <button 
                type="button" 
                @click="collapseAll()" 
                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-750 shadow-2xs"
            >
                <flux:icon name="arrows-pointing-in" class="size-3.5 text-zinc-400" />
                <span>Tutup Semua</span>
            </button>
        </div>
    </div>

    {{-- Tree View Container --}}
    <div class="space-y-3">
        {{-- Root Drop Zone (appears only when dragging a department) --}}
        @if ($canManage)
            <div 
                x-show="draggedItem && draggedItem.type === 'department'"
                @dragover="handleDragOver('root', 0, $event)"
                @dragleave="handleDragLeave('root', 0, $event)"
                @drop="handleDrop('root', 0, $event)"
                class="p-3.5 rounded-xl border-2 border-dashed transition-all flex items-center justify-center gap-2 text-xs font-semibold"
                :class="activeDropTarget === 'root' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'border-zinc-300 dark:border-zinc-700 text-zinc-400'"
            >
                <flux:icon name="arrow-up-circle" class="size-4 text-emerald-600" />
                <span>Lepaskan di sini untuk menjadikan Departemen Tingkat Utama (Root)</span>
            </div>
        @endif

        {{-- Root Departments --}}
        @forelse ($rootDepartments as $rootDept)
            @include('livewire.organization.partials.department-node', [
                'dept' => $rootDept,
                'level' => 1,
                'canCreate' => $canCreate,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
                'canManage' => $canManage,
            ])
        @empty
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-8 text-center text-zinc-400">
                <flux:icon name="building-office-2" class="size-10 mx-auto mb-2 text-zinc-300" />
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Belum ada struktur organisasi tersimpan.</p>
                @if ($canCreate)
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="openCreateDepartment()" class="mt-3">
                        Tambah Departemen Pertama
                    </flux:button>
                @endif
            </div>
        @endforelse

        {{-- Standalone Departments (if any) --}}
        @if ($standaloneDepartments->isNotEmpty())
            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-3">
                <span class="text-2xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 px-1">
                    Departemen Lainnya (Tanpa Induk):
                </span>
                @foreach ($standaloneDepartments as $standaloneDept)
                    @include('livewire.organization.partials.department-node', [
                        'dept' => $standaloneDept,
                        'level' => 1,
                        'canCreate' => $canCreate,
                        'canUpdate' => $canUpdate,
                        'canDelete' => $canDelete,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </div>
        @endif
    </div>

    {{-- Visual Legend (Simple and compact) --}}
    <div class="flex items-center justify-between text-2xs text-zinc-400 px-1 pt-2">
        <div class="flex items-center gap-4">
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-emerald-500"></span> Departemen
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-sky-500"></span> Unit Kerja
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-zinc-400"></span> Formasi Jabatan
            </span>
        </div>
        <span>Seret ikon grip <span class="font-mono">⋮⋮</span> untuk mengatur hierarki</span>
    </div>

    {{-- MODAL: CREATE / EDIT DEPARTMENT --}}
    <flux:modal wire:model="showDepartmentModal" class="w-full max-w-2xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingDeptId ? 'Edit Departemen' : 'Tambah Departemen Baru' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Kelola departemen atau divisi faskes.</flux:subheading>
        </div>

        <form wire:submit="saveDepartment" class="space-y-4">
            <flux:input wire:model="deptCode" label="Kode Departemen" placeholder="Misal: YANMED, KEP, KEU" required />
            <flux:input wire:model="deptName" label="Nama Departemen / Divisi" placeholder="Misal: Pelayanan Medik" required />
            
            <flux:select wire:model="deptParentId" label="Departemen Induk (Opsional)">
                <flux:select.option value="">-- Tingkat Tertinggi (Root) --</flux:select.option>
                @foreach($allDepartments as $d)
                    @if($editingDeptId !== $d->id)
                        <flux:select.option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</flux:select.option>
                    @endif
                @endforeach
            </flux:select>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="deptIsActive" id="deptActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="deptActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Departemen Aktif</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Departemen</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL: CREATE / EDIT UNIT --}}
    <flux:modal wire:model="showUnitModal" class="w-full max-w-2xl space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingUnitId ? 'Edit Unit Kerja' : 'Tambah Unit Kerja' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Kelola instalasi faskes atau ruangan rawat.</flux:subheading>
        </div>

        <form wire:submit="saveUnit" class="space-y-4">
            @if (!$editingUnitId)
                {{-- Master vs Custom Unit Selection --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Sumber Data Unit:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-all {{ $unitSource === 'master' ? 'border-sky-500 bg-sky-50/60 dark:bg-sky-950/40 text-sky-800 dark:text-sky-200 font-semibold' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                            <input type="radio" wire:model.live="unitSource" value="master" class="text-sky-600 focus:ring-sky-500" />
                            <span class="text-xs">Pilih dari Master</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-all {{ $unitSource === 'custom' ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 font-semibold' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                            <input type="radio" wire:model.live="unitSource" value="custom" class="text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-xs">Buat Unit Baru</span>
                        </label>
                    </div>
                </div>

                @if ($unitSource === 'master')
                    <flux:select wire:model.live="selectedMasterUnitId" label="Pilih Unit Kerja (Data Master)" required>
                        <flux:select.option value="">-- Pilih Unit dari Master --</flux:select.option>
                        @foreach($allUnits as $u)
                            <flux:select.option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }}) {{ $u->department ? '— Saat ini di ' . $u->department->name : '(Belum ada departemen)' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            @endif

            @if ($unitSource === 'custom' || $editingUnitId)
                <flux:input wire:model="unitCode" label="Kode Unit" placeholder="Misal: IGD, ICU, FAR" required />
                <flux:input wire:model="unitName" label="Nama Unit / Instalasi" placeholder="Misal: Instalasi Gawat Darurat" required />
            @endif
            
            <flux:select wire:model="unitDepartmentId" label="Departemen Penempatan" required>
                <flux:select.option value="">-- Pilih Departemen --</flux:select.option>
                @foreach($allDepartments as $d)
                    <flux:select.option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</flux:select.option>
                @endforeach
            </flux:select>

            @if ($unitSource === 'custom' || $editingUnitId)
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" wire:model="unitIsActive" id="unitActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="unitActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Unit Aktif</label>
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $unitSource === 'master' && !$editingUnitId ? 'Tautkan Unit ke Departemen' : 'Simpan Unit' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL: CREATE / EDIT POSITION --}}
    <flux:modal wire:model="showPositionModal" class="w-full max-w-3xl space-y-5">
        <div>
            <flux:heading size="lg">{{ $editingPositionId ? 'Edit Formasi Jabatan' : 'Tambah Formasi Jabatan' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Kelola formasi jabatan di Departemen atau Unit Kerja.</flux:subheading>
        </div>

        <form wire:submit="savePosition" class="space-y-4">
            @if (!$editingPositionId)
                {{-- Master vs Custom Position Selection --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Sumber Standar Jabatan:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-all {{ $positionSource === 'master' ? 'border-sky-500 bg-sky-50/60 dark:bg-sky-950/40 text-sky-800 dark:text-sky-200 font-semibold' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                            <input type="radio" wire:model.live="positionSource" value="master" class="text-sky-600 focus:ring-sky-500" />
                            <span class="text-xs">Pilih dari Master</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-all {{ $positionSource === 'custom' ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 font-semibold' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                            <input type="radio" wire:model.live="positionSource" value="custom" class="text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-xs">Ketik Jabatan Baru</span>
                        </label>
                    </div>
                </div>

                @if ($positionSource === 'master')
                    @if ($masterPositionTitles->isNotEmpty())
                        <flux:select wire:model.live="selectedMasterTitle" label="Pilih Standar Jabatan (Data Master)">
                            <flux:select.option value="">-- Pilih Standar Jabatan --</flux:select.option>
                            @foreach($masterPositionTitles as $mPos)
                                <flux:select.option value="{{ $mPos->title }}">{{ $mPos->title }} (Grade: {{ ucfirst($mPos->grade_level) }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
                            Belum ada standar jabatan di Data Master. Silakan gunakan opsi "Ketik Jabatan Baru".
                        </div>
                    @endif
                @endif
            @endif

            <flux:input wire:model="positionTitle" label="Nama / Judul Formasi Jabatan" placeholder="Misal: Direktur Medis, Dokter Jaga IGD, Karu ICU" required />
            
            {{-- Placement Level Selector --}}
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Penempatan Jabatan:</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-all {{ $positionPlacementType === 'department' ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 font-semibold' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                        <input type="radio" wire:model.live="positionPlacementType" value="department" class="text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-xs">Tingkat Departemen</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition-all {{ $positionPlacementType === 'unit' ? 'border-sky-500 bg-sky-50/60 dark:bg-sky-950/40 text-sky-800 dark:text-sky-200 font-semibold' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                        <input type="radio" wire:model.live="positionPlacementType" value="unit" class="text-sky-600 focus:ring-sky-500" />
                        <span class="text-xs">Tingkat Unit Kerja</span>
                    </label>
                </div>
            </div>

            @if ($positionPlacementType === 'department')
                <flux:select wire:model="positionDepartmentId" label="Departemen / Divisi" required>
                    <flux:select.option value="">-- Pilih Departemen --</flux:select.option>
                    @foreach($allDepartments as $d)
                        <flux:select.option value="{{ $d->id }}">{{ $d->name }} ({{ $d->code }})</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:select wire:model="positionUnitId" label="Unit Kerja Penempatan" required>
                    <flux:select.option value="">-- Pilih Unit Kerja --</flux:select.option>
                    @foreach($allUnits as $u)
                        <flux:select.option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="positionGradeLevel" label="Tingkatan / Grade" required>
                <flux:select.option value="staff">Staff (Pelaksana)</flux:select.option>
                <flux:select.option value="specialist">Specialist (Tenaga Medis / Ahli)</flux:select.option>
                <flux:select.option value="supervisor">Supervisor (Penanggung Jawab / Karu)</flux:select.option>
                <flux:select.option value="manager">Manager (Kepala Bagian / Instalasi)</flux:select.option>
                <flux:select.option value="director">Director (Direksi / Pimpinan Faskes)</flux:select.option>
            </flux:select>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Jabatan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL: DELETE CONFIRMATION --}}
    <flux:modal wire:model="showDeleteModal" class="w-full max-w-xl space-y-4">
        <div class="flex items-start gap-3">
            <div class="size-9 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <flux:icon name="exclamation-triangle" class="size-5" />
            </div>
            <div>
                <flux:heading size="lg">Konfirmasi Hapus Data</flux:heading>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    Hapus data <strong class="text-zinc-900 dark:text-zinc-100 font-semibold">{{ $deleteName }}</strong>?
                </p>
                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5">
                    Data tidak dapat dihapus jika masih memiliki sub-unit, jabatan, atau data pegawai terkait.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
            <flux:modal.close>
                <flux:button variant="subtle">Batal</flux:button>
            </flux:modal.close>
            <flux:button wire:click="executeDelete" variant="danger">
                Hapus Data
            </flux:button>
        </div>
    </flux:modal>
</div>

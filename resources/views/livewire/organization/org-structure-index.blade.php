<div class="space-y-6">
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
                Hierarki manajerial faskes dari Direksi, Departemen Medis/Umum, Unit Operasional, hingga Formasi Jabatan.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <flux:badge size="md" color="emerald" class="px-3 py-1 font-mono">
                {{ $departments->count() }} Departemen &bull; {{ $totalUnits }} Unit &bull; {{ $totalPositions }} Jabatan
            </flux:badge>
        </div>
    </div>

    {{-- Visual Hospital Structure Tree --}}
    <div class="bg-gradient-to-br from-emerald-900 to-teal-950 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
            <flux:icon name="building-office-2" class="size-64" />
        </div>
        <div class="relative z-10 flex flex-col items-center text-center space-y-3 max-w-2xl mx-auto">
            <div class="px-3 py-1 rounded-full bg-white/10 text-emerald-200 text-xs font-semibold uppercase tracking-wider">
                Tingkat Tertinggi Faskes
            </div>
            <h2 class="text-xl font-bold">Direktur Utama & Dewan Direksi RS Juwita</h2>
            <p class="text-xs text-emerald-100 leading-relaxed">
                Penanggung jawab utama tata kelola klinis, operasional rumah sakit, keselamatan pasien, dan pembinaan sumber daya manusia.
            </p>
        </div>
    </div>

    {{-- Departments Selector & Details Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Department List --}}
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 px-1">
                Daftar Departemen / Divisi
            </h3>
            <div class="space-y-2">
                @foreach ($departments as $dept)
                    <button 
                        wire:click="selectDepartment({{ $dept->id }})"
                        class="w-full text-left p-4 rounded-xl border transition-all flex items-center justify-between {{ $selectedDepartmentId === $dept->id ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 shadow-xs' : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700' }}"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-semibold {{ $selectedDepartmentId === $dept->id ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-500' }}">
                                    {{ $dept->code }}
                                </span>
                                @if($dept->is_active)
                                    <span class="size-2 rounded-full bg-emerald-500 inline-block"></span>
                                @endif
                            </div>
                            <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $dept->name }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {{ $dept->units_count }} Unit Kerja di bawah departemen
                            </div>
                        </div>
                        <flux:icon name="chevron-right" class="size-5 {{ $selectedDepartmentId === $dept->id ? 'text-emerald-600' : 'text-zinc-400' }}" />
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Right: Units & Positions under Selected Department --}}
        <div class="lg:col-span-2 space-y-4">
            @if ($selectedDept)
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-xs">
                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div>
                            <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 font-bold uppercase">{{ $selectedDept->code }}</span>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $selectedDept->name }}</h2>
                        </div>
                        <flux:badge size="sm" color="emerald">{{ $selectedDept->units->count() }} Unit Terpasang</flux:badge>
                    </div>

                    <div class="mt-4 space-y-4">
                        @forelse ($selectedDept->units as $unit)
                            <div class="p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="size-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs">
                                            {{ substr($unit->code, 0, 3) }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $unit->name }}</h4>
                                            <span class="text-xs font-mono text-zinc-500">Kode: {{ $unit->code }}</span>
                                        </div>
                                    </div>
                                    <flux:badge size="sm" color="{{ $unit->is_active ? 'emerald' : 'zinc' }}">
                                        {{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </flux:badge>
                                </div>

                                {{-- Positions under Unit --}}
                                <div class="mt-3 pl-9">
                                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider block mb-1.5">
                                        Standar Jabatan / Posisi:
                                    </span>
                                    @if ($unit->positions->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($unit->positions as $pos)
                                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-xs font-medium text-zinc-800 dark:text-zinc-200 shadow-2xs">
                                                    <flux:icon name="briefcase" class="size-3.5 text-zinc-400" />
                                                    <span>{{ $pos->title }}</span>
                                                    <span class="text-2xs text-zinc-400 font-mono">({{ $pos->grade_level }})</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs italic text-zinc-400">Belum ada formasi jabatan terdaftar pada unit ini.</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-zinc-400">
                                <flux:icon name="building-office-2" class="size-8 mx-auto mb-2 text-zinc-300" />
                                <p class="text-sm">Belum ada unit kerja yang terdaftar di departemen ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

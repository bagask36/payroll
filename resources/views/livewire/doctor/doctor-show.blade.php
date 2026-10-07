<div class="space-y-6">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('doctors.index') }}">
                <flux:button size="sm" variant="subtle" icon="arrow-left">
                    Kembali ke Direktori
                </flux:button>
            </a>
            <div class="flex items-center gap-3">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 font-mono text-sm font-bold text-white shadow-xs">
                    {{ strtoupper(substr(str_replace(['dr. ', 'drg. ', 'Sp.'], '', $doctor->name), 0, 2)) }}
                </div>
                <div>
                    <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ $doctor->name }}
                    </flux:heading>
                    <flux:subheading class="text-zinc-500 font-mono text-xs">
                        {{ $doctor->specialization }} &bull; {{ $doctor->email }}
                    </flux:subheading>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($doctor->isStrExpired() || $doctor->isSipExpired())
                <flux:badge size="lg" color="rose" icon="exclamation-triangle">
                    Lisensi Kedaluwarsa
                </flux:badge>
            @elseif($doctor->isExpiringSoon(60))
                <flux:badge size="lg" color="amber" icon="clock">
                    Lisensi &lt; 60 Hari
                </flux:badge>
            @endif

            @if($doctor->status === 'active')
                <flux:badge size="lg" color="emerald">Dokter Aktif</flux:badge>
            @else
                <flux:badge size="lg" color="zinc">Non-aktif</flux:badge>
            @endif
        </div>
    </div>

    {{-- Profile Summary Header Card --}}
    <flux:card class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6">
        <div>
            <span class="text-xs font-medium text-zinc-500">Spesialisasi Klinis</span>
            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                {{ $doctor->specialization }}
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Kategori Medis</span>
            <div class="mt-1">
                @if($doctor->employee_id)
                    <flux:badge size="sm" color="emerald">Dokter Organik RS</flux:badge>
                @else
                    <flux:badge size="sm" color="blue">Dokter Mitra / Tamu</flux:badge>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Lisensi STR (Kemenkes)</span>
            <div class="mt-1 flex flex-col gap-0.5">
                <span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                    {{ $doctor->str_number ?? '-' }}
                </span>
                @if($doctor->str_valid_until)
                    <span class="text-[11px] {{ $doctor->isStrExpired() ? 'text-rose-600 font-semibold' : 'text-zinc-400' }}">
                        s/d {{ $doctor->str_valid_until->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Izin Praktik SIP (Dinkes)</span>
            <div class="mt-1 flex flex-col gap-0.5">
                <span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                    {{ $doctor->sip_number ?? '-' }}
                </span>
                @if($doctor->sip_valid_until)
                    <span class="text-[11px] {{ $doctor->isSipExpired() ? 'text-rose-600 font-semibold' : 'text-zinc-400' }}">
                        s/d {{ $doctor->sip_valid_until->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div>
            <span class="text-xs font-medium text-zinc-500">Integrasi Pegawai Organik</span>
            <div class="mt-1">
                @if($doctor->employee)
                    <a href="{{ route('employees.show', $doctor->employee_id) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 underline">
                        <span>NIP: {{ $doctor->employee->nip }}</span>
                        <flux:icon name="arrow-top-right-on-square" class="size-3.5" />
                    </a>
                    <div class="text-[11px] text-zinc-400 mt-0.5">{{ $doctor->employee->unit?->name ?? 'Unit RS' }}</div>
                @else
                    <span class="text-xs text-zinc-400">Tidak Terhubung Pegawai</span>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Tabs Navigation (Adaptive Mobile Slider) --}}
    <div 
        x-data="{
            canScrollLeft: false,
            canScrollRight: false,
            checkScroll() {
                const el = this.$refs.tabSlider;
                if (!el) return;
                this.canScrollLeft = el.scrollLeft > 8;
                this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 8);
            },
            scrollToActive() {
                const active = this.$refs.tabSlider?.querySelector('[data-active=true]');
                if (active) {
                    active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
                this.checkScroll();
            }
        }"
        x-init="
            $nextTick(() => { scrollToActive(); checkScroll(); });
            if (window.Livewire) {
                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        $nextTick(() => { scrollToActive(); checkScroll(); });
                    });
                });
            }
        "
        class="relative border-b border-zinc-200 dark:border-zinc-800 pb-2"
    >
        <!-- Mobile Left Scroll Hint Fade -->
        <div 
            x-show="canScrollLeft" 
            x-transition.opacity
            class="pointer-events-none absolute left-0 top-0 bottom-2 w-8 bg-gradient-to-r from-zinc-50 dark:from-zinc-900 to-transparent z-10 sm:hidden"
            style="display: none;"
        ></div>

        <!-- Mobile Right Scroll Hint Fade -->
        <div 
            x-show="canScrollRight" 
            x-transition.opacity
            class="pointer-events-none absolute right-0 top-0 bottom-2 w-8 bg-gradient-to-l from-zinc-50 dark:from-zinc-900 to-transparent z-10 sm:hidden"
            style="display: none;"
        ></div>

        <div 
            x-ref="tabSlider"
            @scroll.passive="checkScroll()"
            class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar scroll-smooth -mx-4 px-4 sm:mx-0 sm:px-0 py-0.5"
        >
            <flux:button 
                size="sm" 
                icon="identification"
                :variant="$activeTab === 'bio' ? 'primary' : 'subtle'" 
                wire:click="setTab('bio')"
                data-active="{{ $activeTab === 'bio' ? 'true' : 'false' }}"
                class="shrink-0 whitespace-nowrap cursor-pointer"
            >
                Biodata & Kredensial
            </flux:button>

            <flux:button 
                size="sm" 
                icon="calendar"
                :variant="$activeTab === 'schedules' ? 'primary' : 'subtle'" 
                wire:click="setTab('schedules')"
                data-active="{{ $activeTab === 'schedules' ? 'true' : 'false' }}"
                class="shrink-0 whitespace-nowrap cursor-pointer"
            >
                Jadwal & Shift Praktik ({{ $doctor->scheduleAssignments->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="clock"
                :variant="$activeTab === 'attendance' ? 'primary' : 'subtle'" 
                wire:click="setTab('attendance')"
                data-active="{{ $activeTab === 'attendance' ? 'true' : 'false' }}"
                class="shrink-0 whitespace-nowrap cursor-pointer"
            >
                Riwayat Presensi ({{ $doctor->attendances->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="document-text"
                :variant="$activeTab === 'leave' ? 'primary' : 'subtle'" 
                wire:click="setTab('leave')"
                data-active="{{ $activeTab === 'leave' ? 'true' : 'false' }}"
                class="shrink-0 whitespace-nowrap cursor-pointer"
            >
                Cuti & Izin Dokter ({{ $doctor->leaveRequests->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="folder"
                :variant="$activeTab === 'documents' ? 'primary' : 'subtle'" 
                wire:click="setTab('documents')"
                data-active="{{ $activeTab === 'documents' ? 'true' : 'false' }}"
                class="shrink-0 whitespace-nowrap cursor-pointer"
            >
                Berkas & Dokumen ({{ $doctor->documents->count() }})
            </flux:button>

            <flux:button 
                size="sm" 
                icon="user"
                :variant="$activeTab === 'account' ? 'primary' : 'subtle'" 
                wire:click="setTab('account')"
                data-active="{{ $activeTab === 'account' ? 'true' : 'false' }}"
                class="relative inline-flex items-center gap-1.5 shrink-0 whitespace-nowrap cursor-pointer"
            >
                <span>Kelola Akun</span>
                @if(! $doctor->user_id)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white animate-pulse">
                        Belum Ada Akun
                    </span>
                @endif
            </flux:button>
        </div>
    </div>

    {{-- Feedback & Error Banners --}}
    @php
        $activeFeedback = $bioFeedback ?? $credFeedback ?? $linkFeedback ?? $docFeedback ?? $accountFeedback;
        $activeError = $bioError ?? $credError ?? $linkError ?? $docError ?? $accountError;
    @endphp

    @if($activeFeedback)
        <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span>{{ $activeFeedback }}</span>
            </div>
            <button type="button" wire:click="resetMessages" class="text-emerald-600 hover:text-emerald-800 cursor-pointer">
                <flux:icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endif

    @if($activeError)
        <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                <span>{{ $activeError }}</span>
            </div>
            <button type="button" wire:click="resetMessages" class="text-rose-600 hover:text-rose-800 cursor-pointer">
                <flux:icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endif

    {{-- TAB 1: BIODATA & KREDENSIAL --}}
    @if($activeTab === 'bio')
        <div class="space-y-4">
            {{-- Card 1: Informasi Pribadi & Kontak --}}
            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Informasi Pribadi & Kontak Medis</h3>
                    @if($canUpdateBio)
                        <flux:button variant="primary" size="sm" icon="pencil-square" wire:click="openBioModal">
                            Edit Biodata
                        </flux:button>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <span class="text-xs text-zinc-500">Nama Lengkap & Gelar</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $doctor->name }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Spesialisasi Klinis</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $doctor->specialization }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Alamat Email Rumah Sakit</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $doctor->email }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Nomor Telepon / WhatsApp</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $doctor->phone_number ?: '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Status Praktik</span>
                        <p class="mt-0.5">
                            @if($doctor->status === 'active')
                                <flux:badge size="sm" color="emerald">Aktif Praktik</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Nonaktif</flux:badge>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-zinc-500">Kode UUID Dokter</span>
                        <p class="font-mono text-xs text-zinc-400 mt-0.5">{{ $doctor->uuid ?? '-' }}</p>
                    </div>
                </div>
            </flux:card>

            {{-- Card 2: Kredensial Lisensi Medis STR & SIP --}}
            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Kredensial Lisensi Medis (STR & SIP)</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Masa berlaku dan nomor registrasi lisensi praktik kedokteran resmi.</p>
                    </div>
                    @if($canManageCredentials)
                        <flux:button variant="primary" size="sm" icon="pencil-square" wire:click="openCredentialModal">
                            Perbarui Kredensial
                        </flux:button>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- STR Box --}}
                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Surat Tanda Registrasi (STR)</span>
                            @if($doctor->isStrExpired())
                                <flux:badge size="xs" color="rose">Kedaluwarsa</flux:badge>
                            @else
                                <flux:badge size="xs" color="emerald">Berlaku Aktif</flux:badge>
                            @endif
                        </div>
                        <div class="font-mono text-base font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $doctor->str_number ?? '-' }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            Masa Berlaku s/d: 
                            <strong class="{{ $doctor->isStrExpired() ? 'text-rose-600 font-semibold' : 'text-zinc-800 dark:text-zinc-200' }}">
                                {{ $doctor->str_valid_until ? $doctor->str_valid_until->format('d F Y') : '-' }}
                            </strong>
                        </div>
                    </div>

                    {{-- SIP Box --}}
                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Surat Izin Praktik (SIP)</span>
                            @if($doctor->isSipExpired())
                                <flux:badge size="xs" color="rose">Kedaluwarsa</flux:badge>
                            @else
                                <flux:badge size="xs" color="emerald">Berlaku Aktif</flux:badge>
                            @endif
                        </div>
                        <div class="font-mono text-base font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $doctor->sip_number ?? '-' }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            Masa Berlaku s/d: 
                            <strong class="{{ $doctor->isSipExpired() ? 'text-rose-600 font-semibold' : 'text-zinc-800 dark:text-zinc-200' }}">
                                {{ $doctor->sip_valid_until ? $doctor->sip_valid_until->format('d F Y') : '-' }}
                            </strong>
                        </div>
                    </div>
                </div>

                {{-- Status Hubungan Pegawai Organik --}}
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-dashed border-zinc-200 dark:border-zinc-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-medium text-zinc-500">Integrasi Pegawai Organik RS Juwita</span>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                            @if($doctor->employee)
                                {{ $doctor->employee->full_name }} (NIP: {{ $doctor->employee->nip }}) &bull; {{ $doctor->employee->unit?->name ?? 'Unit RS' }}
                            @else
                                Dokter Kemitraan (Bukan Pegawai Organik Tetap RS Juwita)
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($doctor->employee)
                            <a href="{{ route('employees.show', $doctor->employee_id) }}">
                                <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square">
                                    Buka Profil Pegawai
                                </flux:button>
                            </a>
                        @endif
                        @if($canLinkEmployee)
                            <flux:button size="sm" variant="primary" wire:click="openLinkModal">
                                Kelola Tautan
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- TAB 2: JADWAL & SHIFT --}}
    @if($activeTab === 'schedules')
        <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
            <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        Jadwal Praktik &amp; Shift Medis
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Daftar penugasan roster jadwal praktik klinik dan jaga dokter di RS Juwita.
                    </p>
                </div>

                <a href="{{ route('schedules.index') }}">
                    <flux:button variant="primary" size="sm" icon="calendar">
                        Buka Roster Jadwal RS
                    </flux:button>
                </a>
            </div>

            @if($doctor->scheduleAssignments->isEmpty())
                <div class="p-8 text-center text-zinc-400">
                    <flux:icon name="calendar" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                    <p class="text-sm">Belum ada penugasan jadwal praktik untuk dokter ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700/80 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 font-semibold">Tanggal Praktik</th>
                                <th class="px-4 py-3.5 font-semibold">Shift Kerja</th>
                                <th class="px-4 py-3.5 font-semibold">Jam Operasional</th>
                                <th class="px-4 py-3.5 font-semibold">Unit / Poli Pelayanan</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-800 dark:text-zinc-200">
                            @foreach($doctor->scheduleAssignments as $assignment)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $assignment->schedule_date ? \Carbon\Carbon::parse($assignment->schedule_date)->format('d F Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $assignment->shift?->name ?? 'Reguler' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $assignment->shift?->start_time ?? '-' }} - {{ $assignment->shift?->end_time ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $doctor->employee?->unit?->name ?? ($doctor->specialization ? 'Poli ' . $doctor->specialization : 'Pelayanan Medis') }}
                                        </div>
                                        @if($assignment->notes)
                                            <div class="text-[11px] text-zinc-400 truncate max-w-xs">{{ $assignment->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($assignment->status === 'completed')
                                            <flux:badge size="sm" color="emerald">Selesai</flux:badge>
                                        @elseif($assignment->status === 'scheduled')
                                            <flux:badge size="sm" color="blue">Terjadwal</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">{{ ucfirst($assignment->status) }}</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 3: PRESENSI --}}
    @if($activeTab === 'attendance')
        <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
            <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        Riwayat Presensi &amp; Kehadiran Medis
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Log kehadiran waktu masuk (clock-in) dan pulang (clock-out) dokter.
                    </p>
                </div>

                <a href="{{ route('attendance.index') }}">
                    <flux:button variant="primary" size="sm" icon="clock">
                        Direktori Presensi
                    </flux:button>
                </a>
            </div>

            @if($doctor->attendances->isEmpty())
                <div class="p-8 text-center text-zinc-400">
                    <flux:icon name="clock" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                    <p class="text-sm">Belum ada catatan presensi untuk dokter ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700/80 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 font-semibold">Tanggal</th>
                                <th class="px-4 py-3.5 font-semibold">Jam Masuk (Clock In)</th>
                                <th class="px-4 py-3.5 font-semibold">Jam Keluar (Clock Out)</th>
                                <th class="px-4 py-3.5 font-semibold">Keterlambatan</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-800 dark:text-zinc-200">
                            @foreach($doctor->attendances as $att)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $att->date ? \Carbon\Carbon::parse($att->date)->format('d F Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-zinc-800 dark:text-zinc-200">
                                        {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-zinc-800 dark:text-zinc-200">
                                        {{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">
                                        @if(($att->late_minutes ?? 0) > 0)
                                            <span class="text-rose-600 font-semibold">{{ $att->late_minutes }} Menit</span>
                                        @else
                                            <span class="text-zinc-400">Tepat Waktu</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($att->status === 'present')
                                            <flux:badge size="sm" color="emerald">Hadir</flux:badge>
                                        @elseif($att->status === 'late')
                                            <flux:badge size="sm" color="amber">Terlambat</flux:badge>
                                        @elseif($att->status === 'absent')
                                            <flux:badge size="sm" color="rose">Alpa</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">{{ ucfirst($att->status ?? 'Normal') }}</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>
    @endif

    {{-- TAB 4: CUTI & IZIN --}}
    @if($activeTab === 'leave')
        <div class="space-y-6">
            {{-- Kuota Cuti --}}
            <flux:card class="space-y-4">
                <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Alokasi &amp; Saldo Hak Cuti Dokter</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Saldo hak cuti tahunan dan kuota izin medis yang dialokasikan.</p>
                </div>

                @if($doctor->leaveBalances->isEmpty())
                    <p class="text-xs text-zinc-400 italic">Belum ada alokasi saldo cuti aktif.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($doctor->leaveBalances as $balance)
                            <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ $balance->leaveType?->name ?? 'Cuti' }}</span>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                                    {{ $balance->remaining_days ?? 0 }} <span class="text-xs font-normal text-zinc-400">/ {{ $balance->allocated_days ?? 0 }} Hari</span>
                                </div>
                                <div class="text-[11px] text-zinc-400 mt-0.5">Tahun Periode {{ $balance->year }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            {{-- Riwayat Pengajuan --}}
            <flux:card class="p-0 overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-800">
                <div class="p-4 sm:p-5 border-b border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            Riwayat Pengajuan Cuti &amp; Izin
                        </h3>
                        <p class="text-xs text-zinc-500 mt-0.5">
                            Permohonan izin dan disposisi persetujuan berjenjang medis.
                        </p>
                    </div>

                    <a href="{{ route('leaves.index') }}">
                        <flux:button variant="primary" size="sm" icon="document-text">
                            Modul Cuti
                        </flux:button>
                    </a>
                </div>

                @if($doctor->leaveRequests->isEmpty())
                    <div class="p-8 text-center text-zinc-400">
                        <flux:icon name="document-text" class="size-10 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                        <p class="text-sm">Belum ada riwayat permohonan cuti atau izin.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-700/80 text-zinc-600 dark:text-zinc-300 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-4 py-3.5 font-semibold">Jenis Permohonan</th>
                                    <th class="px-4 py-3.5 font-semibold">Periode Tanggal</th>
                                    <th class="px-4 py-3.5 font-semibold">Durasi</th>
                                    <th class="px-4 py-3.5 font-semibold">Alasan Permohonan</th>
                                    <th class="px-4 py-3.5 font-semibold text-center">Status Disposisi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-800 dark:text-zinc-200">
                                @foreach($doctor->leaveRequests as $req)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $req->leaveType?->name ?? 'Izin Medis' }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                            {{ $req->start_date ? \Carbon\Carbon::parse($req->start_date)->format('d/m/Y') : '-' }} s/d {{ $req->end_date ? \Carbon\Carbon::parse($req->end_date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 font-mono font-medium">
                                            {{ $req->total_days }} Hari
                                        </td>
                                        <td class="px-4 py-3 text-zinc-500 max-w-xs truncate">
                                            {{ $req->reason }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($req->status === 'approved')
                                                <flux:badge size="sm" color="emerald">Disetujui</flux:badge>
                                            @elseif($req->status === 'rejected')
                                                <flux:badge size="sm" color="rose">Ditolak</flux:badge>
                                            @elseif($req->status === 'submitted')
                                                <flux:badge size="sm" color="amber">Menunggu Persetujuan</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="zinc">{{ ucfirst($req->status) }}</flux:badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif

    {{-- TAB 5: BERKAS & DOKUMEN --}}
    @if($activeTab === 'documents')
        @php
            $docTypeLabels = ['str' => 'STR (Surat Tanda Registrasi)', 'sip' => 'SIP (Surat Izin Praktik)', 'certificate' => 'Sertifikat Kredensial', 'ijazah' => 'Ijazah & Spesialisasi', 'contract' => 'Kontrak Kemitraan', 'other' => 'Dokumen Lainnya'];
        @endphp
        <div class="space-y-4">
            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Arsip Berkas Digital Dokter</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">PDF/JPG/PNG maks. 10 MB. Unduhan menggunakan tautan sementara (15 menit).</p>
                    </div>
                    @if($canUploadDoc)
                        <flux:button variant="primary" size="sm" icon="arrow-up-tray" wire:click="openDocModal">
                            Unggah Berkas Kredensial
                        </flux:button>
                    @endif
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($doctor->documents as $doc)
                        <div wire:key="doc-{{ $doc->id }}" class="py-3 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:icon name="document-text" class="w-6 h-6 text-zinc-400 shrink-0" />
                                <div class="min-w-0">
                                    <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $docTypeLabels[$doc->document_type] ?? strtoupper($doc->document_type) }}
                                    </span>
                                    <p class="text-xs text-zinc-400 truncate">
                                        {{ $doc->original_name }} ({{ number_format(((int) $doc->file_size) / 1024, 1) }} KB) · {{ $doc->created_at?->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ $doc->getTemporaryDownloadUrl(15) }}" target="_blank">
                                    <flux:button variant="ghost" size="xs" icon="arrow-down-tray">Unduh</flux:button>
                                </a>
                                @can('delete', $doc)
                                    <flux:button variant="danger" size="xs" icon="trash" wire:confirm="Hapus dokumen ini?" wire:click="deleteDocument({{ $doc->id }})">Hapus</flux:button>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400 italic py-4">Belum ada dokumen kredensial yang diunggah.</p>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @endif

    {{-- TAB 6: AKUN PORTAL --}}
    @if($activeTab === 'account')
        <div class="space-y-4">
            @if($generatedPasswordNotice)
                <div class="p-3 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 rounded-xl text-xs font-medium text-amber-800 dark:text-amber-200 flex items-center gap-2">
                    <flux:icon name="key" class="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                    <span>{{ $generatedPasswordNotice }}</span>
                </div>
            @endif

            <flux:card class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">Akun Pengguna Sistem</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Pengaturan akun login portal mandiri dan hak akses dokter.</p>
                    </div>
                </div>

                @if($doctor->user)
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                            <div>
                                <span class="text-xs text-zinc-500">Email Login</span>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100 mt-0.5">{{ $doctor->user->email }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-zinc-500">Peran Akses (Role)</span>
                                <p class="mt-0.5">
                                    <flux:badge size="sm" color="emerald">Dokter (doctor)</flux:badge>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-zinc-500">Status Akun Login</span>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if($doctor->user->is_active)
                                        <flux:badge size="sm" color="emerald">Aktif</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="rose">Nonaktif</flux:badge>
                                    @endif
                                    <flux:button size="xs" variant="subtle" wire:click="toggleUserStatus">
                                        {{ $doctor->user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </flux:button>
                                </div>
                            </div>
                        </div>

                        {{-- Form Reset Password --}}
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 space-y-3">
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Ganti Password Akun Portal</h4>
                            <form wire:submit="resetUserPassword" class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <flux:input 
                                        wire:model="newPassword" 
                                        label="Password Baru" 
                                        type="password" 
                                        placeholder="Minimal 8 karakter"
                                        required 
                                    />

                                    <flux:input 
                                        wire:model="newPasswordConfirmation" 
                                        label="Konfirmasi Password Baru" 
                                        type="password" 
                                        placeholder="Ulangi password baru"
                                        required 
                                    />
                                </div>

                                <div class="pt-1">
                                    <flux:button type="submit" variant="primary" size="sm">
                                        Simpan Password Baru
                                    </flux:button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="p-6 text-center space-y-4 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
                        <flux:icon name="user-plus" class="size-10 mx-auto text-zinc-400" />
                        <div>
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Dokter Belum Memiliki Akun Login</h4>
                            <p class="text-xs text-zinc-500 mt-1 max-w-md mx-auto">
                                Buat akun portal mandiri untuk memungkinkan dokter melihat jadwal praktik, catatan presensi, dan mengajukan cuti.
                            </p>
                        </div>

                        <flux:button variant="primary" size="sm" wire:click="openAccountModal">
                            Aktifkan Akun Portal Dokter
                        </flux:button>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif

    {{-- MODAL: BIODATA & KONTAK --}}
    @if($showBioModal)
        <flux:modal wire:model="showBioModal" class="w-full max-w-xl space-y-6">
            <div>
                <flux:heading size="lg">Perbarui Biodata &amp; Kontak Dokter</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Edit identitas klinis dan data komunikasi.</flux:subheading>
            </div>

            <form wire:submit="saveBio" class="space-y-4">
                <flux:input 
                    wire:model="bioName" 
                    label="Nama Lengkap & Gelar" 
                    required 
                />

                <flux:input 
                    wire:model="bioSpecialization" 
                    label="Spesialisasi Klinis" 
                    required 
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input 
                        wire:model="bioEmail" 
                        label="Alamat Email" 
                        type="email" 
                    />

                    <flux:input 
                        wire:model="bioPhone" 
                        label="Nomor Telepon / WhatsApp" 
                    />
                </div>

                <flux:select wire:model="bioStatus" label="Status Praktik">
                    <flux:select.option value="active">Aktif</flux:select.option>
                    <flux:select.option value="inactive">Nonaktif</flux:select.option>
                </flux:select>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Simpan Perubahan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: KREDENSIAL STR & SIP --}}
    @if($showCredentialModal)
        <flux:modal wire:model="showCredentialModal" class="w-full max-w-xl space-y-6">
            <div>
                <flux:heading size="lg">Perbarui Kredensial STR &amp; SIP</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Pembaruan nomor dan masa berlaku izin lisensi medis.</flux:subheading>
            </div>

            <form wire:submit="saveCredentials" class="space-y-4">
                <div class="space-y-3 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
                    <flux:input 
                        wire:model="strNumber" 
                        label="Nomor STR (Kemenkes)" 
                        required 
                    />

                    <flux:input 
                        wire:model="strValidUntil" 
                        label="Masa Berlaku STR" 
                        type="date" 
                        required 
                    />
                </div>

                <div class="space-y-3 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
                    <flux:input 
                        wire:model="sipNumber" 
                        label="Nomor SIP (Dinkes)" 
                        required 
                    />

                    <flux:input 
                        wire:model="sipValidUntil" 
                        label="Masa Berlaku SIP" 
                        type="date" 
                        required 
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Perbarui Kredensial
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: LINK PEGAWAI ORGANIK --}}
    @if($showLinkModal)
        <flux:modal wire:model="showLinkModal" class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="lg">Tautan Pegawai Organik RS</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Hubungkan dokter dengan profil pegawai organik RS Juwita.</flux:subheading>
            </div>

            <form wire:submit="saveLinkEmployee" class="space-y-4">
                <flux:select wire:model="selectedEmployeeId" label="Pilih Pegawai Organik RS">
                    <flux:select.option value="">-- Bukan Pegawai Organik (Mitra / Tamu) --</flux:select.option>
                    @foreach($availableEmployees as $emp)
                        <flux:select.option value="{{ $emp->id }}">
                            {{ $emp->full_name }} (NIP: {{ $emp->nip }}) - {{ $emp->unit?->name ?? 'RS' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Simpan Tautan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: UNGGAH DOKUMEN --}}
    @if($showDocModal)
        <flux:modal wire:model="showDocModal" class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="lg">Unggah Berkas Kredensial Medis</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">File dokumen digital pendukung (PDF, JPG, PNG maks 10MB).</flux:subheading>
            </div>

            <form wire:submit="uploadDoc" class="space-y-4">
                <flux:select wire:model="docUploadType" label="Kategori Dokumen" required>
                    <flux:select.option value="str">Scan Surat Tanda Registrasi (STR)</flux:select.option>
                    <flux:select.option value="sip">Scan Surat Izin Praktik (SIP)</flux:select.option>
                    <flux:select.option value="certificate">Sertifikat Kredensial / Pelatihan Medis</flux:select.option>
                    <flux:select.option value="ijazah">Ijazah & Spesialisasi Medis</flux:select.option>
                    <flux:select.option value="contract">Perjanjian Kerja Sama Medis</flux:select.option>
                    <flux:select.option value="other">Dokumen Pendukung Lainnya</flux:select.option>
                </flux:select>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Pilih Berkas</label>
                    <input 
                        type="file" 
                        wire:model="docUploadedFile" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-300 cursor-pointer"
                        required 
                    />
                    <div wire:loading wire:target="docUploadedFile" class="text-xs text-emerald-600 mt-1">
                        Mengunggah berkas...
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        Unggah Dokumen
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- MODAL: BUAT AKUN PORTAL --}}
    @if($showAccountModal)
        <flux:modal wire:model="showAccountModal" class="w-full max-w-md space-y-6">
            <div>
                <flux:heading size="lg">Aktivasi Akun Portal Dokter</flux:heading>
                <flux:subheading class="text-xs text-zinc-500">Buat akun pengguna mandiri untuk dokter ini.</flux:subheading>
            </div>

            <form wire:submit="createPortalAccount" class="space-y-4">
                <flux:input 
                    wire:model="accountEmail" 
                    label="Email Login Portal" 
                    type="email" 
                    placeholder="dokter@rsjuwita.co.id" 
                    required 
                />

                <div class="text-xs text-zinc-500 bg-zinc-50 dark:bg-zinc-800/40 p-3 rounded-lg">
                    Sistem akan otomatis meng-generate kata sandi sementara dan memberikan peran (role) <strong>Dokter (doctor)</strong>.
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Buat &amp; Aktifkan Akun
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>

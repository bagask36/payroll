<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Audit Trail & Jejak Kepatuhan Sistem
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Catatan komprehensif seluruh aktivitas login, presensi, pengajuan cuti, kalkulasi payroll, dan mutasi data.
            </flux:subheading>
        </div>
    </div>

    {{-- Filter Card --}}
    <flux:card class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="actionFilter" class="w-56">
                <flux:select.option value="all">Semua Kategori Aktivitas</flux:select.option>
                <flux:select.option value="auth">Autentikasi (Login / Logout)</flux:select.option>
                <flux:select.option value="attendance">Presensi & Geofence</flux:select.option>
                <flux:select.option value="leave">Cuti & Persetujuan Berjenjang</flux:select.option>
                <flux:select.option value="payroll">Penggajian & Finalisasi</flux:select.option>
                <flux:select.option value="document">Dokumen & Berkas</flux:select.option>
                <flux:select.option value="schedule">Penugasan Shift Dinas</flux:select.option>
            </flux:select>
        </div>

        <div class="w-full sm:w-72">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari user, aksi, atau IP address..." 
                icon="magnifying-glass"
            />
        </div>
    </flux:card>

    {{-- Audit Logs Table --}}
    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Waktu & Tanggal</flux:table.column>
                <flux:table.column>Pelaku (Actor)</flux:table.column>
                <flux:table.column>Aktivitas (Action)</flux:table.column>
                <flux:table.column>Entitas Target</flux:table.column>
                <flux:table.column>IP Address</flux:table.column>
                <flux:table.column align="end">Detail</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($logs as $log)
                    <flux:table.row :key="$log->id">
                        <flux:table.cell class="font-mono text-xs text-zinc-500 whitespace-nowrap">
                            <div>{{ $log->created_at->format('d/m/Y') }}</div>
                            <div class="text-[11px] text-zinc-400">{{ $log->created_at->format('H:i:s') }} WIB</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-xs">
                                {{ $log->user?->name ?? 'System' }}
                            </div>
                            <div class="text-[11px] text-zinc-400 truncate max-w-[12rem]">
                                {{ $log->user?->email ?? '-' }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $color = match(explode('.', $log->action)[0] ?? '') {
                                    'auth' => 'blue',
                                    'attendance' => 'emerald',
                                    'leave' => 'purple',
                                    'payroll' => 'amber',
                                    'document' => 'cyan',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="xs" color="{{ $color }}">
                                {{ $log->action }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-xs text-zinc-500">
                            @if($log->auditable_type)
                                <div>{{ class_basename($log->auditable_type) }}</div>
                                <div class="text-[11px] text-zinc-400">ID: {{ $log->auditable_id ?? '-' }}</div>
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-xs text-zinc-500">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:button 
                                size="xs" 
                                variant="subtle" 
                                icon="code-bracket"
                                wire:click="viewLog({{ $log->id }})"
                            >
                                Payload
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-12 text-zinc-400 italic">
                            Tidak ditemukan rekaman log aktivitas.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($logs->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $logs->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Payload Inspection Modal --}}
    @if($selectedLog)
        <flux:modal wire:model="showDetailModal" class="md:w-[36rem] space-y-4">
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Detail Payload Audit Log</flux:heading>
                    <flux:badge size="sm" color="zinc">Log #{{ $selectedLog->id }}</flux:badge>
                </div>
                <div class="mt-2 text-xs text-zinc-500 font-mono">
                    {{ $selectedLog->action }} • {{ $selectedLog->created_at->format('d M Y H:i:s') }}
                </div>
            </div>

            <div class="space-y-3 text-xs">
                @if($selectedLog->old_values && count($selectedLog->old_values) > 0)
                    <div>
                        <span class="font-bold text-zinc-500 uppercase tracking-wider block mb-1">Nilai Sebelum (Old Values):</span>
                        <pre class="p-3 bg-zinc-900 text-zinc-100 rounded-lg overflow-x-auto text-[11px] font-mono leading-relaxed">{{ json_encode($selectedLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif

                <div>
                    <span class="font-bold text-zinc-500 uppercase tracking-wider block mb-1">Nilai Perubahan (New Values / Payload):</span>
                    <pre class="p-3 bg-zinc-900 text-emerald-300 rounded-lg overflow-x-auto text-[11px] font-mono leading-relaxed">{{ json_encode($selectedLog->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>

                <div class="pt-2 text-zinc-400 text-[11px] flex justify-between">
                    <span>IP: {{ $selectedLog->ip_address ?? '127.0.0.1' }}</span>
                    <span>User Agent: {{ Str::limit($selectedLog->user_agent ?? 'Internal System / CLI', 35) }}</span>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="subtle">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>
    @endif
</div>

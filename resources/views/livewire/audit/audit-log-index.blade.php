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

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.audit-log-table />
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

<div class="max-w-2xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('leaves.index') }}">
            <flux:button size="sm" variant="subtle" icon="arrow-left">
                Kembali ke Daftar Cuti
            </flux:button>
        </a>
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Formulir Pengajuan Cuti & Izin
            </flux:heading>
            <flux:subheading class="text-zinc-500 text-xs">
                Ajukan permohonan istirahat tahunan, izin sakit, atau dispensasi dinas.
            </flux:subheading>
        </div>
    </div>

    {{-- Error Banner --}}
    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center gap-3">
            <flux:icon name="exclamation-circle" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" />
            <span class="text-sm font-medium">{{ $errorMessage }}</span>
        </div>
    @endif

    {{-- Application Form Card --}}
    <flux:card class="space-y-6 shadow-xl">
        <form wire:submit="submit" class="space-y-5">
            {{-- Leave Type Selection --}}
            <div class="space-y-2">
                <flux:select wire:model.live="leaveTypeId" label="Jenis Cuti / Izin" required>
                    @foreach($leaveTypes as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} (Maks. {{ $t->max_days_per_year ?? 'Tak Terbatas' }} Hari)</option>
                    @endforeach
                </flux:select>

                {{-- Balance Preview Badge --}}
                @if($availableBalance !== null)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs">
                        <span class="text-emerald-800 dark:text-emerald-300">Sisa Kuota Cuti Anda Tahun Ini:</span>
                        <flux:badge size="sm" color="emerald">{{ $availableBalance }} Hari Kerja</flux:badge>
                    </div>
                @endif
            </div>

            {{-- Date Range --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input 
                    wire:model.live="startDate" 
                    label="Tanggal Mulai Cuti" 
                    type="date" 
                    required 
                />

                <flux:input 
                    wire:model.live="endDate" 
                    label="Tanggal Selesai Cuti" 
                    type="date" 
                    required 
                />
            </div>

            {{-- Duration Indicator --}}
            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 flex items-center justify-between text-xs">
                <span class="text-zinc-500 font-medium">Estimasi Durasi Pengajuan:</span>
                <span class="font-bold text-base text-zinc-900 dark:text-zinc-100 font-mono">{{ $calculatedDays }} Hari</span>
            </div>

            {{-- Reason --}}
            <flux:textarea 
                wire:model="reason" 
                label="Alasan Pengajuan Cuti" 
                placeholder="Jelaskan keperluan cuti atau keterangan izin secara rinci..." 
                rows="4" 
                required 
            />

            {{-- Notice on Approval Flow --}}
            <div class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 text-xs text-blue-900 dark:text-blue-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <flux:icon name="information-circle" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    <span>Alur Persetujuan Bertingkat (SOP RS Permata Bunda):</span>
                </div>
                <p class="text-zinc-600 dark:text-zinc-400 pl-5">
                    Pengajuan akan dievaluasi terlebih dahulu oleh Kepala Unit / Supervisor (Tier 1), kemudian diteruskan ke Manajer SDM (Tier 2) untuk finalisasi dan pemotongan saldo kuota.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <a href="{{ route('leaves.index') }}">
                    <flux:button type="button" variant="subtle">
                        Batal
                    </flux:button>
                </a>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Kirim Permohonan Cuti</span>
                    <span wire:loading>Memproses...</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>

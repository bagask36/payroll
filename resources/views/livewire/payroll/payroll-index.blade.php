<div class="space-y-6">
    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Manajemen Penggajian (Payroll)
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Kalkulasi kompensasi berkala, BPJS Kesehatan & Ketenagakerjaan, serta finalisasi pencairan gaji.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @can('calculate', App\Models\PayrollRun::class)
                <flux:button variant="primary" icon="calculator" wire:click="openCalculateModal">
                    Kalkulasi Payroll Baru
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Feedback & Error Alerts --}}
    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400" />
                <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('feedbackMessage', null)">Tutup</flux:button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 rounded-xl border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 text-red-900 dark:text-red-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400 shrink-0" />
                <span class="text-sm font-medium">{{ $errorMessage }}</span>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="$set('errorMessage', null)">Tutup</flux:button>
        </div>
    @endif

    {{-- Run Selection & Status Header Card --}}
    <flux:card class="space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Pilih Batch Payroll:</label>
                <flux:select wire:model.live="selectedRunId" class="w-72">
                    @forelse($runs as $r)
                        <flux:select.option value="{{ $r->id }}">
                            {{ $r->period?->name }} ({{ $r->run_date->format('d/m/Y') }})
                        </flux:select.option>
                    @empty
                        <flux:select.option value="">Belum ada batch payroll</flux:select.option>
                    @endforelse
                </flux:select>
            </div>

            @if($selectedRun)
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-zinc-400">Status:</span>
                        @if($selectedRun->status === 'draft')
                            <flux:badge size="sm" color="zinc">Draft</flux:badge>
                        @elseif($selectedRun->status === 'calculated')
                            <flux:badge size="sm" color="amber">Terkalkulasi (Simulasi)</flux:badge>
                        @elseif($selectedRun->status === 'finalized')
                            <flux:badge size="sm" color="emerald">Final & Terkunci (Immutable)</flux:badge>
                        @elseif($selectedRun->status === 'paid')
                            <flux:badge size="sm" color="blue">Telah Dicairkan (Paid)</flux:badge>
                        @endif
                    </div>

                    @can('export', App\Models\PayrollRun::class)
                        <flux:button 
                            size="sm" 
                            variant="subtle" 
                            icon="arrow-down-tray"
                            wire:click="exportBankDisbursement"
                        >
                            Ekspor Transfer Bank (CSV)
                        </flux:button>
                    @endcan

                    @can('finalize', $selectedRun)
                        @if(!$selectedRun->isFinalized())
                            <flux:button 
                                size="sm" 
                                variant="primary" 
                                icon="lock-closed"
                                class="bg-amber-600 hover:bg-amber-700 text-white"
                                wire:click="finalizeRun"
                                wire:confirm="PERINGATAN FINANCE: Penggajian yang difinalisasi bersifat permanen dan tidak dapat diubah lagi. Lanjutkan finalisasi?"
                            >
                                Finalisasi & Kunci Penggajian
                            </flux:button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>

        {{-- KPI Summary Cards for Selected Run --}}
        @if($selectedRun)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-stat-card 
                    label="Total Penghasilan Bruto" 
                    value="Rp {{ number_format((float) $selectedRun->total_gross_pay, 0, ',', '.') }}" 
                    icon="banknotes" 
                    color="zinc" 
                    sublabel="Gaji pokok & seluruh tunjangan" 
                />
                <x-stat-card 
                    label="Total Potongan (BPJS & Pajak)" 
                    value="Rp {{ number_format((float) $selectedRun->total_deductions, 0, ',', '.') }}" 
                    icon="receipt-refund" 
                    color="red" 
                    sublabel="Iuran BPJS karyawan & potongan" 
                />
                <x-stat-card 
                    label="Total Gaji Bersih (Net Pay)" 
                    value="Rp {{ number_format((float) $selectedRun->total_net_pay, 0, ',', '.') }}" 
                    icon="check-circle" 
                    color="emerald" 
                    sublabel="Komitmen transfer pencairan bank" 
                />
                <x-stat-card 
                    label="Status Pemrosesan" 
                    value="{{ strtoupper($selectedRun->status) }}" 
                    icon="shield-check" 
                    color="blue" 
                    sublabel="Diproses: {{ $selectedRun->processedBy?->name ?? '-' }}" 
                />
            </div>
        @endif
    </flux:card>

    {{-- Items Breakdown Table --}}
    @if($selectedRun)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg" class="font-bold text-zinc-900 dark:text-zinc-100">
                        Rincian Slip Gaji Personil
                    </flux:heading>
                    <flux:badge size="xs" color="zinc">{{ $items->total() }} Pegawai</flux:badge>
                </div>
            </div>

            <livewire:tables.payroll-item-table :payroll-run-id="$selectedRunId" :key="'payroll-run-'.$selectedRunId" />
        </div>
    @else
        <flux:card class="text-center py-16">
            <flux:icon name="banknotes" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" />
            <flux:heading size="lg">Belum Ada Batch Penggajian</flux:heading>
            <flux:subheading class="max-w-md mx-auto mt-1">
                Silakan jalankan simulasi kalkulasi penggajian baru untuk periode berjalan dengan tombol di atas.
            </flux:subheading>
        </flux:card>
    @endif

    {{-- Calculate Payroll Modal --}}
    <flux:modal wire:model="showCalculateModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">Kalkulasi Payroll Baru</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Pilih periode aktif untuk menghitung gaji seluruh pegawai aktif.</flux:subheading>
        </div>

        <form wire:submit="calculatePayroll" class="space-y-4">
            <flux:select wire:model="selectedPeriodId" label="Pilih Periode Penggajian" required>
                @foreach($periods as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-lg text-xs text-amber-800 dark:text-amber-300">
                Kalkulasi akan menerapkan tarif regulasi BPJS Kesehatan (1% karyawan, 4% RS) dan BPJS Ketenagakerjaan (JHT, JP, JKK, JKM) secara otomatis.
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Mulai Kalkulasi
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Payslip Item Detail Modal --}}
    @if($viewingItem)
        <flux:modal wire:model="showItemDetailModal" class="md:w-[32rem] space-y-4">
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Slip Gaji Pegawai</flux:heading>
                    <flux:badge size="xs" color="emerald">{{ $viewingItem->run?->period?->name }}</flux:badge>
                </div>
                <div class="mt-2 text-sm">
                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $viewingItem->employee?->full_name }}</span>
                    <span class="text-zinc-400 mx-1.5">•</span>
                    <span class="font-mono text-zinc-500">{{ $viewingItem->employee?->nip }}</span>
                </div>
                <div class="text-xs text-zinc-500 mt-0.5">
                    {{ $viewingItem->employee?->unit?->name }} — {{ $viewingItem->employee?->position?->title ?? '-' }}
                </div>
            </div>

            <div class="space-y-3 text-sm">
                {{-- Earnings --}}
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Penghasilan (Earnings)</span>
                    <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                        <span class="text-zinc-600 dark:text-zinc-400">Gaji Pokok</span>
                        <span class="font-mono font-medium">Rp {{ number_format((float) $viewingItem->basic_salary, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                        <span class="text-zinc-600 dark:text-zinc-400">Tunjangan Operasional</span>
                        <span class="font-mono font-medium">Rp {{ number_format((float) $viewingItem->allowances, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Deductions --}}
                <div class="space-y-1.5 pt-2">
                    <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Potongan Resmi (Deductions)</span>
                    <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                        <span class="text-zinc-600 dark:text-zinc-400">BPJS Kesehatan Pegawai (1%)</span>
                        <span class="font-mono text-rose-600">-Rp {{ number_format((float) $viewingItem->bpjs_kes_employee, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                        <span class="text-zinc-600 dark:text-zinc-400">BPJS TK (JHT & JP Pegawai)</span>
                        <span class="font-mono text-rose-600">-Rp {{ number_format((float) $viewingItem->bpjs_tk_employee, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                        <span class="text-zinc-600 dark:text-zinc-400">PPh 21 (Pajak Penghasilan)</span>
                        <span class="font-mono text-rose-600">-Rp {{ number_format((float) $viewingItem->pph21_tax, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Net Pay --}}
                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex justify-between items-center">
                    <div>
                        <div class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 uppercase">Gaji Bersih Diterima (Take Home Pay)</div>
                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400">Ditransfer ke {{ $viewingItem->bank_name ?? 'Bank' }}</div>
                    </div>
                    <span class="text-xl font-bold font-mono text-emerald-700 dark:text-emerald-400">
                        Rp {{ number_format((float) $viewingItem->net_salary, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <a href="{{ route('payroll.payslips.print', $viewingItem->id) }}" target="_blank">
                    <flux:button size="sm" variant="primary" icon="printer">
                        Cetak Slip Resmi (PDF)
                    </flux:button>
                </a>

                <flux:modal.close>
                    <flux:button variant="subtle" size="sm">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>
    @endif
</div>

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

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @can('calculate', App\Models\PayrollRun::class)
                <flux:button variant="subtle" icon="sparkles" wire:click="openThrModal" class="border border-purple-500/30 text-purple-700 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-950/40">
                    Kalkulasi THR (Off-Cycle)
                </flux:button>
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
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            {{-- Left: Batch Selector & Status Badges --}}
            <div class="flex flex-wrap items-center gap-3 min-w-0">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-zinc-500 uppercase tracking-wider shrink-0">Batch Payroll:</label>
                    <flux:select wire:model.live="selectedRunId" class="w-64 sm:w-72">
                        @forelse($runs as $r)
                            <flux:select.option value="{{ $r->id }}">
                                {{ $r->isThr() ? '🎉 [THR] ' : '' }}{{ $r->period?->name }} ({{ $r->run_date->format('d/m/Y') }})
                            </flux:select.option>
                        @empty
                            <flux:select.option value="">Belum ada batch payroll</flux:select.option>
                        @endforelse
                    </flux:select>
                </div>

                @if($selectedRun)
                    <div class="flex items-center gap-2">
                        @if($selectedRun->status === 'draft')
                            <flux:badge size="sm" color="zinc">Draft</flux:badge>
                        @elseif($selectedRun->status === 'calculated')
                            <flux:badge size="sm" color="amber">Terkalkulasi (Simulasi)</flux:badge>
                        @elseif($selectedRun->status === 'finalized')
                            <flux:badge size="sm" color="emerald">Final & Terkunci</flux:badge>
                        @elseif($selectedRun->status === 'paid')
                            <flux:badge size="sm" color="blue">Telah Dicairkan (Paid)</flux:badge>
                        @endif

                        @if($selectedRun->isThr())
                            <flux:badge size="sm" color="purple" icon="sparkles">Off-Cycle THR</flux:badge>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right: Actions (CSV Export & Finalize) --}}
            @if($selectedRun)
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @can('export', App\Models\PayrollRun::class)
                        <flux:button 
                            size="sm" 
                            variant="subtle" 
                            icon="arrow-down-tray"
                            wire:click="exportBankDisbursement"
                        >
                            Ekspor Transfer (CSV)
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

        {{-- Off-Cycle THR Metadata Banner --}}
        @if($selectedRun && $selectedRun->isThr())
            <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-xl bg-purple-50/80 dark:bg-purple-950/30 border border-purple-200/80 dark:border-purple-800/60 text-xs">
                <div class="flex items-center gap-2.5 text-purple-900 dark:text-purple-200 font-medium">
                    <flux:icon name="sparkles" class="size-4 shrink-0 text-purple-600 dark:text-purple-400" />
                    <span><strong>Event THR:</strong> {{ $selectedRun->metadata['holiday_name'] ?? 'Tunjangan Hari Raya' }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-purple-700 dark:text-purple-300">
                    <span>Cutoff Masa Kerja: <strong class="font-mono">{{ \Carbon\Carbon::parse($selectedRun->metadata['cutoff_date'] ?? $selectedRun->run_date)->format('d/m/Y') }}</strong></span>
                    <span>•</span>
                    <span>Penerima: <strong class="capitalize">{{ $selectedRun->metadata['target_religion'] === 'all' || empty($selectedRun->metadata['target_religion']) ? 'Semua Karyawan' : $selectedRun->metadata['target_religion'] }}</strong></span>
                    <span>•</span>
                    <span>Total Personil: <strong>{{ $selectedRun->metadata['processed_count'] ?? $selectedRun->items()->count() }} Pegawai</strong></span>
                </div>
            </div>
        @endif

        {{-- KPI Summary Cards for Selected Run --}}
        @if($selectedRun)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-stat-card 
                    label="Total Penghasilan" 
                    value="Rp {{ number_format((float) $selectedRun->total_gross_pay, 0, ',', '.') }}" 
                    icon="banknotes" 
                    color="zinc" 
                    hint="Gaji pokok & seluruh tunjangan" 
                />
                <x-stat-card 
                    label="Total Potongan" 
                    value="Rp {{ number_format((float) $selectedRun->total_deductions, 0, ',', '.') }}" 
                    icon="receipt-refund" 
                    color="red" 
                    hint="{{ $selectedRun->isThr() ? 'PPh 21 atas THR (Bebas BPJS)' : 'Iuran BPJS & PPh 21' }}" 
                />
                <x-stat-card 
                    label="Total Gaji Bersih" 
                    value="Rp {{ number_format((float) $selectedRun->total_net_pay, 0, ',', '.') }}" 
                    icon="check-circle" 
                    color="emerald" 
                    hint="Komitmen transfer bank" 
                />
                <x-stat-card 
                    label="Status Batch" 
                    value="{{ strtoupper($selectedRun->status) }}" 
                    icon="shield-check" 
                    color="blue" 
                    hint="Diproses: {{ $selectedRun->processedBy?->name ?? '-' }}" 
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
    <flux:modal wire:model="showCalculateModal" class="md:w-xl space-y-4">
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

    {{-- Calculate THR Modal --}}
    <flux:modal wire:model="showThrModal" class="md:w-2xl space-y-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon name="sparkles" class="size-5 text-emerald-600" />
                <flux:heading size="lg">Kalkulasi THR (Off-Cycle)</flux:heading>
            </div>
            <flux:subheading class="text-xs text-zinc-500">
                Perhitungan proporsional masa kerja (Permenaker No. 6/2016), bebas potongan BPJS, dan pemotongan PPh 21 TER.
            </flux:subheading>
        </div>

        <form wire:submit="calculateThr" class="space-y-4">
            <flux:select wire:model="selectedThrPeriodId" label="Pilih Periode Terkait" required>
                @foreach($periods as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input 
                wire:model="thrHolidayName" 
                label="Nama Event / Hari Raya" 
                placeholder="Contoh: THR Idul Fitri 1447 H / THR Natal 2026" 
                required 
            />

            <flux:input 
                type="date" 
                wire:model="thrCutoffDate" 
                label="Tanggal Acuan Cutoff Masa Kerja" 
                required 
            />

            <flux:select wire:model="thrTargetReligion" label="Target Agama / Penerima">
                <flux:select.option value="all">Semua Karyawan (Serentak)</flux:select.option>
                <flux:select.option value="islam">Khusus Muslim (Idul Fitri)</flux:select.option>
                <flux:select.option value="kristen">Khusus Kristen Protestan (Natal)</flux:select.option>
                <flux:select.option value="katolik">Khusus Katolik (Natal)</flux:select.option>
                <flux:select.option value="hindu">Khusus Hindu (Nyepi)</flux:select.option>
                <flux:select.option value="buddha">Khusus Buddha (Waisak)</flux:select.option>
                <flux:select.option value="khonghucu">Khusus Khonghucu (Imlek)</flux:select.option>
            </flux:select>

            <div class="flex items-center gap-2 pt-1">
                <flux:checkbox 
                    wire:model="thrIncludePositional" 
                    label="Sertakan Tunjangan Jabatan / Tunjangan Tetap" 
                />
            </div>

            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 space-y-1">
                <div class="font-semibold">Aturan Otomatis Permenaker No. 6/2016:</div>
                <ul class="list-disc list-inside text-[11px] space-y-0.5 text-emerald-700 dark:text-emerald-400">
                    <li>Masa kerja &ge; 12 bulan: 1 bulan upah penuh (100%).</li>
                    <li>Masa kerja 1 s.d. &lt; 12 bulan: Prorata (Masa Kerja / 12).</li>
                    <li>Masa kerja &lt; 1 bulan: Tidak berhak (Rp 0).</li>
                    <li>Iuran BPJS Kesehatan & Ketenagakerjaan = Rp 0.</li>
                </ul>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 text-white" wire:loading.attr="disabled">
                    Proses Kalkulasi THR
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Payslip Item Detail Modal --}}
    @if($viewingItem)
        <flux:modal wire:model="showItemDetailModal" class="md:w-3xl space-y-4">
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-7 w-auto max-w-[90px] object-contain" />
                        <flux:heading size="lg">Slip Gaji Pegawai</flux:heading>
                    </div>
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
                    @php $allowanceComps = $viewingItem->components->whereIn('type', ['allowance', 'earning']); @endphp
                    @forelse($allowanceComps as $comp)
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $comp->name }}</span>
                            <span class="font-mono font-medium text-emerald-700 dark:text-emerald-400">+Rp {{ number_format((float) $comp->amount, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        @if((float) $viewingItem->allowances > 0)
                            <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                                <span class="text-zinc-600 dark:text-zinc-400">Tunjangan Operasional</span>
                                <span class="font-mono font-medium text-emerald-700 dark:text-emerald-400">+Rp {{ number_format((float) $viewingItem->allowances, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    @endforelse
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
                    @if((float) $viewingItem->pph21_tax > 0)
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                            <span class="text-zinc-600 dark:text-zinc-400">PPh 21 (Pajak Penghasilan)</span>
                            <span class="font-mono text-rose-600">-Rp {{ number_format((float) $viewingItem->pph21_tax, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @php $deductionComps = $viewingItem->components->where('type', 'deduction'); @endphp
                    @foreach($deductionComps as $comp)
                        <div class="flex justify-between items-center py-1 border-b border-zinc-100 dark:border-zinc-800/50">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $comp->name }}</span>
                            <span class="font-mono text-rose-600">-Rp {{ number_format((float) $comp->amount, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
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

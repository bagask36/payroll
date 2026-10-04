<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Riwayat Slip Gaji Saya
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Transparansi kompensasi bulanan, rincian potongan iuran BPJS, dan bukti transfer gaji.
            </flux:subheading>
        @if($isUnlocked)
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="emerald" class="flex items-center gap-1">
                    <flux:icon name="lock-open" class="size-3.5" />
                    <span>Terverifikasi</span>
                </flux:badge>
                <flux:button wire:click="lock" variant="subtle" size="sm" icon="lock-closed" class="cursor-pointer">
                    Kunci Kembali
                </flux:button>
            </div>
        @endif
    </div>

    @if(! $isUnlocked)
        {{-- Password Verification Lock Card --}}
        <div class="mx-auto max-w-md py-6">
            <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                <div class="absolute -right-8 -top-8 size-40 rounded-full bg-emerald-500/10 blur-2xl dark:bg-emerald-500/20"></div>

                <div class="relative space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <flux:icon name="shield-check" class="size-6" />
                        </div>
                        <div>
                            <flux:heading size="lg" class="font-bold text-zinc-900 dark:text-zinc-100">
                                Verifikasi Keamanan
                            </flux:heading>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Perlindungan privasi data finansial pegawai
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-200/80 bg-amber-50/60 p-3.5 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300">
                        <div class="flex gap-2">
                            <flux:icon name="exclamation-triangle" class="size-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                            <span>Untuk melihat riwayat dan rincian slip gaji, silakan masukkan <strong>kata sandi akun login</strong> Anda terlebih dahulu.</span>
                        </div>
                    </div>

                    <form wire:submit.prevent="unlock" class="space-y-4">
                        <flux:field>
                            <flux:label>Kata Sandi Akun</flux:label>
                            <flux:input
                                wire:model="password"
                                type="password"
                                placeholder="Masukkan kata sandi login Anda"
                                autofocus
                            />
                            <flux:error name="password" />
                        </flux:field>

                        <flux:button type="submit" variant="primary" class="w-full justify-center cursor-pointer">
                            <span wire:loading.remove wire:target="unlock" class="flex items-center gap-2">
                                <flux:icon name="lock-open" class="size-4" />
                                <span>Verifikasi &amp; Buka Slip Gaji</span>
                            </span>
                            <span wire:loading wire:target="unlock" class="flex items-center gap-2">
                                <flux:icon name="arrow-path" class="size-4 animate-spin" />
                                <span>Memverifikasi...</span>
                            </span>
                        </flux:button>
                    </form>

                    <div class="border-t border-zinc-100 pt-3 text-center text-[11px] text-zinc-400 dark:border-zinc-800">
                        Sesi akses slip gaji akan aktif selama 15 menit.
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Employee Identity Card --}}
        @if($employee)
            <flux:card class="bg-gradient-to-r from-emerald-500/10 via-transparent to-transparent border border-emerald-500/20">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="size-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg">
                            {{ strtoupper(substr($employee->full_name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-zinc-100 text-base">{{ $employee->full_name }}</div>
                            <div class="text-xs text-zinc-500 font-mono">NIP: {{ $employee->nip }} • Unit: {{ $employee->unit?->name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="text-xs text-zinc-500 sm:text-right">
                        <div>Rekening Payroll: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $employee->primaryBankAccount?->bank_name ?? '-' }}</span></div>
                        <div class="font-mono text-zinc-400">
                            @if($employee->primaryBankAccount?->account_number)
                                **** {{ substr($employee->primaryBankAccount->account_number, -4) }}
                            @else
                                Belum terdaftar
                            @endif
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif

        {{-- Payslips PowerGrid Table --}}
        <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800 overflow-x-auto">
            <livewire:tables.my-payslip-table />
        </flux:card>

    {{-- Payslip Modal View --}}
    @if($selectedItem)
        <flux:modal wire:model="showDetailModal" class="w-full max-w-3xl space-y-5">
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-9 w-auto max-w-[110px] object-contain" />
                        <div>
                            <flux:heading size="lg">SLIP GAJI ELEKTRONIK</flux:heading>
                            <div class="text-xs text-zinc-500 font-semibold tracking-wider uppercase mt-0.5">SIM SDM RS Juwita</div>
                        </div>
                    </div>
                    <flux:badge size="sm" color="emerald">{{ $selectedItem->run?->period?->name }}</flux:badge>
                </div>
            </div>

            {{-- Employee Details --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-xs">
                <div>
                    <span class="text-zinc-400 block text-[11px]">Nama Pegawai</span>
                    <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $selectedItem->employee?->full_name }}</span>
                </div>
                <div>
                    <span class="text-zinc-400 block text-[11px]">Nomor Induk Pegawai (NIP)</span>
                    <span class="font-mono font-semibold">{{ $selectedItem->employee?->nip }}</span>
                </div>
                <div>
                    <span class="text-zinc-400 block text-[11px]">Unit Kerja / Ruangan</span>
                    <span>{{ $selectedItem->employee?->unit?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-zinc-400 block text-[11px]">Jabatan Fungsional</span>
                    <span>{{ $selectedItem->employee?->position?->title ?? '-' }}</span>
                </div>
            </div>

            {{-- Breakdown --}}
            <div class="space-y-4 text-xs">
                {{-- Income --}}
                <div class="space-y-1">
                    <span class="font-bold uppercase tracking-wider text-zinc-500 block text-[11px]">1. Penghasilan Bruto (Earnings)</span>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Gaji Pokok Sesuai SK</span>
                        <span class="font-mono font-medium">Rp {{ number_format((float) $selectedItem->basic_salary, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">Tunjangan Operasional</span>
                        <span class="font-mono font-medium">Rp {{ number_format((float) $selectedItem->allowances, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Deductions --}}
                <div class="space-y-1">
                    <span class="font-bold uppercase tracking-wider text-zinc-500 block text-[11px]">2. Potongan Wajib (Deductions)</span>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">BPJS Kesehatan (Iuran Pegawai 1%)</span>
                        <span class="font-mono text-rose-600">-Rp {{ number_format((float) $selectedItem->bpjs_kes_employee, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-600 dark:text-zinc-400">BPJS Ketenagakerjaan (JHT 2% + JP 1%)</span>
                        <span class="font-mono text-rose-600">-Rp {{ number_format((float) $selectedItem->bpjs_tk_employee, 0, ',', '.') }}</span>
                    </div>
                    @if((float) $selectedItem->pph21_tax > 0)
                        <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-600 dark:text-zinc-400">PPh 21 (Pajak Penghasilan)</span>
                            <span class="font-mono text-rose-600">-Rp {{ number_format((float) $selectedItem->pph21_tax, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Net Take Home Pay --}}
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase block">Gaji Bersih Diterima (Take Home Pay)</span>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400">
                            Transfer ke {{ $selectedItem->bank_name }} (**** {{ substr($selectedItem->bank_account_number, -4) }})
                        </span>
                    </div>
                    <div class="text-xl font-bold font-mono text-emerald-700 dark:text-emerald-300">
                        Rp {{ number_format((float) $selectedItem->net_salary, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Employer Contributions Transparency Note --}}
                <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 text-[11px] text-zinc-400 space-y-0.5">
                    <div class="font-semibold text-zinc-500">Iuran Ditanggung Rumah Sakit (Pemberi Kerja):</div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                        <span>BPJS Kesehatan (4%): Rp {{ number_format((float) $selectedItem->bpjs_kes_employer, 0, ',', '.') }}</span>
                        <span>BPJS Ketenagakerjaan: Rp {{ number_format((float) $selectedItem->bpjs_tk_employer, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <a href="{{ route('payroll.payslips.print', $selectedItem->id) }}" target="_blank">
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
    @endif
</div>

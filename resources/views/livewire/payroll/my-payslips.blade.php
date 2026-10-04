<div class="space-y-6">
    @if(! $isUnlocked)
        {{-- Centered Lock Screen Layout --}}
        <div class="flex min-h-[60vh] flex-col items-center justify-center py-6 sm:py-12">
            <div class="w-full max-w-md">
                <div class="relative overflow-hidden rounded-3xl border border-zinc-200/90 bg-white p-7 shadow-xl shadow-zinc-200/40 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none sm:p-9">
                    {{-- Ambient background glows --}}
                    <div class="absolute -right-10 -top-10 size-44 rounded-full bg-gradient-to-br from-emerald-500/15 via-teal-500/10 to-transparent blur-2xl"></div>
                    <div class="absolute -left-10 -bottom-10 size-44 rounded-full bg-gradient-to-tr from-blue-500/10 via-emerald-500/5 to-transparent blur-2xl"></div>

                    <div class="relative space-y-6">
                        {{-- Icon & Header --}}
                        <div class="text-center space-y-2">
                            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-lg shadow-emerald-500/25">
                                <flux:icon name="lock-closed" class="size-7" />
                            </div>
                            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Verifikasi Keamanan
                            </flux:heading>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-xs mx-auto">
                                Masukkan <strong>kata sandi akun login</strong> Anda untuk mengakses riwayat dan rincian slip gaji.
                            </p>
                        </div>

                        {{-- Employee Profile Pill --}}
                        @if($employee)
                            <div class="flex items-center gap-3 rounded-2xl border border-zinc-100 bg-zinc-50/80 p-3.5 dark:border-zinc-800/80 dark:bg-zinc-800/50">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white text-sm shadow-xs">
                                    {{ strtoupper(substr($employee->full_name, 0, 2)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                        {{ $employee->full_name }}
                                    </div>
                                    <div class="truncate text-[11px] font-mono text-zinc-400">
                                        NIP: {{ $employee->nip }} • {{ $employee->unit?->name ?? 'RS Juwita' }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Unlock Form --}}
                        <form wire:submit.prevent="unlock" class="space-y-4">
                            <flux:field>
                                <flux:label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    Kata Sandi Akun Login
                                </flux:label>
                                <flux:input
                                    wire:model="password"
                                    type="password"
                                    placeholder="Masukkan kata sandi Anda"
                                    autofocus
                                />
                                <flux:error name="password" />
                            </flux:field>

                            <flux:button type="submit" variant="primary" class="w-full justify-center py-2.5 font-semibold cursor-pointer shadow-sm shadow-emerald-600/20">
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

                        {{-- Footer links & info --}}
                        <div class="space-y-2 pt-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 text-[11px] text-zinc-400">
                                <flux:icon name="clock" class="size-3.5 text-zinc-400" />
                                <span>Sesi akses aktif selama 15 menit setelah verifikasi</span>
                            </div>
                            <div class="pt-1">
                                <a href="{{ route('dashboard') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 hover:underline dark:text-emerald-400">
                                    &larr; Kembali ke Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Riwayat Slip Gaji Saya
                </flux:heading>
                <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Transparansi kompensasi bulanan, rincian potongan iuran BPJS, dan bukti transfer gaji.
                </flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="emerald" class="flex items-center gap-1.5">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    <span>Terverifikasi (15 mnt)</span>
                </flux:badge>
                <flux:button wire:click="lock" variant="subtle" size="sm" icon="lock-closed" class="cursor-pointer">
                    Kunci Kembali
                </flux:button>
            </div>
        </div>

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

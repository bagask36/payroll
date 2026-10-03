<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $item->employee?->full_name }} ({{ $item->run?->period?->name }})</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-rs-juwita.png') }}">
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
                color: black !important;
                padding: 0 !important;
            }
            .payslip-container {
                border: 1px solid #d1d5db !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-zinc-100 text-zinc-900 min-h-screen py-8 px-4 font-sans">
    {{-- On-screen action toolbar --}}
    <div class="max-w-3xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="javascript:history.back()" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 inline-flex items-center gap-1.5">
            &larr; Kembali
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-xs flex items-center gap-2">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak / Simpan PDF
        </button>
    </div>

    {{-- Official Payslip Document --}}
    <div class="max-w-3xl mx-auto bg-white p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200 payslip-container relative">
        {{-- Hospital Header --}}
        <div class="border-b-2 border-zinc-900 pb-4 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo RS Juwita" class="h-12 w-auto max-w-[150px] object-contain" />
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-zinc-900">RUMAH SAKIT JUWITA</h1>
                    <p class="text-xs text-zinc-500">Jl. Teratai No. 12, Jakarta • Telp: (021) 555-0199 • sdm@juwitahospital.com</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-2.5 py-1 rounded bg-zinc-100 text-[11px] font-mono font-bold uppercase tracking-wider text-zinc-700">
                    Dokumen Rahasia
                </span>
                <p class="text-xs text-zinc-400 font-mono mt-1">Ref: PAY-{{ str_pad($item->id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>

        {{-- Document Title --}}
        <div class="py-4 text-center">
            <h2 class="text-lg font-bold uppercase tracking-wider text-zinc-900">Slip Penghasilan Pegawai</h2>
            <p class="text-xs text-zinc-600 font-medium">Periode: {{ $item->run?->period?->name }} (Tanggal Bayar: {{ $item->run?->run_date?->format('d/m/Y') }})</p>
        </div>

        {{-- Employee Details Grid --}}
        <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-zinc-50 border border-zinc-200 text-xs mb-6">
            <div class="space-y-1">
                <div><span class="text-zinc-500">Nama Lengkap:</span> <strong class="text-zinc-900">{{ $item->employee?->full_name }}</strong></div>
                <div><span class="text-zinc-500">Nomor Induk Pegawai (NIP):</span> <strong class="font-mono text-zinc-900">{{ $item->employee?->nip }}</strong></div>
                <div><span class="text-zinc-500">Unit / Ruangan:</span> <strong class="text-zinc-900">{{ $item->employee?->unit?->name ?? '-' }}</strong></div>
            </div>
            <div class="space-y-1">
                <div><span class="text-zinc-500">Jabatan:</span> <strong class="text-zinc-900">{{ $item->employee?->position?->title ?? '-' }}</strong></div>
                <div><span class="text-zinc-500">Rekening Tujuan:</span> <strong class="text-zinc-900">{{ $item->bank_name ?? 'Bank' }} (**** {{ substr($item->bank_account_number, -4) }})</strong></div>
                <div><span class="text-zinc-500">Status Pembayaran:</span> <span class="font-bold text-emerald-700 uppercase">Telah Disetujui (Final)</span></div>
            </div>
        </div>

        {{-- Compensation Table --}}
        <div class="space-y-6 text-sm mb-6">
            {{-- Earnings --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 border-b border-zinc-200 pb-1 mb-2">
                    A. Penghasilan Bruto (Earnings)
                </h3>
                <table class="w-full text-xs">
                    <tr class="border-b border-zinc-100">
                        <td class="py-1.5 text-zinc-600">Gaji Pokok Berdasarkan SK</td>
                        <td class="py-1.5 text-right font-mono font-semibold">Rp {{ number_format((float) $item->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <td class="py-1.5 text-zinc-600">Tunjangan Operasional</td>
                        <td class="py-1.5 text-right font-mono font-semibold">Rp {{ number_format((float) $item->allowances, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="bg-zinc-50 font-bold">
                        <td class="py-1.5 px-2">Total Penghasilan Bruto</td>
                        <td class="py-1.5 px-2 text-right font-mono">Rp {{ number_format((float) ($item->basic_salary + $item->allowances), 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            {{-- Deductions --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 border-b border-zinc-200 pb-1 mb-2">
                    B. Potongan Wajib Resmi (Deductions)
                </h3>
                <table class="w-full text-xs">
                    <tr class="border-b border-zinc-100">
                        <td class="py-1.5 text-zinc-600">Iuran BPJS Kesehatan Pegawai (1%)</td>
                        <td class="py-1.5 text-right font-mono text-rose-600 font-semibold">-Rp {{ number_format((float) $item->bpjs_kes_employee, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <td class="py-1.5 text-zinc-600">Iuran BPJS Ketenagakerjaan Pegawai (JHT 2% + JP 1%)</td>
                        <td class="py-1.5 text-right font-mono text-rose-600 font-semibold">-Rp {{ number_format((float) $item->bpjs_tk_employee, 0, ',', '.') }}</td>
                    </tr>
                    @if((float) $item->pph21_tax > 0)
                        <tr class="border-b border-zinc-100">
                            <td class="py-1.5 text-zinc-600">Pajak Penghasilan (PPh Pasal 21)</td>
                            <td class="py-1.5 text-right font-mono text-rose-600 font-semibold">-Rp {{ number_format((float) $item->pph21_tax, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if((float) $item->deductions > 0)
                        <tr class="border-b border-zinc-100">
                            <td class="py-1.5 text-zinc-600">Potongan Lainnya / Penyesuaian</td>
                            <td class="py-1.5 text-right font-mono text-rose-600 font-semibold">-Rp {{ number_format((float) $item->deductions, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="bg-zinc-50 font-bold">
                        <td class="py-1.5 px-2">Total Potongan Resmi</td>
                        <td class="py-1.5 px-2 text-right font-mono text-rose-600">
                            -Rp {{ number_format((float) ($item->bpjs_kes_employee + $item->bpjs_tk_employee + $item->pph21_tax + $item->deductions), 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Net Pay Box --}}
            <div class="p-4 rounded-xl bg-emerald-50 border-2 border-emerald-500/30 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 block">Gaji Bersih Diterima (Take Home Pay)</span>
                    <span class="text-[11px] text-emerald-600">Ditransfer ke {{ $item->bank_name ?? 'Bank' }}</span>
                </div>
                <div class="text-2xl font-bold font-mono text-emerald-700">
                    Rp {{ number_format((float) $item->net_salary, 0, ',', '.') }}
                </div>
            </div>

            {{-- Employer Contributions Transparency Note --}}
            <div class="p-3 rounded-lg bg-zinc-50 border border-zinc-200 text-[11px] text-zinc-500 flex justify-between">
                <div>Iuran Ditanggung Rumah Sakit (Pemberi Kerja):</div>
                <div class="font-mono">
                    BPJS Kes (4%): Rp {{ number_format((float) $item->bpjs_kes_employer, 0, ',', '.') }} |
                    BPJS TK: Rp {{ number_format((float) $item->bpjs_tk_employer, 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Signatures Section --}}
        <div class="pt-6 border-t border-zinc-200 grid grid-cols-2 text-center text-xs">
            <div>
                <p class="text-zinc-500 mb-14">Diterima oleh Pegawai,</p>
                <p class="font-bold underline text-zinc-900">{{ $item->employee?->full_name }}</p>
                <p class="text-zinc-400 font-mono">{{ $item->employee?->nip }}</p>
            </div>
            <div>
                <p class="text-zinc-500 mb-14">Direktur Keuangan & SDM RS Juwita,</p>
                <p class="font-bold underline text-zinc-900">dr. H. Hendra Pratama, Sp.PD</p>
                <p class="text-zinc-400 font-mono">SIP. 19780415-200312-1-002</p>
            </div>
        </div>

        <div class="mt-8 text-center text-[10px] text-zinc-400 font-mono">
            Dokumen ini diterbitkan secara elektronik oleh Sistem Informasi Kepegawaian & Penggajian RS Juwita.
        </div>
    </div>
</body>
</html>

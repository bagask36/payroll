@php
    $hospitalName = $hospital['name'] ?? \App\Models\SystemSetting::get('hospital_name', 'Rumah Sakit Juwita');
    $hospitalAddress = $hospital['address'] ?? \App\Models\SystemSetting::get('hospital_address', 'Jl. Mayor M. Hasibuan No. 78, Bekasi Timur, Jawa Barat');
    $hospitalPhone = $hospital['phone'] ?? \App\Models\SystemSetting::get('hospital_phone', '(021) 8816654');
    $hospitalEmail = $hospital['email'] ?? \App\Models\SystemSetting::get('hospital_email', 'sdm@rsjuwita.co.id');
    $hospitalFaskesCode = $hospital['faskes_code'] ?? \App\Models\SystemSetting::get('hospital_faskes_code', '3275012');
    $hospitalNpwp = $hospital['npwp'] ?? \App\Models\SystemSetting::get('hospital_npwp', '01.889.345.2-407.000');
    $terbilangText = $terbilang ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Penghasilan Pegawai - {{ $item->employee?->full_name }} ({{ $item->run?->period?->name }})</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo-rs-juwita.png') }}">
    @vite(['resources/css/app.css'])
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .payslip-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .page-break-avoid {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-zinc-100 text-zinc-900 min-h-screen py-6 px-4 font-sans antialiased text-xs sm:text-sm">
    {{-- On-Screen Action Toolbar (Hidden in Print) --}}
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-600 hover:text-zinc-900 bg-white border border-zinc-200 px-3 py-2 rounded-lg shadow-2xs hover:bg-zinc-50 transition-colors">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>

        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-500 hidden sm:inline">Format: Dokumen Resmi A4</span>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- Official Payslip Document Sheet --}}
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-10 rounded-xl shadow-xs border border-zinc-300 payslip-container page-break-avoid">
        
        {{-- KOP SURAT RESMI RUMAH SAKIT (Official Letterhead) --}}
        <div class="pb-3">
            <div class="flex items-center justify-between gap-4">
                {{-- Logo Faskes --}}
                <div class="shrink-0">
                    <img src="{{ asset('assets/logo-rs-juwita.png') }}" alt="Logo {{ $hospitalName }}" class="h-16 w-auto max-w-[150px] object-contain" />
                </div>

                {{-- Hospital Identity Profile --}}
                <div class="text-center flex-1">
                    <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wider text-zinc-950 font-serif">
                        {{ strtoupper($hospitalName) }}
                    </h1>
                    <p class="text-xs font-semibold text-zinc-700 uppercase tracking-widest mt-0.5">
                        KODE REGISTRASI FASKES KEMENKES: <span class="font-mono text-zinc-900">{{ $hospitalFaskesCode }}</span>
                    </p>
                    <p class="text-xs text-zinc-600 mt-1 leading-relaxed">
                        {{ $hospitalAddress }}
                    </p>
                    <p class="text-[11px] text-zinc-500 mt-0.5">
                        <span>Telp: {{ $hospitalPhone }}</span> &bull; 
                        <span>Email: {{ $hospitalEmail }}</span> &bull; 
                        <span>NPWP: {{ $hospitalNpwp }}</span>
                    </p>
                </div>

                {{-- Official Seal / QR Placeholder --}}
                <div class="shrink-0 text-right hidden sm:block">
                    <div class="border border-zinc-300 rounded p-1.5 bg-zinc-50/70 text-center">
                        <span class="block text-[9px] font-mono uppercase tracking-wider text-zinc-500">SIM-RS JUWITA</span>
                        <div class="size-12 mx-auto my-0.5 bg-zinc-200 border border-zinc-300 flex items-center justify-center font-mono text-[9px] text-zinc-600 font-bold">
                            VALID
                        </div>
                        <span class="block text-[8px] font-mono text-zinc-400">PAY-{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
            </div>

            {{-- Double Border Line (Garis Ganda Kop Resmi Kemenkes/RS) --}}
            <div class="mt-4 border-b-2 border-zinc-950"></div>
            <div class="mt-0.5 border-b border-zinc-800"></div>
        </div>

        {{-- Document Title & Classification Header --}}
        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-200">
            <div>
                <h2 class="text-base sm:text-lg font-extrabold uppercase tracking-wide text-zinc-900">
                    Slip Penghasilan Pegawai
                </h2>
                <p class="text-xs text-zinc-600 font-medium">
                    Periode Penggajian: <strong class="text-zinc-900">{{ $item->run?->period?->name ?? '-' }}</strong> &bull;
                    Tanggal Transfer: <span class="font-medium text-zinc-800">{{ $item->run?->run_date ? \Carbon\Carbon::parse($item->run->run_date)->translatedFormat('d F Y') : '-' }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-center">
                <span class="inline-flex items-center px-2.5 py-1 rounded bg-zinc-100 text-zinc-800 border border-zinc-300 text-[10px] font-bold uppercase tracking-wider">
                    Dokumen Rahasia
                </span>
                <span class="text-xs font-mono text-zinc-600 font-semibold bg-zinc-50 px-2 py-1 rounded border border-zinc-200">
                    REF: PAY-{{ str_pad($item->id, 6, '0', STR_PAD_LEFT) }}
                </span>
            </div>
        </div>

        {{-- Employee Profile & Job Details (Formal Table Grid) --}}
        <div class="my-4 border border-zinc-300 rounded-md overflow-hidden bg-white text-xs">
            <div class="bg-zinc-100 px-3 py-1.5 border-b border-zinc-300 font-bold text-zinc-800 uppercase tracking-wider text-[11px] flex justify-between items-center">
                <span>Data Induk Pegawai</span>
                <span class="text-[10px] font-normal normal-case text-zinc-500">Kategori: {{ ucfirst($item->employee?->employment_status ?? 'Pegawai') }}</span>
            </div>
            <table class="w-full text-left border-collapse">
                <tbody>
                    <tr class="border-b border-zinc-200">
                        <td class="w-1/6 py-1.5 px-3 bg-zinc-50/70 text-zinc-500 font-medium border-r border-zinc-200">Nama Lengkap</td>
                        <td class="w-2/6 py-1.5 px-3 font-bold text-zinc-900 border-r border-zinc-200">{{ $item->employee?->full_name }}</td>
                        <td class="w-1/6 py-1.5 px-3 bg-zinc-50/70 text-zinc-500 font-medium border-r border-zinc-200">Jabatan Resmi</td>
                        <td class="w-2/6 py-1.5 px-3 font-semibold text-zinc-900">{{ $item->employee?->position?->title ?? '-' }}</td>
                    </tr>
                    <tr class="border-b border-zinc-200">
                        <td class="py-1.5 px-3 bg-zinc-50/70 text-zinc-500 font-medium border-r border-zinc-200">Nomor Induk (NIP)</td>
                        <td class="py-1.5 px-3 font-mono font-bold text-zinc-900 border-r border-zinc-200">{{ $item->employee?->nip }}</td>
                        <td class="py-1.5 px-3 bg-zinc-50/70 text-zinc-500 font-medium border-r border-zinc-200">Unit Kerja / Ruang</td>
                        <td class="py-1.5 px-3 font-semibold text-zinc-900">{{ $item->employee?->unit?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 px-3 bg-zinc-50/70 text-zinc-500 font-medium border-r border-zinc-200">Rekening Tujuan</td>
                        <td class="py-1.5 px-3 font-mono text-zinc-800 border-r border-zinc-200">
                            {{ $item->bank_name ?? 'Bank' }} &bull; **** {{ substr($item->bank_account_number, -4) }}
                        </td>
                        <td class="py-1.5 px-3 bg-zinc-50/70 text-zinc-500 font-medium border-r border-zinc-200">Status Pajak / NPWP</td>
                        <td class="py-1.5 px-3 font-mono text-zinc-800">
                            {{ $item->employee?->tax_status ?? 'TK/0' }} &bull; {{ $item->employee?->npwp ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Financial Compensation Ledger (Side-by-Side Earnings & Deductions) --}}
        <div class="my-4 border border-zinc-300 rounded-md overflow-hidden bg-white text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2">
                
                {{-- LEFT COLUMN: PENERIMAAN (EARNINGS) --}}
                <div class="border-b md:border-b-0 md:border-r border-zinc-300 flex flex-col justify-between">
                    <div>
                        <div class="bg-zinc-100 px-3 py-1.5 border-b border-zinc-300 font-bold text-zinc-800 uppercase tracking-wider text-[11px] flex justify-between">
                            <span>I. Penghasilan / Hak (Earnings)</span>
                            <span class="font-mono text-zinc-500">Jumlah (Rp)</span>
                        </div>
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="border-b border-zinc-100 hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 text-zinc-700">Gaji Pokok Berdasarkan SK</td>
                                    <td class="py-2 px-3 text-right font-mono font-semibold text-zinc-900">
                                        {{ number_format((float) $item->basic_salary, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="border-b border-zinc-100 hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 text-zinc-700">Tunjangan Operasional & Fungsional</td>
                                    <td class="py-2 px-3 text-right font-mono font-semibold text-zinc-900">
                                        {{ number_format((float) $item->allowances, 0, ',', '.') }}
                                    </td>
                                </tr>
                                {{-- Extra itemized allowance components if present --}}
                                @if($item->components && $item->components->where('type', 'allowance')->count() > 0)
                                    @foreach($item->components->where('type', 'allowance') as $comp)
                                        <tr class="border-b border-zinc-100 hover:bg-zinc-50/50 text-[11px]">
                                            <td class="py-1.5 px-3 text-zinc-500 pl-6">&bull; {{ $comp->name }}</td>
                                            <td class="py-1.5 px-3 text-right font-mono text-zinc-700">
                                                {{ number_format((float) $comp->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Subtotal A --}}
                    <div class="bg-zinc-100/80 border-t border-zinc-300 px-3 py-2 flex justify-between items-center font-bold">
                        <span class="text-zinc-800 uppercase tracking-wider text-[11px]">Total Penerimaan Bruto (A)</span>
                        <span class="font-mono text-zinc-950 text-xs sm:text-sm">
                            Rp {{ number_format((float) ($item->basic_salary + $item->allowances), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- RIGHT COLUMN: POTONGAN RESMI (DEDUCTIONS) --}}
                <div class="flex flex-col justify-between">
                    <div>
                        <div class="bg-zinc-100 px-3 py-1.5 border-b border-zinc-300 font-bold text-zinc-800 uppercase tracking-wider text-[11px] flex justify-between">
                            <span>II. Potongan Resmi (Deductions)</span>
                            <span class="font-mono text-zinc-500">Jumlah (Rp)</span>
                        </div>
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="border-b border-zinc-100 hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 text-zinc-700">Iuran BPJS Kesehatan Pegawai (1%)</td>
                                    <td class="py-2 px-3 text-right font-mono font-semibold text-rose-700">
                                        -{{ number_format((float) $item->bpjs_kes_employee, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="border-b border-zinc-100 hover:bg-zinc-50/50">
                                    <td class="py-2 px-3 text-zinc-700">Iuran BPJS Ketenagakerjaan Pegawai (3%)</td>
                                    <td class="py-2 px-3 text-right font-mono font-semibold text-rose-700">
                                        -{{ number_format((float) $item->bpjs_tk_employee, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @if((float) $item->pph21_tax > 0)
                                    <tr class="border-b border-zinc-100 hover:bg-zinc-50/50">
                                        <td class="py-2 px-3 text-zinc-700">Pajak Penghasilan (PPh Pasal 21)</td>
                                        <td class="py-2 px-3 text-right font-mono font-semibold text-rose-700">
                                            -{{ number_format((float) $item->pph21_tax, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                                @if((float) $item->deductions > 0)
                                    <tr class="border-b border-zinc-100 hover:bg-zinc-50/50">
                                        <td class="py-2 px-3 text-zinc-700">Potongan Lainnya / Kasbon</td>
                                        <td class="py-2 px-3 text-right font-mono font-semibold text-rose-700">
                                            -{{ number_format((float) $item->deductions, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                                {{-- Extra itemized deduction components if present --}}
                                @if($item->components && $item->components->where('type', 'deduction')->count() > 0)
                                    @foreach($item->components->where('type', 'deduction') as $comp)
                                        <tr class="border-b border-zinc-100 hover:bg-zinc-50/50 text-[11px]">
                                            <td class="py-1.5 px-3 text-zinc-500 pl-6">&bull; {{ $comp->name }}</td>
                                            <td class="py-1.5 px-3 text-right font-mono text-rose-600">
                                                -{{ number_format((float) $comp->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- Subtotal B --}}
                    @php
                        $totalDeductions = (float) ($item->bpjs_kes_employee + $item->bpjs_tk_employee + $item->pph21_tax + $item->deductions);
                    @endphp
                    <div class="bg-zinc-100/80 border-t border-zinc-300 px-3 py-2 flex justify-between items-center font-bold">
                        <span class="text-zinc-800 uppercase tracking-wider text-[11px]">Total Potongan Resmi (B)</span>
                        <span class="font-mono text-rose-700 text-xs sm:text-sm">
                            -Rp {{ number_format($totalDeductions, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        {{-- TAKE HOME PAY (NET SALARY) BOX --}}
        <div class="my-4 border-2 border-zinc-900 rounded-md bg-zinc-50 p-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-300 pb-2">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-zinc-900 block">
                        TOTAL DITERIMA BERSIH (TAKE HOME PAY = A - B)
                    </span>
                    <span class="text-[11px] text-zinc-600">
                        Status Pembayaran: <strong class="text-emerald-700 uppercase">Telah Disetujui (Final)</strong> via transfer {{ $item->bank_name ?? 'Bank' }}
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-black font-mono text-zinc-950">
                    Rp {{ number_format((float) $item->net_salary, 0, ',', '.') }}
                </div>
            </div>

            {{-- Formal Indonesian Spelled Out Amount (Terbilang) --}}
            <div class="pt-2 text-xs text-zinc-700">
                <span class="font-semibold text-zinc-500">Terbilang:</span>
                <span class="italic font-medium text-zinc-900 ml-1">
                    # {{ $terbilangText ?? '' }} #
                </span>
            </div>
        </div>

        {{-- Transparansi Beban Iuran Jaminan Sosial Pemberi Kerja (Hospital Employer BPJS) --}}
        <div class="my-4 p-3 rounded border border-zinc-200 bg-zinc-50/50 text-[11px] text-zinc-600">
            <div class="font-semibold text-zinc-700 mb-1 flex items-center justify-between">
                <span>Informasi Manfaat Jaminan Sosial Ditanggung Rumah Sakit (Pemberi Kerja):</span>
                <span class="text-[10px] text-zinc-400 font-normal italic">*Bukan potongan gaji pegawai</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono text-zinc-700">
                <div>&bull; BPJS Kesehatan (4% Beban RS): <strong>Rp {{ number_format((float) $item->bpjs_kes_employer, 0, ',', '.') }}</strong></div>
                <div>&bull; BPJS Ketenagakerjaan (Beban RS): <strong>Rp {{ number_format((float) $item->bpjs_tk_employer, 0, ',', '.') }}</strong></div>
            </div>
        </div>

        {{-- Lembar Pengesahan Resmi (Signatures) --}}
        <div class="mt-8 pt-4 border-t border-zinc-300">
            <div class="text-right text-xs text-zinc-600 mb-4">
                <span>Bekasi, {{ $item->run?->run_date ? \Carbon\Carbon::parse($item->run->run_date)->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</span>
            </div>

            <div class="grid grid-cols-2 text-center text-xs">
                {{-- Employee Signature --}}
                <div class="flex flex-col justify-between h-32 px-4">
                    <p class="text-zinc-600 font-medium">Penerima Penghasilan,</p>
                    <div>
                        <p class="font-bold text-zinc-950 underline underline-offset-2">{{ $item->employee?->full_name }}</p>
                        <p class="text-zinc-500 font-mono text-[11px]">NIP. {{ $item->employee?->nip }}</p>
                    </div>
                </div>

                {{-- Hospital HR/Finance Authority Signature --}}
                <div class="flex flex-col justify-between h-32 px-4">
                    <div>
                        <p class="text-zinc-600 font-medium">Pengelola SDM & Keuangan,</p>
                        <p class="text-zinc-800 font-bold uppercase text-[11px]">{{ $hospitalName }}</p>
                    </div>
                    <div>
                        <div class="inline-block border border-dashed border-zinc-400 px-3 py-1 rounded bg-zinc-50 text-[10px] font-mono text-zinc-600 mb-1">
                            [ Terverifikasi Sistem SIM-RS ]
                        </div>
                        <p class="font-bold text-zinc-950 underline underline-offset-2">Bagian Administrasi SDM & Penggajian</p>
                        <p class="text-zinc-500 font-mono text-[11px]">Faskes Reg: {{ $hospitalFaskesCode }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Compliance & Security Notice --}}
        <div class="mt-8 pt-3 border-t border-zinc-200 text-center text-[10px] text-zinc-400 font-mono leading-relaxed">
            <p>
                Dokumen ini dicetak secara otomatis dan sah melalui Sistem Informasi Manajemen Rumah Sakit (SIM-RS Juwita).
            </p>
            <p class="text-[9px] text-zinc-400 mt-0.5">
                Segala informasi rincian gaji ini bersifat RAHASIA (CONFIDENTIAL) dan hanya diperuntukkan bagi pegawai bersangkutan.
            </p>
        </div>

    </div>
</body>
</html>

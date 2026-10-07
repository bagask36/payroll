@php
    $hospitalName = $hospital['name'] ?? \App\Models\SystemSetting::get('hospital_name', 'RS Permata Bunda');
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
    <link rel="icon" type="image/png" href="{{ asset('assets/RS-Permata-Bunda.jpg') }}">
    @vite(['resources/css/app.css'])
    <style>
        /* Standar Font Arial untuk Dokumen Resmi */
        * {
            font-family: Arial, Helvetica, sans-serif !important;
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        @media print {
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 10.5px !important;
                line-height: 1.25 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                height: 100% !important;
                overflow: hidden !important;
            }

            .no-print {
                display: none !important;
            }

            .payslip-container {
                border: 1.5px solid #000000 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 5mm 6mm !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .page-break-avoid, tr, td, th, div {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Formal print borders & contrast */
            .border-print {
                border-color: #000000 !important;
            }
            .bg-print-light {
                background-color: #f4f4f5 !important;
            }
        }

        @media screen {
            body {
                background-color: #f4f4f5;
                font-family: Arial, Helvetica, sans-serif;
            }
            .payslip-container {
                max-width: 210mm;
                min-height: auto;
            }
        }
    </style>
</head>
<body class="bg-zinc-100 text-zinc-900 min-h-screen py-4 px-3 sm:px-6 antialiased text-xs">

    {{-- Toolbar Aksi Layar (Hidden saat dicetak) --}}
    <div class="max-w-[210mm] mx-auto mb-3 flex items-center justify-between no-print">
        <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-700 hover:text-zinc-950 bg-white border border-zinc-300 px-3 py-1.5 rounded shadow-2xs hover:bg-zinc-50 transition-colors">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>

        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-600 hidden sm:inline">Format: Standar Resmi A4 (Muat 1 Halaman)</span>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-1.5 bg-zinc-900 hover:bg-black text-white rounded text-xs font-bold shadow-xs transition-colors cursor-pointer">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    {{-- Lembar Dokumen Slip Gaji Resmi --}}
    <div class="mx-auto bg-white p-5 sm:p-6 border-2 border-zinc-900 payslip-container page-break-avoid text-zinc-900">
        
        {{-- KOP SURAT RESMI RUMAH SAKIT --}}
        <div class="pb-1.5">
            <div class="flex items-center justify-between gap-4">
                {{-- Logo Rumah Sakit --}}
                <div class="shrink-0 w-24 flex items-center justify-center">
                    <img src="{{ asset('assets/RS-Permata-Bunda.jpg') }}" alt="Logo {{ $hospitalName }}" class="h-14 w-auto max-w-[100px] object-contain" />
                </div>

                {{-- Identitas Profil Instansi --}}
                <div class="text-center flex-1 px-2">
                    <h1 class="text-lg sm:text-xl font-bold uppercase tracking-wider text-black leading-tight">
                        {{ strtoupper($hospitalName) }}
                    </h1>
                    <p class="text-[10.5px] font-bold text-zinc-800 uppercase tracking-widest mt-0.5">
                        KODE REGISTRASI FASKES KEMENKES: <span class="text-black">{{ $hospitalFaskesCode }}</span>
                    </p>
                    <p class="text-[10px] text-zinc-700 mt-0.5 leading-snug">
                        {{ $hospitalAddress }}
                    </p>
                    <p class="text-[9.5px] text-zinc-600 mt-0.5">
                        <span>Telp: {{ $hospitalPhone }}</span> &bull; 
                        <span>Email: {{ $hospitalEmail }}</span> &bull; 
                        <span>NPWP: {{ $hospitalNpwp }}</span>
                    </p>
                </div>

                {{-- Status Verifikasi Dokumen & QR Placeholder --}}
                <div class="shrink-0 w-24 text-center">
                    <div class="border border-zinc-400 p-1 bg-zinc-50 inline-block text-center">
                        <span class="block text-[8px] font-bold uppercase tracking-wider text-zinc-600">SIM-RS</span>
                        <div class="size-9 mx-auto my-0.5 bg-zinc-200 border border-zinc-300 flex items-center justify-center text-[8px] text-zinc-700 font-bold">
                            VALID
                        </div>
                        <span class="block text-[7.5px] text-zinc-500 font-bold">PAY-{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
            </div>

            {{-- Garis Ganda Pemisah Kop Dokumen Resmi (Thick + Thin line) --}}
            <div class="mt-2 border-b-2 border-black"></div>
            <div class="mt-0.5 border-b border-black"></div>
        </div>

        {{-- Judul & Klasifikasi Dokumen --}}
        <div class="py-1.5 flex items-center justify-between border-b border-zinc-300">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-black">
                    Slip Penghasilan Pegawai
                </h2>
                <p class="text-[10.5px] text-zinc-700">
                    Periode: <strong class="text-black">{{ $item->run?->period?->name ?? '-' }}</strong> &bull;
                    Tgl Transfer: <span class="text-black">{{ $item->run?->run_date ? \Carbon\Carbon::parse($item->run->run_date)->translatedFormat('d F Y') : '-' }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block px-2 py-0.5 bg-zinc-100 border border-zinc-400 text-[9.5px] font-bold uppercase tracking-wider text-black">
                    Dokumen Rahasia
                </span>
                <span class="text-[10px] text-zinc-700 font-bold border border-zinc-300 px-1.5 py-0.5 bg-zinc-50">
                    REF: PAY-{{ str_pad($item->id, 6, '0', STR_PAD_LEFT) }}
                </span>
            </div>
        </div>

        {{-- Data Induk Pegawai (Tabel Formal Kompak) --}}
        <div class="my-2 border border-zinc-400">
            <table class="w-full text-left border-collapse text-[10.5px]">
                <tbody>
                    <tr class="border-b border-zinc-300">
                        <td class="w-[18%] py-1 px-2 bg-zinc-100 font-bold text-zinc-700 border-r border-zinc-300">Nama Lengkap</td>
                        <td class="w-[32%] py-1 px-2 font-bold text-black border-r border-zinc-300">{{ $item->employee?->full_name }}</td>
                        <td class="w-[18%] py-1 px-2 bg-zinc-100 font-bold text-zinc-700 border-r border-zinc-300">Jabatan</td>
                        <td class="w-[32%] py-1 px-2 font-semibold text-black">{{ $item->employee?->position?->title ?? '-' }}</td>
                    </tr>
                    <tr class="border-b border-zinc-300">
                        <td class="py-1 px-2 bg-zinc-100 font-bold text-zinc-700 border-r border-zinc-300">NIP / Status</td>
                        <td class="py-1 px-2 text-black border-r border-zinc-300">
                            <span class="font-bold">{{ $item->employee?->nip }}</span>
                            <span class="text-[9.5px] text-zinc-600">({{ ucfirst($item->employee?->employment_status ?? 'Pegawai') }})</span>
                        </td>
                        <td class="py-1 px-2 bg-zinc-100 font-bold text-zinc-700 border-r border-zinc-300">Unit Kerja / Ruang</td>
                        <td class="py-1 px-2 font-semibold text-black">{{ $item->employee?->unit?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 px-2 bg-zinc-100 font-bold text-zinc-700 border-r border-zinc-300">Rekening Tujuan</td>
                        <td class="py-1 px-2 text-black border-r border-zinc-300">
                            {{ $item->bank_name ?? 'Bank' }} &bull; **** {{ substr($item->bank_account_number, -4) }}
                        </td>
                        <td class="py-1 px-2 bg-zinc-100 font-bold text-zinc-700 border-r border-zinc-300">Status Pajak / NPWP</td>
                        <td class="py-1 px-2 text-black">
                            {{ $item->employee?->tax_status ?? 'TK/0' }} &bull; {{ $item->employee?->npwp ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Tabel Kompensasi Finansial (Penghasilan & Potongan Berdampingan) --}}
        <div class="my-2 border border-zinc-400 text-[10.5px]">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-400 font-bold text-black uppercase text-[10px]">
                        <th class="w-1/2 py-1 px-2 text-left border-r border-zinc-400">
                            <div class="flex justify-between items-center">
                                <span>I. Penghasilan / Hak (Earnings)</span>
                                <span class="font-normal text-[9.5px] normal-case text-zinc-600">Jumlah (Rp)</span>
                            </div>
                        </th>
                        <th class="w-1/2 py-1 px-2 text-left">
                            <div class="flex justify-between items-center">
                                <span>II. Potongan Resmi (Deductions)</span>
                                <span class="font-normal text-[9.5px] normal-case text-zinc-600">Jumlah (Rp)</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="align-top">
                    <tr>
                        {{-- Kolom Kiri: Penghasilan --}}
                        <td class="border-r border-zinc-400 p-0 align-top">
                            <table class="w-full border-collapse">
                                <tbody>
                                    <tr class="border-b border-zinc-200">
                                        <td class="py-1 px-2 text-zinc-800">Gaji Pokok Berdasarkan SK</td>
                                        <td class="py-1 px-2 text-right font-bold text-black">
                                            {{ number_format((float) $item->basic_salary, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr class="border-b border-zinc-200">
                                        <td class="py-1 px-2 text-zinc-800">Tunjangan Operasional & Fungsional</td>
                                        <td class="py-1 px-2 text-right font-bold text-black">
                                            {{ number_format((float) $item->allowances, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @if($item->components && $item->components->where('type', 'allowance')->count() > 0)
                                        @foreach($item->components->where('type', 'allowance') as $comp)
                                            <tr class="border-b border-zinc-200 text-[10px]">
                                                <td class="py-0.5 px-2 text-zinc-600 pl-4">&bull; {{ $comp->name }}</td>
                                                <td class="py-0.5 px-2 text-right text-zinc-800">
                                                    {{ number_format((float) $comp->amount, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </td>

                        {{-- Kolom Kanan: Potongan --}}
                        <td class="p-0 align-top">
                            <table class="w-full border-collapse">
                                <tbody>
                                    <tr class="border-b border-zinc-200">
                                        <td class="py-1 px-2 text-zinc-800">Iuran BPJS Kesehatan Pegawai (1%)</td>
                                        <td class="py-1 px-2 text-right font-bold text-zinc-900">
                                            -{{ number_format((float) $item->bpjs_kes_employee, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr class="border-b border-zinc-200">
                                        <td class="py-1 px-2 text-zinc-800">Iuran BPJS Ketenagakerjaan Pegawai (3%)</td>
                                        <td class="py-1 px-2 text-right font-bold text-zinc-900">
                                            -{{ number_format((float) $item->bpjs_tk_employee, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @if((float) $item->pph21_tax > 0)
                                        <tr class="border-b border-zinc-200">
                                            <td class="py-1 px-2 text-zinc-800">Pajak Penghasilan (PPh Pasal 21)</td>
                                            <td class="py-1 px-2 text-right font-bold text-zinc-900">
                                                -{{ number_format((float) $item->pph21_tax, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if((float) $item->deductions > 0)
                                        <tr class="border-b border-zinc-200">
                                            <td class="py-1 px-2 text-zinc-800">Potongan Lainnya / Kasbon</td>
                                            <td class="py-1 px-2 text-right font-bold text-zinc-900">
                                                -{{ number_format((float) $item->deductions, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if($item->components && $item->components->where('type', 'deduction')->count() > 0)
                                        @foreach($item->components->where('type', 'deduction') as $comp)
                                            <tr class="border-b border-zinc-200 text-[10px]">
                                                <td class="py-0.5 px-2 text-zinc-600 pl-4">&bull; {{ $comp->name }}</td>
                                                <td class="py-0.5 px-2 text-right text-zinc-800">
                                                    -{{ number_format((float) $comp->amount, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    @php
                        $totalDeductions = (float) ($item->bpjs_kes_employee + $item->bpjs_tk_employee + $item->pph21_tax + $item->deductions);
                    @endphp
                    <tr class="bg-zinc-100 font-bold border-t border-zinc-400 text-[10.5px]">
                        <td class="py-1.5 px-2 border-r border-zinc-400">
                            <div class="flex justify-between items-center">
                                <span class="uppercase">Total Penerimaan Bruto (A)</span>
                                <span class="text-black">
                                    Rp {{ number_format((float) ($item->basic_salary + $item->allowances), 0, ',', '.') }}
                                </span>
                            </div>
                        </td>
                        <td class="py-1.5 px-2">
                            <div class="flex justify-between items-center">
                                <span class="uppercase">Total Potongan Resmi (B)</span>
                                <span class="text-black">
                                    -Rp {{ number_format($totalDeductions, 0, ',', '.') }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- KOTAK TAKE HOME PAY (PENGHASILAN BERSIH) --}}
        <div class="my-2 border-2 border-black bg-zinc-50 p-2 text-black">
            <div class="flex items-center justify-between border-b border-zinc-300 pb-1.5">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider block">
                        TOTAL DITERIMA BERSIH (TAKE HOME PAY = A - B)
                    </span>
                    <span class="text-[10px] text-zinc-700">
                        Status: <strong class="text-black uppercase">Telah Disetujui (Final)</strong> via transfer {{ $item->bank_name ?? 'Bank' }}
                    </span>
                </div>
                <div class="text-lg sm:text-xl font-bold text-black tracking-tight">
                    Rp {{ number_format((float) $item->net_salary, 0, ',', '.') }}
                </div>
            </div>

            {{-- Terbilang Resmi Bahasa Indonesia --}}
            <div class="pt-1 text-[10.5px]">
                <span class="font-bold text-zinc-700">Terbilang:</span>
                <span class="italic font-bold text-black ml-1">
                    # {{ $terbilangText ?? '' }} #
                </span>
            </div>
        </div>

        {{-- Informasi Iuran Jaminan Sosial Ditanggung Pemberi Kerja (Beban Rumah Sakit) --}}
        <div class="my-1.5 p-1.5 border border-zinc-300 bg-zinc-50/70 text-[9.5px] text-zinc-700">
            <div class="font-bold text-black mb-0.5 flex items-center justify-between">
                <span>Informasi Manfaat Jaminan Sosial Ditanggung Rumah Sakit (Pemberi Kerja):</span>
                <span class="text-[9px] font-normal italic text-zinc-500">*Bukan potongan gaji pegawai</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-zinc-900">
                <div>&bull; BPJS Kesehatan (4% Beban RS): <strong>Rp {{ number_format((float) $item->bpjs_kes_employer, 0, ',', '.') }}</strong></div>
                <div>&bull; BPJS Ketenagakerjaan (Beban RS): <strong>Rp {{ number_format((float) $item->bpjs_tk_employer, 0, ',', '.') }}</strong></div>
            </div>
        </div>

        {{-- Lembar Pengesahan Tanda Tangan Resmi --}}
        <div class="mt-3 pt-2 border-t border-zinc-300">
            <div class="text-right text-[10.5px] text-zinc-700 mb-2">
                <span>Bekasi, {{ $item->run?->run_date ? \Carbon\Carbon::parse($item->run->run_date)->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</span>
            </div>

            <div class="grid grid-cols-2 text-center text-[10.5px]">
                {{-- Tanda Tangan Pegawai --}}
                <div class="flex flex-col justify-between h-20 px-2">
                    <p class="text-zinc-800 font-bold">Penerima Penghasilan,</p>
                    <div>
                        <p class="font-bold text-black underline underline-offset-2">{{ $item->employee?->full_name }}</p>
                        <p class="text-zinc-700 text-[10px]">NIP. {{ $item->employee?->nip }}</p>
                    </div>
                </div>

                {{-- Tanda Tangan Otoritas SDM / RS --}}
                <div class="flex flex-col justify-between h-20 px-2">
                    <div>
                        <p class="text-zinc-800 font-bold">Pengelola SDM & Keuangan,</p>
                        <p class="text-black font-bold uppercase text-[10px]">{{ $hospitalName }}</p>
                    </div>
                    <div>
                        <div class="inline-block border border-zinc-400 px-2 py-0.5 bg-zinc-50 text-[9px] text-zinc-700 mb-0.5 font-bold">
                            [ Terverifikasi Sistem SIM-RS ]
                        </div>
                        <p class="font-bold text-black underline underline-offset-2">Bagian Administrasi SDM & Penggajian</p>
                        <p class="text-zinc-600 text-[9.5px]">Faskes Reg: {{ $hospitalFaskesCode }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Catatan Keabsahan & Kerahasiaan Dokumen --}}
        <div class="mt-2.5 pt-1.5 border-t border-zinc-300 text-center text-[9px] text-zinc-500 leading-tight">
            <p class="font-medium">
                Dokumen ini dicetak secara otomatis dan sah melalui Sistem Informasi Manajemen Rumah Sakit ({{ $hospitalName }}).
            </p>
            <p class="text-[8.5px] text-zinc-500 mt-0.5">
                Segala rincian penghasilan ini bersifat RAHASIA (CONFIDENTIAL) dan hanya diperuntukkan bagi pegawai yang bersangkutan.
            </p>
        </div>

    </div>
</body>
</html>

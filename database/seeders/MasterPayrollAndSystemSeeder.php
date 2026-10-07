<?php

namespace Database\Seeders;

use App\Models\Pph21Parameter;
use App\Models\SalaryComponent;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class MasterPayrollAndSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Komponen Penghasilan (Allowances)
        $earnings = [
            [
                'code' => 'TUNJ_PROF_MEDIS',
                'name' => 'Tunjangan Profesi / Keahlian Medis',
                'type' => 'allowance',
                'is_taxable' => true,
                'is_fixed' => true,
                'default_amount' => 2500000.00,
                'description' => 'Tunjangan kompetensi tenaga medis klinis dan spesialisasi',
                'is_active' => true,
            ],
            [
                'code' => 'TUNJ_JABATAN',
                'name' => 'Tunjangan Jabatan Struktural',
                'type' => 'allowance',
                'is_taxable' => true,
                'is_fixed' => true,
                'default_amount' => 1500000.00,
                'description' => 'Tunjangan untuk kepala unit, kepala ruang, dan manajerial',
                'is_active' => true,
            ],
            [
                'code' => 'TUNJ_SHIFT_MALAM',
                'name' => 'Insentif Shift Jaga Malam',
                'type' => 'allowance',
                'is_taxable' => true,
                'is_fixed' => false,
                'default_amount' => 75000.00,
                'description' => 'Insentif per shift jaga malam (21:00 - 07:00)',
                'is_active' => true,
            ],
            [
                'code' => 'TUNJ_TRANSPORT_MAKAN',
                'name' => 'Tunjangan Transport & Makan',
                'type' => 'allowance',
                'is_taxable' => true,
                'is_fixed' => false,
                'default_amount' => 50000.00,
                'description' => 'Uang kehadiran transport dan makan harian pegawai',
                'is_active' => true,
            ],
            [
                'code' => 'INSENTIF_KEHADIRAN',
                'name' => 'Premi Kehadiran Penuh',
                'type' => 'allowance',
                'is_taxable' => true,
                'is_fixed' => false,
                'default_amount' => 300000.00,
                'description' => 'Bonus bulanan bagi pegawai dengan absensi 100% tanpa terlambat',
                'is_active' => true,
            ],
        ];

        foreach ($earnings as $item) {
            SalaryComponent::updateOrCreate(['code' => $item['code']], $item);
        }

        // 2. Komponen Potongan (Deductions)
        $deductions = [
            [
                'code' => 'POT_KETERLAMBATAN',
                'name' => 'Potongan Keterlambatan Presensi',
                'type' => 'deduction',
                'is_taxable' => false,
                'is_fixed' => false,
                'default_amount' => 25000.00,
                'description' => 'Denda disiplin per 15 menit keterlambatan tanpa konfirmasi',
                'is_active' => true,
            ],
            [
                'code' => 'KOPERASI_RS',
                'name' => 'Iuran Koperasi Karyawan Sejahtera',
                'type' => 'deduction',
                'is_taxable' => false,
                'is_fixed' => true,
                'default_amount' => 100000.00,
                'description' => 'Simpanan wajib bulanan anggota koperasi rumah sakit',
                'is_active' => true,
            ],
            [
                'code' => 'KASBON_KARYAWAN',
                'name' => 'Cicilan Pinjaman / Kasbon Karyawan',
                'type' => 'deduction',
                'is_taxable' => false,
                'is_fixed' => false,
                'default_amount' => 0.00,
                'description' => 'Potongan cicilan pinjaman internal staf rumah sakit',
                'is_active' => true,
            ],
            [
                'code' => 'IURAN_PROFESI',
                'name' => 'Iuran Organisasi Profesi (IDI/PPNI/IBI)',
                'type' => 'deduction',
                'is_taxable' => false,
                'is_fixed' => true,
                'default_amount' => 50000.00,
                'description' => 'Penyaluran iuran keanggotaan profesi medis kolektif',
                'is_active' => true,
            ],
            [
                'code' => 'DONASI_SOSIAL',
                'name' => 'Infaq & Donasi Sosial Juwita Peduli',
                'type' => 'deduction',
                'is_taxable' => false,
                'is_fixed' => false,
                'default_amount' => 25000.00,
                'description' => 'Sumbangan sukarela untuk kegiatan sosial dhuafa & CSR',
                'is_active' => true,
            ],
        ];

        foreach ($deductions as $item) {
            SalaryComponent::updateOrCreate(['code' => $item['code']], $item);
        }

        // 3. Parameter PPh 21 (PTKP & Tarif Pasal 17 UU HPP)
        $pphParameters = [
            // PTKP Tahunan
            [
                'category' => 'ptkp',
                'code' => 'PTKP_TK0',
                'name' => 'Tidak Kawin Tanpa Tanggungan (TK/0)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 54000000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_TK1',
                'name' => 'Tidak Kawin 1 Tanggungan (TK/1)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 58500000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_TK2',
                'name' => 'Tidak Kawin 2 Tanggungan (TK/2)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 63000000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_TK3',
                'name' => 'Tidak Kawin 3 Tanggungan (TK/3)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 67500000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_K0',
                'name' => 'Kawin Tanpa Tanggungan (K/0)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 58500000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_K1',
                'name' => 'Kawin 1 Tanggungan (K/1)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 63000000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_K2',
                'name' => 'Kawin 2 Tanggungan (K/2)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 67500000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'ptkp',
                'code' => 'PTKP_K3',
                'name' => 'Kawin 3 Tanggungan (K/3)',
                'rate' => null,
                'threshold_min' => null,
                'threshold_max' => null,
                'amount' => 72000000.00,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            // Tarif Progresif Pasal 17 Ayat 1 UU Harmonisasi Peraturan Perpajakan (HPP)
            [
                'category' => 'tarif_pasal_17',
                'code' => 'PASAL17_LAP1',
                'name' => 'Lapisan I (s.d. Rp 60 Juta)',
                'rate' => 0.0500,
                'threshold_min' => 0.00,
                'threshold_max' => 60000000.00,
                'amount' => null,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'tarif_pasal_17',
                'code' => 'PASAL17_LAP2',
                'name' => 'Lapisan II (> Rp 60 Juta s.d. Rp 250 Juta)',
                'rate' => 0.1500,
                'threshold_min' => 60000000.00,
                'threshold_max' => 250000000.00,
                'amount' => null,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'tarif_pasal_17',
                'code' => 'PASAL17_LAP3',
                'name' => 'Lapisan III (> Rp 250 Juta s.d. Rp 500 Juta)',
                'rate' => 0.2500,
                'threshold_min' => 250000000.00,
                'threshold_max' => 500000000.00,
                'amount' => null,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'tarif_pasal_17',
                'code' => 'PASAL17_LAP4',
                'name' => 'Lapisan IV (> Rp 500 Juta s.d. Rp 5 Miliar)',
                'rate' => 0.3000,
                'threshold_min' => 500000000.00,
                'threshold_max' => 5000000000.00,
                'amount' => null,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
            [
                'category' => 'tarif_pasal_17',
                'code' => 'PASAL17_LAP5',
                'name' => 'Lapisan V (> Rp 5 Miliar)',
                'rate' => 0.3500,
                'threshold_min' => 5000000000.00,
                'threshold_max' => null,
                'amount' => null,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ],
        ];

        foreach ($pphParameters as $param) {
            Pph21Parameter::updateOrCreate(['code' => $param['code']], $param);
        }

        // 4. System Settings
        $settings = [
            ['group' => 'general', 'key' => 'hospital_name', 'value' => 'RS Permata Bunda', 'description' => 'Nama resmi faskes rumah sakit'],
            ['group' => 'general', 'key' => 'hospital_address', 'value' => 'Jl. Mayor M. Hasibuan No. 78, Bekasi Timur, Jawa Barat', 'description' => 'Alamat operasional rumah sakit'],
            ['group' => 'general', 'key' => 'hospital_phone', 'value' => '(021) 8816654', 'description' => 'Nomor kontak hotline / IGD'],
            ['group' => 'general', 'key' => 'hospital_email', 'value' => 'sdm@rsjuwita.co.id', 'description' => 'Email korespondensi SDM Rumah Sakit'],
            ['group' => 'general', 'key' => 'hospital_faskes_code', 'value' => '3275012', 'description' => 'Kode registrasi Faskes Kemenkes / BPJS'],
            ['group' => 'general', 'key' => 'hospital_npwp', 'value' => '01.889.345.2-407.000', 'description' => 'NPWP Rumah Sakit untuk pelaporan pajak'],
            ['group' => 'attendance', 'key' => 'late_grace_period_minutes', 'value' => '15', 'description' => 'Toleransi keterlambatan presensi dalam menit'],
            ['group' => 'attendance', 'key' => 'default_geofence_radius', 'value' => '100', 'description' => 'Radius geofence default dalam meter'],
            ['group' => 'security', 'key' => 'session_timeout_minutes', 'value' => '120', 'description' => 'Waktu kadaluarsa sesi login tanpa aktivitas'],
            ['group' => 'security', 'key' => 'biometric_liveness_required', 'value' => '1', 'description' => 'Wajib verifikasi liveness anti-spoofing pada Face AI'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}

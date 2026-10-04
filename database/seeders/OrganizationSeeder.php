<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Holiday;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Based on SK.DIRUT PT. SEHATI MEDIKA INVESTAMA NO. : HK.02.01/SMI/006.1/I/2023
     * STRUKTUR ORGANISASI RUMAH SAKIT JUWITA
     */
    public function run(): void
    {
        // ==========================================
        // 1. DEPARTEMEN / DIREKTORAT / BIDANG / BAGIAN
        // ==========================================
        $directorship = Department::updateOrCreate(
            ['code' => 'DIR'],
            ['name' => 'Direktorat Utama', 'parent_id' => null, 'is_active' => true]
        );

        $yanmed = Department::updateOrCreate(
            ['code' => 'YANMED'],
            ['name' => 'Pelayanan Medik', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $jangmed = Department::updateOrCreate(
            ['code' => 'JANGMED'],
            ['name' => 'Penunjang Medik', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $kep = Department::updateOrCreate(
            ['code' => 'KEP'],
            ['name' => 'Keperawatan', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $mkt = Department::updateOrCreate(
            ['code' => 'MKT'],
            ['name' => 'Marketing', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $umSdm = Department::updateOrCreate(
            ['code' => 'UM-SDM'],
            ['name' => 'Umum & SDM', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $keu = Department::updateOrCreate(
            ['code' => 'KEU'],
            ['name' => 'Keuangan & Akuntansi', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        // ==========================================
        // 2. UNITS / SUB-BAGIAN / INSTALASI
        // ==========================================
        // 2.1 Staf Direksi, Komite & Tim SK Direktur
        $sekretariat = Unit::updateOrCreate(
            ['code' => 'SEKRETARIS'],
            ['department_id' => $directorship->id, 'name' => 'Sekretariat Direksi', 'is_active' => true]
        );

        $spi = Unit::updateOrCreate(
            ['code' => 'SPI'],
            ['department_id' => $directorship->id, 'name' => 'Satuan Pengawas Internal (SPI)', 'is_active' => true]
        );

        $casemix = Unit::updateOrCreate(
            ['code' => 'CASEMIX'],
            ['department_id' => $directorship->id, 'name' => 'Tim Casemix & Pengendali BPJS', 'is_active' => true]
        );

        $komiteMedik = Unit::updateOrCreate(
            ['code' => 'KOM-MEDIK'],
            ['department_id' => $directorship->id, 'name' => 'Komite Medik & Sub Komite', 'is_active' => true]
        );

        // 2.2 Bidang Pelayanan Medik (YANMED)
        $rajal = Unit::updateOrCreate(
            ['code' => 'RAJAL'],
            ['department_id' => $yanmed->id, 'name' => 'Unit Rawat Jalan (Poliklinik)', 'is_active' => true]
        );

        $ranap = Unit::updateOrCreate(
            ['code' => 'RANAP'],
            ['department_id' => $yanmed->id, 'name' => 'Unit Rawat Inap', 'is_active' => true]
        );

        $igd = Unit::updateOrCreate(
            ['code' => 'IGD'],
            ['department_id' => $yanmed->id, 'name' => 'Instalasi Gawat Darurat', 'is_active' => true]
        );

        $okVk = Unit::updateOrCreate(
            ['code' => 'OK-VK'],
            ['department_id' => $yanmed->id, 'name' => 'Unit Kamar Operasi & VK (Bersalin)', 'is_active' => true]
        );

        $icu = Unit::updateOrCreate(
            ['code' => 'ICU'],
            ['department_id' => $yanmed->id, 'name' => 'Unit ICU & NICU', 'is_active' => true]
        );

        $perina = Unit::updateOrCreate(
            ['code' => 'PERINA'],
            ['department_id' => $yanmed->id, 'name' => 'Unit Ruang Bayi & Perinatologi', 'is_active' => true]
        );

        $hd = Unit::updateOrCreate(
            ['code' => 'HD'],
            ['department_id' => $yanmed->id, 'name' => 'Unit Hemodialisa', 'is_active' => true]
        );

        // 2.3 Bidang Penunjang Medis (JANGMED)
        $farmasi = Unit::updateOrCreate(
            ['code' => 'FAR'],
            ['department_id' => $jangmed->id, 'name' => 'Instalasi Farmasi', 'is_active' => true]
        );

        $radiologi = Unit::updateOrCreate(
            ['code' => 'RAD'],
            ['department_id' => $jangmed->id, 'name' => 'Unit Radiologi', 'is_active' => true]
        );

        $gizi = Unit::updateOrCreate(
            ['code' => 'GIZI'],
            ['department_id' => $jangmed->id, 'name' => 'Unit Gizi & Dapur', 'is_active' => true]
        );

        $rekamMedik = Unit::updateOrCreate(
            ['code' => 'RM'],
            ['department_id' => $jangmed->id, 'name' => 'Unit Rekam Medik', 'is_active' => true]
        );

        $laboratorium = Unit::updateOrCreate(
            ['code' => 'LAB'],
            ['department_id' => $jangmed->id, 'name' => 'Unit Laboratorium', 'is_active' => true]
        );

        $rehabMedik = Unit::updateOrCreate(
            ['code' => 'REHAB'],
            ['department_id' => $jangmed->id, 'name' => 'Unit Rehabilitasi Medik', 'is_active' => true]
        );

        // 2.4 Bidang Keperawatan (KEP)
        $kepRanap = Unit::updateOrCreate(
            ['code' => 'KEP-RANAP'],
            ['department_id' => $kep->id, 'name' => 'Pelayanan Keperawatan Rawat Inap', 'is_active' => true]
        );

        $kepRajal = Unit::updateOrCreate(
            ['code' => 'KEP-RAJAL'],
            ['department_id' => $kep->id, 'name' => 'Pelayanan Keperawatan Rawat Jalan', 'is_active' => true]
        );

        $kepKhusus = Unit::updateOrCreate(
            ['code' => 'KEP-KHUSUS'],
            ['department_id' => $kep->id, 'name' => 'Pelayanan Keperawatan Rawat Khusus (ICU/OK)', 'is_active' => true]
        );

        $kepHd = Unit::updateOrCreate(
            ['code' => 'KEP-HD'],
            ['department_id' => $kep->id, 'name' => 'Pelayanan Keperawatan Hemodialisa', 'is_active' => true]
        );

        $kepKebidanan = Unit::updateOrCreate(
            ['code' => 'KEP-KEBIDANAN'],
            ['department_id' => $kep->id, 'name' => 'Pelayanan Kandungan & Kebidanan', 'is_active' => true]
        );

        // 2.5 Bagian Marketing (MKT)
        $mktEks = Unit::updateOrCreate(
            ['code' => 'MKT-EKS'],
            ['department_id' => $mkt->id, 'name' => 'Marketing Eksternal & Kerjasama Asuransi', 'is_active' => true]
        );

        $mktInt = Unit::updateOrCreate(
            ['code' => 'MKT-INT'],
            ['department_id' => $mkt->id, 'name' => 'Marketing Internal & Humas', 'is_active' => true]
        );

        $custCare = Unit::updateOrCreate(
            ['code' => 'CUST-CARE'],
            ['department_id' => $mkt->id, 'name' => 'Customer Care & Pendaftaran Pasien', 'is_active' => true]
        );

        // 2.6 Bagian Umum & SDM (UM-SDM)
        $itUnit = Unit::updateOrCreate(
            ['code' => 'IT'],
            ['department_id' => $umSdm->id, 'name' => 'Sub Bagian Umum - Unit SIMRS & TI', 'is_active' => true]
        );

        $umumUnit = Unit::updateOrCreate(
            ['code' => 'UMUM'],
            ['department_id' => $umSdm->id, 'name' => 'Sub Bagian Umum - IPSRS, Logistik & Keamanan', 'is_active' => true]
        );

        $sdmUnit = Unit::updateOrCreate(
            ['code' => 'SDM'],
            ['department_id' => $umSdm->id, 'name' => 'Sub Bagian SDM & Diklat', 'is_active' => true]
        );

        // 2.7 Bagian Keuangan (KEU)
        $keuUnit = Unit::updateOrCreate(
            ['code' => 'KEU'],
            ['department_id' => $keu->id, 'name' => 'Sub Bagian Keuangan & Akuntansi', 'is_active' => true]
        );

        // 2.8 Komite & Tim Tambahan SK Direktur
        $komiteKep = Unit::updateOrCreate(
            ['code' => 'KOM-KEP'],
            ['department_id' => $directorship->id, 'name' => 'Komite Keperawatan', 'is_active' => true]
        );

        $komiteFar = Unit::updateOrCreate(
            ['code' => 'KOM-FAR'],
            ['department_id' => $directorship->id, 'name' => 'Komite Farmasi & Terapi', 'is_active' => true]
        );

        $komiteNakes = Unit::updateOrCreate(
            ['code' => 'KOM-NAKES'],
            ['department_id' => $directorship->id, 'name' => 'Komite Tenaga Kesehatan Lain', 'is_active' => true]
        );

        $komitePmkp = Unit::updateOrCreate(
            ['code' => 'KOM-PMKP'],
            ['department_id' => $directorship->id, 'name' => 'Komite Mutu & PMKP', 'is_active' => true]
        );

        $komiteEtik = Unit::updateOrCreate(
            ['code' => 'KOM-ETIK'],
            ['department_id' => $directorship->id, 'name' => 'Komite Etik & Hukum Rumah Sakit', 'is_active' => true]
        );

        $timPpi = Unit::updateOrCreate(
            ['code' => 'TIM-PPI'],
            ['department_id' => $directorship->id, 'name' => 'Tim PPI & PPIRS', 'is_active' => true]
        );

        $timK3rs = Unit::updateOrCreate(
            ['code' => 'TIM-K3RS'],
            ['department_id' => $directorship->id, 'name' => 'Tim K3RS', 'is_active' => true]
        );

        $timPonek = Unit::updateOrCreate(
            ['code' => 'TIM-PONEK'],
            ['department_id' => $directorship->id, 'name' => 'Tim PONEK Rumah Sakit', 'is_active' => true]
        );

        $timTb = Unit::updateOrCreate(
            ['code' => 'TIM-TBDOT'],
            ['department_id' => $directorship->id, 'name' => 'Tim TB-DOTS', 'is_active' => true]
        );

        $timHiv = Unit::updateOrCreate(
            ['code' => 'TIM-HIV'],
            ['department_id' => $directorship->id, 'name' => 'Tim Penanggulangan HIV/AIDS', 'is_active' => true]
        );

        $timStunting = Unit::updateOrCreate(
            ['code' => 'TIM-STUNTING'],
            ['department_id' => $directorship->id, 'name' => 'Tim Penurunan Prevalensi Stunting', 'is_active' => true]
        );

        $timPkbrs = Unit::updateOrCreate(
            ['code' => 'TIM-PKBRS'],
            ['department_id' => $directorship->id, 'name' => 'Tim Pelayanan KB Rumah Sakit (PKBRS)', 'is_active' => true]
        );

        $timPra = Unit::updateOrCreate(
            ['code' => 'TIM-PRA'],
            ['department_id' => $directorship->id, 'name' => 'Tim Program Pengendalian Resistensi Antimikroba', 'is_active' => true]
        );

        $panRm = Unit::updateOrCreate(
            ['code' => 'PAN-RM'],
            ['department_id' => $directorship->id, 'name' => 'Panitia Rekam Medis', 'is_active' => true]
        );

        // ==========================================
        // 3. POSITIONS / JABATAN STRUKTURAL & FUNGSIONAL (STANDAR JABATAN)
        // ==========================================
        $positionsData = [
            // Level 0: Direktur Puncak
            ['unit_id' => $sekretariat->id, 'title' => 'Direktur Rumah Sakit Juwita', 'grade_level' => 'director', 'basic' => 25000000.00, 'allowance' => 10000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/DIR-001'],

            // Level Staf Direksi, Komite & Tim
            ['unit_id' => $sekretariat->id, 'title' => 'Sekretaris Direktur', 'grade_level' => 'staff', 'basic' => 6000000.00, 'allowance' => 1000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/SEK-001'],
            ['unit_id' => $spi->id, 'title' => 'Kepala Satuan Pengawas Internal (SPI)', 'grade_level' => 'manager', 'basic' => 12000000.00, 'allowance' => 3500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/SPI-001'],
            ['unit_id' => $casemix->id, 'title' => 'Ketua Tim Casemix', 'grade_level' => 'supervisor', 'basic' => 9000000.00, 'allowance' => 2500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/CMX-001'],
            ['unit_id' => $komiteMedik->id, 'title' => 'Ketua Komite Medik', 'grade_level' => 'specialist', 'basic' => 15000000.00, 'allowance' => 5000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KOM-001'],
            ['unit_id' => $komiteKep->id, 'title' => 'Ketua Komite Keperawatan', 'grade_level' => 'supervisor', 'basic' => 9000000.00, 'allowance' => 2500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KOM-002'],
            ['unit_id' => $komitePmkp->id, 'title' => 'Ketua Komite Mutu & PMKP', 'grade_level' => 'supervisor', 'basic' => 9000000.00, 'allowance' => 2500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KOM-003'],
            ['unit_id' => $timK3rs->id, 'title' => 'Ketua Tim K3RS & PPI', 'grade_level' => 'supervisor', 'basic' => 8500000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/TIM-001'],

            // Level 1: Kepala Bidang & Kepala Bagian (5 Pilar)
            ['unit_id' => $ranap->id, 'title' => 'Kepala Bidang Pelayanan Medik', 'grade_level' => 'manager', 'basic' => 14000000.00, 'allowance' => 4500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KB-YANMED'],
            ['unit_id' => $farmasi->id, 'title' => 'Kepala Bidang Penunjang Medis', 'grade_level' => 'manager', 'basic' => 13500000.00, 'allowance' => 4000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KB-JANGMED'],
            ['unit_id' => $kepRanap->id, 'title' => 'Kepala Bidang Keperawatan', 'grade_level' => 'manager', 'basic' => 13500000.00, 'allowance' => 4000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KB-KEP'],
            ['unit_id' => $mktEks->id, 'title' => 'Kepala Bagian Marketing', 'grade_level' => 'manager', 'basic' => 13000000.00, 'allowance' => 4000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KB-MKT'],
            ['unit_id' => $sdmUnit->id, 'title' => 'Kepala Bagian Umum, SDM dan Keuangan', 'grade_level' => 'manager', 'basic' => 14500000.00, 'allowance' => 4500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KB-UMSDMKEU'],

            // Level 2: Pimpinan Unit / PJ di bawah Umum, SDM dan Keuangan
            ['unit_id' => $itUnit->id, 'title' => 'Penanggung Jawab (PJ) Unit IT & SIMRS', 'grade_level' => 'supervisor', 'basic' => 8500000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/PJ-IT'],
            ['unit_id' => $umumUnit->id, 'title' => 'Penanggung Jawab (PJ) Unit Umum & IPSRS', 'grade_level' => 'supervisor', 'basic' => 8000000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/PJ-UMUM'],
            ['unit_id' => $sdmUnit->id, 'title' => 'Kepala Bagian SDM', 'grade_level' => 'manager', 'basic' => 9500000.00, 'allowance' => 2500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KB-SDM'],
            ['unit_id' => $keuUnit->id, 'title' => 'Kepala Sub Bagian Keuangan & Akuntansi', 'grade_level' => 'supervisor', 'basic' => 9000000.00, 'allowance' => 2500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KS-KEU'],

            // Level 2: Pimpinan Unit / PJ di bawah Pelayanan Medik
            ['unit_id' => $igd->id, 'title' => 'Kepala Instalasi Gawat Darurat', 'grade_level' => 'manager', 'basic' => 9500000.00, 'allowance' => 3000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KI-IGD'],
            ['unit_id' => $icu->id, 'title' => 'Kepala Ruang ICU', 'grade_level' => 'supervisor', 'basic' => 7800000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KR-ICU'],
            ['unit_id' => $ranap->id, 'title' => 'Kepala Ruang Rawat Inap', 'grade_level' => 'supervisor', 'basic' => 7800000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KR-RANAP'],
            ['unit_id' => $rajal->id, 'title' => 'Kepala Ruang Rawat Jalan', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KR-RAJAL'],
            ['unit_id' => $okVk->id, 'title' => 'Kepala Unit OK & VK', 'grade_level' => 'supervisor', 'basic' => 8500000.00, 'allowance' => 2200000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KU-OKVK'],
            ['unit_id' => $perina->id, 'title' => 'Kepala Ruang Bayi & Perina', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KR-PERINA'],
            ['unit_id' => $hd->id, 'title' => 'Kepala Unit Hemodialisa', 'grade_level' => 'supervisor', 'basic' => 8000000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KU-HD'],

            // Level 2: Pimpinan Unit / PJ di bawah Penunjang Medis
            ['unit_id' => $farmasi->id, 'title' => 'Apoteker Penanggung Jawab', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/APJ-FAR'],
            ['unit_id' => $laboratorium->id, 'title' => 'Kepala Laboratorium', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KU-LAB'],
            ['unit_id' => $radiologi->id, 'title' => 'Kepala Unit Radiologi', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KU-RAD'],
            ['unit_id' => $gizi->id, 'title' => 'Kepala Instalasi Gizi', 'grade_level' => 'supervisor', 'basic' => 7000000.00, 'allowance' => 1500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KI-GIZI'],
            ['unit_id' => $rekamMedik->id, 'title' => 'Kepala Unit Rekam Medik', 'grade_level' => 'supervisor', 'basic' => 7000000.00, 'allowance' => 1500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KU-RM'],
            ['unit_id' => $rehabMedik->id, 'title' => 'Kepala Unit Rehabilitasi Medik', 'grade_level' => 'supervisor', 'basic' => 7200000.00, 'allowance' => 1600000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KU-REHAB'],

            // Level 2: Pimpinan di bawah Keperawatan & Marketing
            ['unit_id' => $kepRanap->id, 'title' => 'Koordinator Keperawatan Rawat Inap', 'grade_level' => 'supervisor', 'basic' => 8000000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KD-KEPRANAP'],
            ['unit_id' => $kepRajal->id, 'title' => 'Koordinator Keperawatan Rawat Jalan', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KD-KEPRAJAL'],
            ['unit_id' => $kepKhusus->id, 'title' => 'Koordinator Keperawatan Rawat Khusus', 'grade_level' => 'supervisor', 'basic' => 8000000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KD-KEPKHUSUS'],
            ['unit_id' => $kepKebidanan->id, 'title' => 'Koordinator Kebidanan & Kandungan', 'grade_level' => 'supervisor', 'basic' => 7800000.00, 'allowance' => 1900000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/KD-KEPBIDAN'],
            ['unit_id' => $mktEks->id, 'title' => 'Penanggung Jawab Marketing Eksternal', 'grade_level' => 'supervisor', 'basic' => 7500000.00, 'allowance' => 1800000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/PJ-MKTEKS'],
            ['unit_id' => $mktInt->id, 'title' => 'Penanggung Jawab Marketing Internal & Humas', 'grade_level' => 'supervisor', 'basic' => 7200000.00, 'allowance' => 1600000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/PJ-MKTINT'],
            ['unit_id' => $custCare->id, 'title' => 'Penanggung Jawab Customer Care & Pendaftaran', 'grade_level' => 'supervisor', 'basic' => 7000000.00, 'allowance' => 1500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/PJ-CUSTCARE'],

            // Level 3: Staf Fungsional & Pelaksana
            ['unit_id' => $itUnit->id, 'title' => 'Staff Programmer SIMRS', 'grade_level' => 'staff', 'basic' => 6000000.00, 'allowance' => 500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-PROG'],
            ['unit_id' => $itUnit->id, 'title' => 'Staff Teknisi IT & Jaringan', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-TEKIT'],
            ['unit_id' => $umumUnit->id, 'title' => 'Staff Teknisi Elektromedis & IPSRS', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-IPSRS'],
            ['unit_id' => $sdmUnit->id, 'title' => 'Staff Penggajian & HRIS', 'grade_level' => 'staff', 'basic' => 5500000.00, 'allowance' => 500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-HRIS'],
            ['unit_id' => $sdmUnit->id, 'title' => 'Staff Rekrutmen & Diklat', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-REKRUT'],
            ['unit_id' => $keuUnit->id, 'title' => 'Staff Kasir Rumah Sakit', 'grade_level' => 'staff', 'basic' => 4800000.00, 'allowance' => 300000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-KASIR'],
            ['unit_id' => $keuUnit->id, 'title' => 'Staff Akuntansi & Perpajakan', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-AKUN'],
            ['unit_id' => $custCare->id, 'title' => 'Staff Pendaftaran Pasien (Front Office)', 'grade_level' => 'staff', 'basic' => 4800000.00, 'allowance' => 300000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-FO'],

            ['unit_id' => $igd->id, 'title' => 'Dokter Jaga IGD', 'grade_level' => 'specialist', 'basic' => 8500000.00, 'allowance' => 2000000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-DRIGD'],
            ['unit_id' => $igd->id, 'title' => 'Perawat Pelaksana IGD', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 500000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-PRWIGD'],
            ['unit_id' => $icu->id, 'title' => 'Perawat Mahir ICU', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 600000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-PRWICU'],
            ['unit_id' => $ranap->id, 'title' => 'Perawat Pelaksana Rawat Inap', 'grade_level' => 'staff', 'basic' => 5000000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-PRWRANAP'],
            ['unit_id' => $rajal->id, 'title' => 'Perawat Pelaksana Rawat Jalan', 'grade_level' => 'staff', 'basic' => 5000000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-PRWRAJAL'],
            ['unit_id' => $okVk->id, 'title' => 'Bidan Pelaksana VK', 'grade_level' => 'staff', 'basic' => 5100000.00, 'allowance' => 450000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-BDNVK'],
            ['unit_id' => $farmasi->id, 'title' => 'Tenaga Teknis Kefarmasian', 'grade_level' => 'staff', 'basic' => 5000000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-TTK'],
            ['unit_id' => $laboratorium->id, 'title' => 'Pranata Laboratorium Kesehatan', 'grade_level' => 'staff', 'basic' => 5000000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-LAB'],
            ['unit_id' => $radiologi->id, 'title' => 'Radiografer', 'grade_level' => 'staff', 'basic' => 5200000.00, 'allowance' => 450000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-RAD'],
            ['unit_id' => $gizi->id, 'title' => 'Nutrisionis / Dietisien', 'grade_level' => 'staff', 'basic' => 5000000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-GIZI'],
            ['unit_id' => $rekamMedik->id, 'title' => 'Perekam Medis', 'grade_level' => 'staff', 'basic' => 4900000.00, 'allowance' => 350000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-RM'],
            ['unit_id' => $rehabMedik->id, 'title' => 'Fisioterapis', 'grade_level' => 'staff', 'basic' => 5000000.00, 'allowance' => 400000.00, 'contract_number' => 'HK.02.01/SMI/006.1/I/2023/ST-FISIO'],
        ];

        foreach ($positionsData as $pos) {
            Position::updateOrCreate(
                ['title' => $pos['title']],
                [
                    'unit_id' => $pos['unit_id'],
                    'grade_level' => $pos['grade_level'],
                    'default_basic_salary' => $pos['basic'],
                    'default_positional_allowance' => $pos['allowance'],
                    'default_contract_number' => $pos['contract_number'] ?? null,
                ]
            );
        }

        // ==========================================
        // 4. LINKING REPORTS_TO_ID (HIRARKI RANTAI KOMANDO)
        // ==========================================
        $reportingHierarchy = [
            // Pimpinan Bidang/Bagian, Staf Direksi, Komite -> Direktur
            'Sekretaris Direktur' => 'Direktur Rumah Sakit Juwita',
            'Kepala Satuan Pengawas Internal (SPI)' => 'Direktur Rumah Sakit Juwita',
            'Ketua Tim Casemix' => 'Direktur Rumah Sakit Juwita',
            'Ketua Komite Medik' => 'Direktur Rumah Sakit Juwita',
            'Ketua Komite Keperawatan' => 'Direktur Rumah Sakit Juwita',
            'Ketua Komite Mutu & PMKP' => 'Direktur Rumah Sakit Juwita',
            'Ketua Tim K3RS & PPI' => 'Direktur Rumah Sakit Juwita',
            'Kepala Bidang Pelayanan Medik' => 'Direktur Rumah Sakit Juwita',
            'Kepala Bidang Penunjang Medis' => 'Direktur Rumah Sakit Juwita',
            'Kepala Bidang Keperawatan' => 'Direktur Rumah Sakit Juwita',
            'Kepala Bagian Marketing' => 'Direktur Rumah Sakit Juwita',
            'Kepala Bagian Umum, SDM dan Keuangan' => 'Direktur Rumah Sakit Juwita',

            // Di bawah Bagian Umum, SDM dan Keuangan
            'Penanggung Jawab (PJ) Unit IT & SIMRS' => 'Kepala Bagian Umum, SDM dan Keuangan',
            'Penanggung Jawab (PJ) Unit Umum & IPSRS' => 'Kepala Bagian Umum, SDM dan Keuangan',
            'Kepala Bagian SDM' => 'Kepala Bagian Umum, SDM dan Keuangan',
            'Kepala Sub Bagian Keuangan & Akuntansi' => 'Kepala Bagian Umum, SDM dan Keuangan',

            // Di bawah Pelayanan Medik
            'Kepala Instalasi Gawat Darurat' => 'Kepala Bidang Pelayanan Medik',
            'Kepala Ruang ICU' => 'Kepala Bidang Pelayanan Medik',
            'Kepala Ruang Rawat Inap' => 'Kepala Bidang Pelayanan Medik',
            'Kepala Ruang Rawat Jalan' => 'Kepala Bidang Pelayanan Medik',
            'Kepala Unit OK & VK' => 'Kepala Bidang Pelayanan Medik',
            'Kepala Ruang Bayi & Perina' => 'Kepala Bidang Pelayanan Medik',
            'Kepala Unit Hemodialisa' => 'Kepala Bidang Pelayanan Medik',

            // Di bawah Penunjang Medis
            'Apoteker Penanggung Jawab' => 'Kepala Bidang Penunjang Medis',
            'Kepala Laboratorium' => 'Kepala Bidang Penunjang Medis',
            'Kepala Unit Radiologi' => 'Kepala Bidang Penunjang Medis',
            'Kepala Instalasi Gizi' => 'Kepala Bidang Penunjang Medis',
            'Kepala Unit Rekam Medik' => 'Kepala Bidang Penunjang Medis',
            'Kepala Unit Rehabilitasi Medik' => 'Kepala Bidang Penunjang Medis',

            // Di bawah Keperawatan
            'Koordinator Keperawatan Rawat Inap' => 'Kepala Bidang Keperawatan',
            'Koordinator Keperawatan Rawat Jalan' => 'Kepala Bidang Keperawatan',
            'Koordinator Keperawatan Rawat Khusus' => 'Kepala Bidang Keperawatan',
            'Koordinator Kebidanan & Kandungan' => 'Kepala Bidang Keperawatan',

            // Di bawah Marketing
            'Penanggung Jawab Marketing Eksternal' => 'Kepala Bagian Marketing',
            'Penanggung Jawab Marketing Internal & Humas' => 'Kepala Bagian Marketing',
            'Penanggung Jawab Customer Care & Pendaftaran' => 'Kepala Bagian Marketing',

            // Staf Pelaksana IT -> PJ Unit IT
            'Staff Programmer SIMRS' => 'Penanggung Jawab (PJ) Unit IT & SIMRS',
            'Staff Teknisi IT & Jaringan' => 'Penanggung Jawab (PJ) Unit IT & SIMRS',

            // Staf Pelaksana Umum & IPSRS -> PJ Umum & IPSRS
            'Staff Teknisi Elektromedis & IPSRS' => 'Penanggung Jawab (PJ) Unit Umum & IPSRS',

            // Staf Pelaksana SDM & Keu
            'Staff Penggajian & HRIS' => 'Kepala Bagian SDM',
            'Staff Rekrutmen & Diklat' => 'Kepala Bagian SDM',
            'Staff Kasir Rumah Sakit' => 'Kepala Sub Bagian Keuangan & Akuntansi',
            'Staff Akuntansi & Perpajakan' => 'Kepala Sub Bagian Keuangan & Akuntansi',

            // Staf Pelaksana Customer Care -> PJ Customer Care
            'Staff Pendaftaran Pasien (Front Office)' => 'Penanggung Jawab Customer Care & Pendaftaran',

            // Staf Pelaksana Medis & Keperawatan
            'Dokter Jaga IGD' => 'Kepala Instalasi Gawat Darurat',
            'Perawat Pelaksana IGD' => 'Kepala Instalasi Gawat Darurat',
            'Perawat Mahir ICU' => 'Kepala Ruang ICU',
            'Perawat Pelaksana Rawat Inap' => 'Kepala Ruang Rawat Inap',
            'Perawat Pelaksana Rawat Jalan' => 'Kepala Ruang Rawat Jalan',
            'Bidan Pelaksana VK' => 'Kepala Unit OK & VK',

            // Staf Pelaksana Penunjang Medis
            'Tenaga Teknis Kefarmasian' => 'Apoteker Penanggung Jawab',
            'Pranata Laboratorium Kesehatan' => 'Kepala Laboratorium',
            'Radiografer' => 'Kepala Unit Radiologi',
            'Nutrisionis / Dietisien' => 'Kepala Instalasi Gizi',
            'Perekam Medis' => 'Kepala Unit Rekam Medik',
            'Fisioterapis' => 'Kepala Unit Rehabilitasi Medik',
        ];

        foreach ($reportingHierarchy as $subordinateTitle => $supervisorTitle) {
            $supervisorPos = Position::where('title', $supervisorTitle)->first();
            $subordinatePos = Position::where('title', $subordinateTitle)->first();

            if ($supervisorPos && $subordinatePos) {
                $subordinatePos->update(['reports_to_id' => $supervisorPos->id]);
            }
        }

        // ==========================================
        // 5. PUBLIC HOLIDAYS 2026
        // ==========================================
        $holidays = [
            ['holiday_date' => '2026-01-01', 'name' => 'Tahun Baru 2026 Masehi', 'is_collective_leave' => false],
            ['holiday_date' => '2026-01-16', 'name' => 'Isra Mi\'raj Nabi Muhammad SAW', 'is_collective_leave' => false],
            ['holiday_date' => '2026-02-17', 'name' => 'Tahun Baru Imlek 2577 Kongzili', 'is_collective_leave' => false],
            ['holiday_date' => '2026-03-20', 'name' => 'Hari Suci Nyepi Tahun Baru Saka 1948', 'is_collective_leave' => false],
            ['holiday_date' => '2026-03-21', 'name' => 'Hari Raya Idul Fitri 1447 H (Hari Pertama)', 'is_collective_leave' => false],
            ['holiday_date' => '2026-03-22', 'name' => 'Hari Raya Idul Fitri 1447 H (Hari Kedua)', 'is_collective_leave' => false],
            ['holiday_date' => '2026-03-23', 'name' => 'Cuti Bersama Hari Raya Idul Fitri 1447 H', 'is_collective_leave' => true],
            ['holiday_date' => '2026-03-24', 'name' => 'Cuti Bersama Hari Raya Idul Fitri 1447 H', 'is_collective_leave' => true],
            ['holiday_date' => '2026-04-03', 'name' => 'Wafat Yesus Kristus', 'is_collective_leave' => false],
            ['holiday_date' => '2026-04-05', 'name' => 'Hari Paskah', 'is_collective_leave' => false],
            ['holiday_date' => '2026-05-01', 'name' => 'Hari Buruh Internasional', 'is_collective_leave' => false],
            ['holiday_date' => '2026-05-14', 'name' => 'Kenaikan Yesus Kristus', 'is_collective_leave' => false],
            ['holiday_date' => '2026-05-27', 'name' => 'Hari Raya Idul Adha 1447 H', 'is_collective_leave' => false],
            ['holiday_date' => '2026-05-31', 'name' => 'Hari Raya Waisak 2570 BE', 'is_collective_leave' => false],
            ['holiday_date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila', 'is_collective_leave' => false],
            ['holiday_date' => '2026-06-16', 'name' => 'Tahun Baru Islam 1448 H', 'is_collective_leave' => false],
            ['holiday_date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI ke-81', 'is_collective_leave' => false],
            ['holiday_date' => '2026-08-25', 'name' => 'Maulid Nabi Muhammad SAW', 'is_collective_leave' => false],
            ['holiday_date' => '2026-12-25', 'name' => 'Hari Raya Natal', 'is_collective_leave' => false],
            ['holiday_date' => '2026-12-26', 'name' => 'Cuti Bersama Hari Raya Natal', 'is_collective_leave' => true],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['holiday_date' => $holiday['holiday_date']],
                [
                    'name' => $holiday['name'],
                    'is_collective_leave' => $holiday['is_collective_leave'],
                ]
            );
        }
    }
}

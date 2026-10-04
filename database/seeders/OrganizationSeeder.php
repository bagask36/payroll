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
     */
    public function run(): void
    {
        // 1. Departments / Directorates
        $directorship = Department::updateOrCreate(
            ['code' => 'DIR'],
            ['name' => 'Direktorat Utama', 'parent_id' => null, 'is_active' => true]
        );

        $yanmed = Department::updateOrCreate(
            ['code' => 'YANMED'],
            ['name' => 'Pelayanan Medik', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $kep = Department::updateOrCreate(
            ['code' => 'KEP'],
            ['name' => 'Keperawatan', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $jangmed = Department::updateOrCreate(
            ['code' => 'JANGMED'],
            ['name' => 'Penunjang Medik', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $umsdm = Department::updateOrCreate(
            ['code' => 'UM-SDM'],
            ['name' => 'Umum & SDM', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        $keu = Department::updateOrCreate(
            ['code' => 'KEU'],
            ['name' => 'Keuangan & Akuntansi', 'parent_id' => $directorship->id, 'is_active' => true]
        );

        // 2. Units
        $igd = Unit::updateOrCreate(
            ['code' => 'IGD'],
            ['department_id' => $yanmed->id, 'name' => 'Instalasi Gawat Darurat', 'is_active' => true]
        );

        $icu = Unit::updateOrCreate(
            ['code' => 'ICU'],
            ['department_id' => $yanmed->id, 'name' => 'Intensive Care Unit', 'is_active' => true]
        );

        $ok = Unit::updateOrCreate(
            ['code' => 'OK'],
            ['department_id' => $yanmed->id, 'name' => 'Kamar Operasi (Bedah)', 'is_active' => true]
        );

        $rajal = Unit::updateOrCreate(
            ['code' => 'RAJAL'],
            ['department_id' => $yanmed->id, 'name' => 'Instalasi Rawat Jalan (Poliklinik)', 'is_active' => true]
        );

        $ranap = Unit::updateOrCreate(
            ['code' => 'RANAP'],
            ['department_id' => $yanmed->id, 'name' => 'Instalasi Rawat Inap', 'is_active' => true]
        );

        $farmasi = Unit::updateOrCreate(
            ['code' => 'FAR'],
            ['department_id' => $jangmed->id, 'name' => 'Instalasi Farmasi', 'is_active' => true]
        );

        $lab = Unit::updateOrCreate(
            ['code' => 'LAB'],
            ['department_id' => $jangmed->id, 'name' => 'Instalasi Laboratorium', 'is_active' => true]
        );

        $rad = Unit::updateOrCreate(
            ['code' => 'RAD'],
            ['department_id' => $jangmed->id, 'name' => 'Instalasi Radiologi', 'is_active' => true]
        );

        $sdm = Unit::updateOrCreate(
            ['code' => 'SDM'],
            ['department_id' => $umsdm->id, 'name' => 'Sub Bagian SDM & Diklat', 'is_active' => true]
        );

        $it = Unit::updateOrCreate(
            ['code' => 'IT'],
            ['department_id' => $umsdm->id, 'name' => 'Unit SIMRS & TI', 'is_active' => true]
        );

        $kasir = Unit::updateOrCreate(
            ['code' => 'KASIR'],
            ['department_id' => $keu->id, 'name' => 'Unit Kasir & Penagihan', 'is_active' => true]
        );

        // 3. Positions
        $positions = [
            ['unit_id' => $igd->id, 'title' => 'Kepala Instalasi Gawat Darurat', 'grade_level' => 'manager', 'default_basic_salary' => 9500000.00, 'default_positional_allowance' => 3000000.00],
            ['unit_id' => $igd->id, 'title' => 'Dokter Jaga IGD', 'grade_level' => 'specialist', 'default_basic_salary' => 8500000.00, 'default_positional_allowance' => 2000000.00],
            ['unit_id' => $igd->id, 'title' => 'Perawat Pelaksana IGD', 'grade_level' => 'staff', 'default_basic_salary' => 5200000.00, 'default_positional_allowance' => 500000.00],

            ['unit_id' => $icu->id, 'title' => 'Kepala Ruang ICU', 'grade_level' => 'supervisor', 'default_basic_salary' => 7800000.00, 'default_positional_allowance' => 2000000.00],
            ['unit_id' => $icu->id, 'title' => 'Perawat Mahir ICU', 'grade_level' => 'staff', 'default_basic_salary' => 5500000.00, 'default_positional_allowance' => 600000.00],

            ['unit_id' => $farmasi->id, 'title' => 'Apoteker Penanggung Jawab', 'grade_level' => 'supervisor', 'default_basic_salary' => 7500000.00, 'default_positional_allowance' => 1800000.00],
            ['unit_id' => $farmasi->id, 'title' => 'Tenaga Teknis Kefarmasian', 'grade_level' => 'staff', 'default_basic_salary' => 5000000.00, 'default_positional_allowance' => 400000.00],

            ['unit_id' => $lab->id, 'title' => 'Kepala Laboratorium', 'grade_level' => 'supervisor', 'default_basic_salary' => 7500000.00, 'default_positional_allowance' => 1800000.00],
            ['unit_id' => $lab->id, 'title' => 'Pranata Laboratorium Kesehatan', 'grade_level' => 'staff', 'default_basic_salary' => 5000000.00, 'default_positional_allowance' => 400000.00],

            ['unit_id' => $rad->id, 'title' => 'Radiografer', 'grade_level' => 'staff', 'default_basic_salary' => 5200000.00, 'default_positional_allowance' => 450000.00],

            ['unit_id' => $sdm->id, 'title' => 'Kepala Bagian SDM', 'grade_level' => 'manager', 'default_basic_salary' => 9500000.00, 'default_positional_allowance' => 2500000.00],
            ['unit_id' => $sdm->id, 'title' => 'Staff Penggajian & HRIS', 'grade_level' => 'staff', 'default_basic_salary' => 5500000.00, 'default_positional_allowance' => 500000.00],
            ['unit_id' => $sdm->id, 'title' => 'Staff Rekrutmen & Diklat', 'grade_level' => 'staff', 'default_basic_salary' => 5200000.00, 'default_positional_allowance' => 400000.00],

            ['unit_id' => $it->id, 'title' => 'Kepala Unit SIMRS & TI', 'grade_level' => 'supervisor', 'default_basic_salary' => 8000000.00, 'default_positional_allowance' => 2000000.00],
            ['unit_id' => $it->id, 'title' => 'Staff Programmer SIMRS', 'grade_level' => 'staff', 'default_basic_salary' => 6000000.00, 'default_positional_allowance' => 500000.00],

            ['unit_id' => $kasir->id, 'title' => 'Staff Kasir Rumah Sakit', 'grade_level' => 'staff', 'default_basic_salary' => 4800000.00, 'default_positional_allowance' => 300000.00],
        ];

        foreach ($positions as $pos) {
            Position::updateOrCreate(
                ['unit_id' => $pos['unit_id'], 'title' => $pos['title']],
                [
                    'grade_level' => $pos['grade_level'],
                    'default_basic_salary' => $pos['default_basic_salary'],
                    'default_positional_allowance' => $pos['default_positional_allowance'],
                ]
            );
        }

        // Link Hierarchical Reporting Lines (reports_to_id)
        $reportingLines = [
            'Perawat Mahir ICU' => 'Kepala Ruang ICU',
            'Perawat Pelaksana IGD' => 'Kepala Instalasi Gawat Darurat',
            'Dokter Jaga IGD' => 'Kepala Instalasi Gawat Darurat',
            'Tenaga Teknis Kefarmasian' => 'Apoteker Penanggung Jawab',
            'Pranata Laboratorium Kesehatan' => 'Kepala Laboratorium',
            'Staff Penggajian & HRIS' => 'Kepala Bagian SDM',
            'Staff Rekrutmen & Diklat' => 'Kepala Bagian SDM',
            'Staff Programmer SIMRS' => 'Kepala Unit SIMRS & TI',
            'Staff Kasir Rumah Sakit' => 'Kepala Bagian SDM',
        ];

        foreach ($reportingLines as $subordinateTitle => $supervisorTitle) {
            $supervisorPos = Position::where('title', $supervisorTitle)->first();
            $subordinatePos = Position::where('title', $subordinateTitle)->first();

            if ($supervisorPos && $subordinatePos) {
                $subordinatePos->update(['reports_to_id' => $supervisorPos->id]);
            }
        }

        // 4. Public Holidays 2026
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

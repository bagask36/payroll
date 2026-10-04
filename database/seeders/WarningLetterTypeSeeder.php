<?php

namespace Database\Seeders;

use App\Models\WarningLetterType;
use Illuminate\Database\Seeder;

class WarningLetterTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'TEGURAN',
                'name' => 'Surat Teguran Tertulis',
                'level' => 0,
                'validity_months' => 3,
                'description' => 'Peringatan awal tertulis atas pelanggaran tata tertib dan disiplin kerja ringan.',
                'is_active' => true,
            ],
            [
                'code' => 'SP-1',
                'name' => 'Surat Peringatan Pertama (SP I)',
                'level' => 1,
                'validity_months' => 6,
                'description' => 'Sanksi atas pengulangan pelanggaran ringan atau pelanggaran tingkat pertama dengan masa berlaku 6 bulan.',
                'is_active' => true,
            ],
            [
                'code' => 'SP-2',
                'name' => 'Surat Peringatan Kedua (SP II)',
                'level' => 2,
                'validity_months' => 6,
                'description' => 'Sanksi atas pengulangan pelanggaran selama SP I masih berlaku atau pelanggaran disiplin tingkat sedang.',
                'is_active' => true,
            ],
            [
                'code' => 'SP-3',
                'name' => 'Surat Peringatan Ketiga / Terakhir (SP III)',
                'level' => 3,
                'validity_months' => 6,
                'description' => 'Peringatan keras tingkat akhir sebelum pemutusan hubungan kerja (PHK) atas pelanggaran berat.',
                'is_active' => true,
            ],
            [
                'code' => 'SKORSING',
                'name' => 'Skorsing / Pembebasan Tugas Sementara',
                'level' => 4,
                'validity_months' => 1,
                'description' => 'Pembebasan tugas sementara selama proses investigasi atau sanksi disiplin berat.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            WarningLetterType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}

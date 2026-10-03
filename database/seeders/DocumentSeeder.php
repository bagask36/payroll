<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first() ?? User::factory()->create();

        // 1. Employee KTP Document
        $nurse = Employee::where('nip', 'PEG-2025042')->first();
        if ($nurse) {
            Document::updateOrCreate(
                [
                    'documentable_type' => Employee::class,
                    'documentable_id' => $nurse->id,
                    'document_type' => 'ktp',
                ],
                [
                    'original_name' => 'KTP_Budi_Santoso.pdf',
                    'storage_path' => 'employees/ktp/ktp_budi_santoso.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 524288, // 512 KB
                    'storage_provider' => 'local',
                    'uploaded_by' => $nurse->user ? $nurse->user->id : $admin->id,
                ]
            );
        }

        // 2. Doctor SIP License Document
        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->first();
        if ($doctor) {
            Document::updateOrCreate(
                [
                    'documentable_type' => Doctor::class,
                    'documentable_id' => $doctor->id,
                    'document_type' => 'sip',
                ],
                [
                    'original_name' => 'SIP_dr_Hendra_Pratama.pdf',
                    'storage_path' => 'doctors/sip/sip_dr_hendra.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 1048576, // 1 MB
                    'storage_provider' => 'local',
                    'uploaded_by' => $admin->id,
                ]
            );
        }
    }
}

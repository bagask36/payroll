<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentable_type' => Employee::class,
            'documentable_id' => Employee::factory(),
            'document_type' => 'ktp',
            'original_name' => 'KTP_Scan.pdf',
            'storage_path' => 'employees/ktp/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(102400, 2097152),
            'storage_provider' => 'local',
            'drive_file_id' => null,
            'uploaded_by' => User::factory(),
        ];
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Master Data: Tingkat & Jenis Surat Peringatan (SP)
        Schema::create('warning_letter_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->unsignedSmallInteger('level')->default(1)->comment('1: SP I, 2: SP II, 3: SP III, dll.');
            $table->unsignedSmallInteger('validity_months')->default(6)->comment('Standar masa berlaku dalam bulan, default 6 bulan');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Transaksional: Surat Peringatan & Sanksi Karyawan
        Schema::create('employee_warning_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('warning_letter_type_id')->constrained('warning_letter_types')->restrictOnDelete();
            $table->string('letter_number', 100)->comment('Nomor dinas resmi surat peringatan');
            $table->date('issued_date')->comment('Tanggal penerbitan surat');
            $table->date('valid_from')->comment('Tanggal mulai berlaku sanksi');
            $table->date('valid_until')->comment('Tanggal akhir masa berlaku sanksi');
            $table->text('violation_reason')->comment('Uraian tindakan pelanggaran');
            $table->string('authorized_by', 150)->comment('Nama/jabatan pejabat penandatangan');
            $table->string('status', 30)->default('active')->comment('active, expired, revoked');
            $table->text('revocation_reason')->nullable()->comment('Alasan jika sanksi dicabut lebih awal');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_path')->nullable()->comment('File lampiran scan surat SP');
            $table->text('notes')->nullable()->comment('Catatan pembinaan / komitmen');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_warning_letters');
        Schema::dropIfExists('warning_letter_types');
    }
};

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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->string('nip', 30)->unique();
            $table->text('ktp_number'); // Encrypted NIK KTP
            $table->string('full_name', 150);
            $table->string('gender', 10);
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->string('marital_status', 20)->default('single');
            $table->unsignedSmallInteger('tax_dependents')->default(0);
            $table->string('npwp_number', 50)->nullable();
            $table->string('phone_number', 30);
            $table->text('residential_address');
            $table->text('face_biometric_vector')->nullable(); // Encrypted feature vector
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('contract_type', 20); // permanent, contract, probation, internship
            $table->string('contract_number', 100);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('basic_salary', 15, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('revision_reason', 255);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('bank_name', 50);
            $table->text('account_number'); // Encrypted account number
            $table->string('account_holder', 150);
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('employee_employments');
        Schema::dropIfExists('employees');
    }
};

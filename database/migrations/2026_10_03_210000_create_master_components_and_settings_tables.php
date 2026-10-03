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
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('type', 20); // allowance, deduction
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_fixed')->default(false);
            $table->decimal('default_amount', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pph21_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50); // ptkp, tarif_pasal_17, ter
            $table->string('code', 50);
            $table->string('name', 100);
            $table->decimal('rate', 6, 4)->nullable(); // e.g. 0.0500 for 5%
            $table->decimal('threshold_min', 15, 2)->nullable();
            $table->decimal('threshold_max', 15, 2)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general'); // general, attendance, payroll, security
            $table->string('key', 50)->unique();
            $table->text('value')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('pph21_parameters');
        Schema::dropIfExists('salary_components');
    }
};

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
        Schema::create('bpjs_rules', function (Blueprint $table) {
            $table->id();
            $table->string('program_name', 50); // kesehatan, jht, jp, jkk, jkm
            $table->decimal('employee_rate', 6, 4)->default(0.0000);
            $table->decimal('employer_rate', 6, 4)->default(0.0000);
            $table->decimal('wage_ceiling', 15, 2)->nullable();
            $table->decimal('wage_floor', 15, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('cutoff_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            $table->date('run_date');
            $table->string('status', 20)->default('draft'); // draft, calculated, finalized, paid, cancelled
            $table->decimal('total_gross_pay', 15, 2)->default(0.00);
            $table->decimal('total_deductions', 15, 2)->default(0.00);
            $table->decimal('total_net_pay', 15, 2)->default(0.00);
            $table->foreignId('processed_by')->constrained('users');
            $table->foreignId('finalized_by')->nullable()->constrained('users');
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('allowances', 15, 2)->default(0.00);
            $table->decimal('deductions', 15, 2)->default(0.00);
            $table->decimal('bpjs_tk_employee', 15, 2)->default(0.00);
            $table->decimal('bpjs_tk_employer', 15, 2)->default(0.00);
            $table->decimal('bpjs_kes_employee', 15, 2)->default(0.00);
            $table->decimal('bpjs_kes_employer', 15, 2)->default(0.00);
            $table->decimal('pph21_tax', 15, 2)->default(0.00);
            $table->decimal('net_salary', 15, 2);
            $table->string('payment_status', 20)->default('pending'); // pending, paid
            $table->string('bank_name', 50)->nullable();
            $table->text('bank_account_number')->nullable(); // encrypted
            $table->timestamps();

            $table->index(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained('payroll_items')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20); // allowance, deduction
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('bpjs_rules');
    }
};

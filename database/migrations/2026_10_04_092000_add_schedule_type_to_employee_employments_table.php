<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_employments', function (Blueprint $table) {
            $table->string('schedule_type', 20)->default('non_shift')->after('contract_type');
        });

        // Backfill schedule_type from employees table
        DB::statement("
            UPDATE employee_employments 
            SET schedule_type = COALESCE(employees.schedule_type, 'non_shift')
            FROM employees 
            WHERE employee_employments.employee_id = employees.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_employments', function (Blueprint $table) {
            $table->dropColumn('schedule_type');
        });
    }
};

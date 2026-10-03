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
            $table->foreignId('unit_id')->nullable()->after('employee_id')->constrained('units')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('unit_id')->constrained('positions')->nullOnDelete();
            $table->text('notes')->nullable()->after('is_active');
        });

        // Backfill existing employment records with employee's current unit and position
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                UPDATE employee_employments
                SET unit_id = (SELECT unit_id FROM employees WHERE employees.id = employee_employments.employee_id),
                    position_id = (SELECT position_id FROM employees WHERE employees.id = employee_employments.employee_id)
                WHERE unit_id IS NULL
            ');
        } else {
            DB::statement('
                UPDATE employee_employments ee
                SET unit_id = e.unit_id,
                    position_id = e.position_id
                FROM employees e
                WHERE ee.employee_id = e.id
                  AND ee.unit_id IS NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_employments', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['position_id']);
            $table->dropColumn(['unit_id', 'position_id', 'notes']);
        });
    }
};

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
        Schema::table('positions', function (Blueprint $table) {
            $table->decimal('default_basic_salary', 14, 2)->nullable()->after('grade_level');
            $table->decimal('default_positional_allowance', 14, 2)->nullable()->after('default_basic_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['default_basic_salary', 'default_positional_allowance']);
        });
    }
};

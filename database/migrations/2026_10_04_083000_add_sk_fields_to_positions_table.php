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
            $table->string('default_contract_number', 100)->nullable()->after('default_positional_allowance');
            $table->foreignId('document_id')->nullable()->after('default_contract_number')->constrained('documents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropColumn(['default_contract_number', 'document_id']);
        });
    }
};

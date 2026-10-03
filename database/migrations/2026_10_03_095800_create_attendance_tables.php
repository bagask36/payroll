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
        Schema::create('geofence_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_assignment_id')->nullable()->constrained('schedule_assignments')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->string('status', 20)->default('present'); // present, late, early_leave, absent, on_leave
            $table->unsignedInteger('work_duration_minutes')->nullable();
            $table->unsignedInteger('late_duration_minutes')->default(0);
            $table->timestamps();

            $table->index(['employee_id', 'date']);
            $table->index(['doctor_id', 'date']);
        });

        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->string('event_type', 20); // clock_in, clock_out
            $table->dateTime('recorded_at');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('distance_meters');
            $table->boolean('is_within_geofence')->default(true);
            $table->string('device_info', 255)->nullable();
            $table->boolean('face_matched')->default(false);
            $table->float('face_similarity')->nullable();
            $table->float('liveness_score')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->dateTime('original_clock_in')->nullable();
            $table->dateTime('adjusted_clock_in')->nullable();
            $table->dateTime('original_clock_out')->nullable();
            $table->dateTime('adjusted_clock_out')->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('geofence_locations');
    }
};

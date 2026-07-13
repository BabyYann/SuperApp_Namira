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
        // 1. Locations for Employee Attendance (Geofencing)
        Schema::create('attendance_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('name'); // e.g., "Gedung Utama", "Gedung Olahraga"
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius')->default(50); // in meters
            $table->timestamps();
        });

        // 2. Employee Attendance Logs (Guru & Staff)
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            
            // Check In
            $table->time('check_in_time')->nullable();
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->string('check_in_photo')->nullable(); // Path to selfie
            
            // Check Out
            $table->time('check_out_time')->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->string('check_out_photo')->nullable();

            $table->string('status')->default('absent'); // present, late, absent, permit, sick
            $table->text('note')->nullable();
            $table->timestamps();

            // Prevent duplicate logs for same user same day
            $table->unique(['user_id', 'date']);
        });

        // 3. Student Attendance Logs (Daily)
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete(); // Snapshot of class at that time
            $table->date('date');
            
            $table->enum('status', ['H', 'S', 'I', 'A'])->default('H'); // Hadir, Sakit, Izin, Alpha
            $table->text('note')->nullable();
            
            $table->foreignId('recorded_by')->constrained('users'); // Who input this? (Wali Kelas / Guru Piket)
            $table->timestamps();

            // One status per student per day
            $table->unique(['student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('employee_attendances');
        Schema::dropIfExists('attendance_locations');
    }
};

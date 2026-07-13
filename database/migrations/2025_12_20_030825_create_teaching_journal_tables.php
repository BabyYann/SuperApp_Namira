<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. The Journal Entry itself
        Schema::create('teaching_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete(); // Link to Teacher Profile
            $table->foreignId('class_schedule_id')->nullable()->constrained('class_schedules')->nullOnDelete(); // Optional link to schedule
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            
            $table->string('custom_theme')->nullable(); // Fallback topic if no TPs
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable(); // Evidence
            
            $table->enum('status', ['draft', 'submitted'])->default('submitted');
            $table->timestamps();
        });

        // 2. Pivot Table for TPs (Many-to-Many)
        Schema::create('journal_learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_journal_id')->constrained('teaching_journals')->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Attendance Record per Student
        Schema::create('journal_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_journal_id')->constrained('teaching_journals')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            // Enum: H=Hadir, S=Sakit, I=Izin, A=Alpha, T=Terlambat
            $table->enum('status', ['present', 'sick', 'permission', 'alpha', 'late'])->default('present');
            $table->string('note')->nullable(); // Optional note per student
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_attendance');
        Schema::dropIfExists('journal_learning_objectives');
        Schema::dropIfExists('teaching_journals');
    }
};

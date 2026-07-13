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
        // 1. LMS Classrooms (Virtual Classrooms)
        Schema::create('lms_classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });

        // 2. LMS Materials
        Schema::create('lms_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_classroom_id')->constrained('lms_classrooms')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // 3. LMS Material Files (Attachments)
        Schema::create('lms_material_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_material_id')->constrained('lms_materials')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->enum('file_type', ['pdf', 'word', 'ppt', 'image', 'youtube', 'link']);
            $table->timestamps();
        });

        // 4. LMS Assignments
        Schema::create('lms_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_classroom_id')->constrained('lms_classrooms')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_date');
            $table->integer('max_score')->default(100);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
        });

        // 5. LMS Submissions
        Schema::create('lms_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('submission_text')->nullable();
            $table->enum('status', ['submitted', 'late', 'missing', 'returned'])->default('submitted');
            $table->timestamp('submitted_at');
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
        });

        // 6. LMS Submission Files (Attachments from Students)
        Schema::create('lms_submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_submission_id')->constrained('lms_submissions')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->timestamps();
        });

        // 7. LMS Announcements
        Schema::create('lms_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lms_classroom_id')->constrained('lms_classrooms')->cascadeOnDelete();
            $table->text('content');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lms_announcements');
        Schema::dropIfExists('lms_submission_files');
        Schema::dropIfExists('lms_submissions');
        Schema::dropIfExists('lms_assignments');
        Schema::dropIfExists('lms_material_files');
        Schema::dropIfExists('lms_materials');
        Schema::dropIfExists('lms_classrooms');
    }
};

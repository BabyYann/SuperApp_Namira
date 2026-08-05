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
        Schema::create('job_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('applicant_code')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('last_education')->nullable(); // SMA, D3, S1, S2, S3
            $table->string('major')->nullable(); // Jurusan
            $table->string('institution')->nullable(); // Universitas/Sekolah
            $table->string('gpa')->nullable(); // IPK/Nilai
            $table->string('cv_path')->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('ktp_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->enum('selection_status', ['pending', 'shortlisted', 'interview', 'accepted', 'rejected'])->default('pending');
            $table->text('selection_notes')->nullable();
            $table->foreignId('converted_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applicants');
    }
};

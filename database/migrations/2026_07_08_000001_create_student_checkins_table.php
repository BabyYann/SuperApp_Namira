<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_checkins')) {
            return;
        }

        Schema::create('student_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('scanned_by')->nullable()->constrained('users')->onDelete('set null'); // Guru piket
            $table->date('checkin_date');
            $table->time('checkin_time');
            $table->enum('status', ['hadir', 'terlambat'])->default('hadir');
            $table->string('notes')->nullable();
            $table->timestamps();

            // Unique: satu siswa, satu check-in per hari per tahun ajaran
            $table->unique(['student_id', 'checkin_date', 'academic_year_id'], 'unique_student_checkin_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_checkins');
    }
};

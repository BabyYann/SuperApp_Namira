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
        Schema::create('class_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('from_classroom_id')->constrained('classrooms');
            $table->foreignId('to_classroom_id')->nullable()->constrained('classrooms'); // Null for graduated/left
            $table->foreignId('from_academic_year_id')->constrained('academic_years');
            $table->foreignId('to_academic_year_id')->constrained('academic_years');
            $table->enum('status', ['naik', 'tinggal', 'lulus', 'pindah', 'keluar'])->default('naik');
            $table->text('notes')->nullable();
            $table->foreignId('promoted_by')->constrained('users');
            $table->timestamp('promoted_at');
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['from_academic_year_id', 'to_academic_year_id']);
            $table->index(['student_id', 'from_academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_promotions');
    }
};

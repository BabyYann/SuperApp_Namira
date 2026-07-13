<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration makes classrooms permanent (not tied to academic year)
     * and moves academic year tracking to the students table.
     */
    public function up(): void
    {
        // Step 1: Add academic_year_id to students table
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('classroom_id')
                ->constrained('academic_years')
                ->nullOnDelete();
        });

        // Step 2: Migrate existing data - set student's academic_year_id from their classroom
        DB::statement('
            UPDATE students 
            SET academic_year_id = (
                SELECT academic_year_id 
                FROM classrooms 
                WHERE classrooms.id = students.classroom_id
            )
            WHERE classroom_id IS NOT NULL
        ');

        // Step 3: Remove academic_year_id from classrooms table
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add academic_year_id to classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('academic_years')
                ->nullOnDelete();
        });

        // Remove academic_year_id from students
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};

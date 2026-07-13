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
        Schema::table('class_schedules', function (Blueprint $table) {
            // 1. Make nullable
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
            
            // 2. Drop existing foreign if exists (try-catch implicit via Schema builder usually needs explicit name, but we'll try standard syntax)
            // Assuming name is class_schedules_teacher_id_foreign
            $table->dropForeign(['teacher_id']);

            // 3. Add robust constraint
            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('teachers')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
            // Re-add weak constraint or none
        });
    }
};

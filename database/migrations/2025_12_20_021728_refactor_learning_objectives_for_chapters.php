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
        Schema::table('learning_objectives', function (Blueprint $table) {
            $table->foreignId('chapter_id')->after('unit_id')->nullable()->constrained('chapters')->cascadeOnDelete(); // Nullable first for migration safety, but practically required
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['subject_id', 'semester', 'chapter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_objectives', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropColumn('chapter_id');
            // Re-adding columns in down is complex due to data loss, simplified for now
            $table->foreignId('subject_id')->nullable()->constrained('subjects');
            $table->enum('semester', ['1', '2'])->default('1');
            $table->string('chapter')->nullable();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find active academic year ID, or fallback to the latest academic year
        $activeYearId = DB::table('academic_years')
            ->where('is_active', true)
            ->value('id');

        if (!$activeYearId) {
            $activeYearId = DB::table('academic_years')
                ->orderBy('id', 'desc')
                ->value('id');
        }

        if ($activeYearId) {
            // 1. Update all students where academic_year_id IS NULL
            DB::table('students')
                ->whereNull('academic_year_id')
                ->update(['academic_year_id' => $activeYearId]);

            // 2. Update all students who are currently placed in a classroom
            // Since classrooms are permanent, students currently in classrooms
            // belong to the active academic year.
            DB::table('students')
                ->whereNotNull('classroom_id')
                ->update(['academic_year_id' => $activeYearId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed as this is a data synchronization migration
    }
};

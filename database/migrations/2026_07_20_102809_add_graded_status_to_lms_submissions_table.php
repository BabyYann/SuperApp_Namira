<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE lms_submissions MODIFY COLUMN status ENUM('submitted','late','missing','returned','graded') DEFAULT 'submitted'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE lms_submissions MODIFY COLUMN status ENUM('submitted','late','missing','returned') DEFAULT 'submitted'");
    }
};

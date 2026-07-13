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
        Schema::table('units', function (Blueprint $table) {
             // We use DB statement because changing enum can be tricky with Schema builder depending on drivers
             // Converting 'category' from ENUM to VARCHAR to allow 'SD', 'SMP', etc.
        });

        if (\DB::getDriverName() !== 'sqlite') {
            \DB::statement("ALTER TABLE units MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'Lainnya'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Revert back to enum not easily possible without data loss if new values exist
            // For now, we leave it as string in down or try to revert if strictly needed
        });
    }
};

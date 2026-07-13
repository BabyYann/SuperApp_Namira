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
            $table->time('work_start_time')->default('07:00:00')->after('level');
            $table->time('work_end_time')->default('16:00:00')->after('work_start_time');
            $table->integer('late_tolerance_minutes')->default(15)->after('work_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['work_start_time', 'work_end_time', 'late_tolerance_minutes']);
        });
    }
};

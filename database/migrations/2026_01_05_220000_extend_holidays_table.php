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
        Schema::table('holidays', function (Blueprint $table) {
            $table->enum('event_type', ['libur', 'ujian', 'event', 'rapat'])->default('libur')->after('description');
            $table->string('color', 7)->nullable()->after('event_type'); // Hex color
            $table->time('start_time')->nullable()->after('color');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn(['event_type', 'color', 'start_time', 'end_time']);
        });
    }
};

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
        Schema::table('class_promotions', function (Blueprint $table) {
            $table->text('validation_summary')->nullable()->after('notes');
            $table->boolean('is_rolled_back')->default(false)->after('validation_summary');
            $table->foreignId('rolled_back_by')->nullable()->after('is_rolled_back')->constrained('users');
            $table->timestamp('rolled_back_at')->nullable()->after('rolled_back_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_promotions', function (Blueprint $table) {
            $table->dropForeign(['rolled_back_by']);
            $table->dropColumn(['validation_summary', 'is_rolled_back', 'rolled_back_by', 'rolled_back_at']);
        });
    }
};

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
        Schema::table('sarpar_inventories', function (Blueprint $table) {
            // Item type: asset (tetap) or consumable (habis pakai)
            $table->enum('item_type', ['asset', 'consumable'])->default('asset')->after('funding_source');
            
            // Link to classroom (optional, for items in classrooms)
            $table->foreignId('classroom_id')->nullable()->after('room_id')->constrained('classrooms')->nullOnDelete();
            
            // Minimum stock level (for consumables)
            $table->integer('min_stock')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sarpar_inventories', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropColumn(['item_type', 'classroom_id', 'min_stock']);
        });
    }
};

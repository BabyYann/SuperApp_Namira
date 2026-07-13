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
        Schema::create('sarpar_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('sarpar_inventories')->cascadeOnDelete();
            $table->foreignId('used_by')->constrained('users');
            
            $table->integer('quantity_used');
            $table->date('used_date');
            $table->string('purpose')->nullable(); // Tujuan penggunaan
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpar_usage_logs');
    }
};

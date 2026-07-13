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
        Schema::create('sarpar_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('sarpar_inventories')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users');  // Guru/Koordinator
            $table->foreignId('handled_by')->nullable()->constrained('users'); // Koordinator
            
            $table->text('issue');                    // Masalah yang dilaporkan
            $table->text('action_taken')->nullable(); // Tindakan perbaikan
            $table->bigInteger('cost')->nullable();   // Biaya perbaikan
            $table->date('reported_date');
            $table->date('resolved_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'cancelled'])->default('pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpar_maintenance_logs');
    }
};

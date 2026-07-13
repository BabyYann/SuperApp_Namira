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
        Schema::create('sarpar_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('sarpar_inventories')->cascadeOnDelete();
            $table->foreignId('borrower_id')->constrained('users'); // Guru
            $table->foreignId('processed_by')->constrained('users'); // Koordinator
            
            $table->integer('quantity')->default(1);
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['borrowed', 'returned', 'overdue', 'lost'])->default('borrowed');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpar_loans');
    }
};

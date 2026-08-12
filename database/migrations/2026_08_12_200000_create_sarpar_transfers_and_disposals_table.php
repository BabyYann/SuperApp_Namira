<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sarpar_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('sarpar_inventories')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('to_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('from_room_id')->nullable()->constrained('sarpar_rooms')->nullOnDelete();
            $table->foreignId('to_room_id')->nullable()->constrained('sarpar_rooms')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('transferred_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('sarpar_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('sarpar_inventories')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->enum('disposal_type', ['rusak_berat', 'hilang', 'dijual', 'hibah'])->default('rusak_berat');
            $table->text('reason');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sarpar_disposals');
        Schema::dropIfExists('sarpar_transfers');
    }
};

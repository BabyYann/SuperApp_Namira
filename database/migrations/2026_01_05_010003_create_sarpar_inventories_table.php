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
        Schema::create('sarpar_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('sarpar_categories')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('sarpar_rooms')->nullOnDelete();
            
            // Kode & Sumber Dana
            $table->enum('funding_source', ['BOS', 'YYS'])->default('YYS'); // BOS / Yayasan
            $table->string('code')->unique();  // BOS-SD01-ELK-2024-001
            
            // Detail Barang
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->year('year_acquired');
            $table->integer('quantity')->default(1);
            $table->bigInteger('unit_price')->nullable();
            
            // Status & Kondisi
            $table->enum('condition', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->enum('status', ['tersedia', 'dipinjam', 'diperbaiki', 'dihapus'])->default('tersedia');
            
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpar_inventories');
    }
};

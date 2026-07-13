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
        // 1. Master Data: Kategori Pelanggaran
        Schema::create('violation_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Terlambat", "Merokok"
            $table->enum('type', ['ringan', 'sedang', 'berat'])->default('ringan');
            $table->integer('default_points')->default(0); // e.g. 5, 10, 100
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Transaksi: Pelanggaran Siswa
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('violation_category_id')->nullable()->constrained()->nullOnDelete();
            
            $table->date('date');
            $table->integer('points'); // Stores the actual points applied (allow override)
            $table->text('description')->nullable();
            $table->string('photo_proof')->nullable(); // Path to evidence image
            
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete(); // Guru who reported
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // BK who approved (if needed)
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Transaksi: Prestasi Siswa
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            
            $table->string('title'); // e.g. "Juara 1 Lomba Pidato"
            $table->enum('level', ['sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional']);
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('proof_file')->nullable(); // Certificate/Photo
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Transaksi: Sesi Konseling (Private)
        Schema::create('counseling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete(); // Guru BK
            
            $table->date('date');
            $table->time('time')->nullable();
            $table->enum('category', ['akademik', 'pribadi', 'sosial', 'karir', 'kedisiplinan'])->default('pribadi');
            $table->text('problem_description');
            $table->text('result_notes')->nullable();
            $table->string('attachment')->nullable(); // Optional docs
            $table->enum('status', ['scheduled', 'done', 'canceled', 'follow_up'])->default('scheduled');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_sessions');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('violations');
        Schema::dropIfExists('violation_categories');
    }
};

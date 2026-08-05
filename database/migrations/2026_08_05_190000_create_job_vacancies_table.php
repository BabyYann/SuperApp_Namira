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
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('teacher'); // teacher, staff, operational, other
            $table->string('type')->default('full_time'); // full_time, part_time, contract
            $table->integer('quota')->default(1);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->date('deadline')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedInteger('views_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_vacancies');
    }
};

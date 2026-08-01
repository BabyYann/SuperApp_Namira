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
        Schema::table('student_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('student_bills', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('status');
            }
            if (!Schema::hasColumn('student_bills', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('payment_proof');
            }
            if (!Schema::hasColumn('student_bills', 'proof_uploaded_at')) {
                $table->timestamp('proof_uploaded_at')->nullable()->after('payment_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_bills', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'payment_notes', 'proof_uploaded_at']);
        });
    }
};

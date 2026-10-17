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
        Schema::table('spmb_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('spmb_applicants', 'settlement_payment_proof')) {
                $table->string('settlement_payment_proof')->nullable()->after('re_registration_payment_proof')
                    ->comment('Bukti bayar pelunasan daftar ulang (Termin 2)');
            }
            if (!Schema::hasColumn('spmb_applicants', 'settlement_payment_verified_at')) {
                $table->timestamp('settlement_payment_verified_at')->nullable()->after('admission_payment_verified_at')
                    ->comment('Waktu verifikasi bukti pelunasan daftar ulang');
            }
            if (!Schema::hasColumn('spmb_applicants', 'settlement_payment_verified_by')) {
                $table->foreignId('settlement_payment_verified_by')->nullable()->after('admission_payment_verified_by')
                    ->constrained('users')->onDelete('set null')
                    ->comment('User panitia/keuangan pemverifikasi bukti pelunasan');
            }
            if (!Schema::hasColumn('spmb_applicants', 'payment_history')) {
                $table->json('payment_history')->nullable()->after('settlement_payment_verified_by')
                    ->comment('Riwayat transaksi pembayaran bertahap (Termin 1, Termin 2, dsb)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spmb_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('spmb_applicants', 'payment_history')) {
                $table->dropColumn('payment_history');
            }
            if (Schema::hasColumn('spmb_applicants', 'settlement_payment_verified_by')) {
                $table->dropForeign(['settlement_payment_verified_by']);
                $table->dropColumn('settlement_payment_verified_by');
            }
            if (Schema::hasColumn('spmb_applicants', 'settlement_payment_verified_at')) {
                $table->dropColumn('settlement_payment_verified_at');
            }
            if (Schema::hasColumn('spmb_applicants', 'settlement_payment_proof')) {
                $table->dropColumn('settlement_payment_proof');
            }
        });
    }
};

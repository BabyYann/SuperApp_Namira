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
        Schema::create('spmb_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->string('name')->default('Pendaftaran Jalur Inden');
            $table->string('academic_year')->default('2026/2027');
            $table->integer('quota')->default(60);
            $table->decimal('registration_fee', 12, 2)->default(250000);
            $table->string('qris_image_path')->nullable();
            $table->string('bank_name')->default('Bank Jatim');
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->decimal('admission_fee_total', 12, 2)->nullable();
            $table->integer('min_down_payment_percentage')->default(60);
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('contact_whatsapp')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('spmb_applicants', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('category')->default('eksternal_umum'); // internal_tk, eksternal_umum
            $table->string('status')->default('submitted'); // submitted, verified, scheduled, evaluated, accepted, reserve, rejected, partial_paid, fully_paid, enrolled, cancelled

            // Data Calon Siswa
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('gender', 5)->default('L'); // L / P
            $table->string('nik', 20)->nullable();
            $table->string('nisn', 20)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->default('Islam');
            $table->integer('child_order')->nullable();
            $table->integer('total_siblings')->nullable();
            $table->string('previous_school')->nullable();
            $table->text('special_notes')->nullable();

            // Data Orang Tua / Wali
            $table->string('father_name')->nullable();
            $table->string('father_nik', 20)->nullable();
            $table->string('father_phone', 25)->nullable();
            $table->string('father_job')->nullable();
            $table->string('father_education')->nullable();

            $table->string('mother_name')->nullable();
            $table->string('mother_nik', 20)->nullable();
            $table->string('mother_phone', 25)->nullable();
            $table->string('mother_job')->nullable();
            $table->string('mother_education')->nullable();

            $table->string('parent_phone', 25); // No WhatsApp utama akun
            $table->text('address')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();

            // Berkas Dokumen Persyaratan
            $table->string('photo_path')->nullable(); // Pas foto latar merah
            $table->string('family_card_path')->nullable(); // Kartu Keluarga
            $table->string('birth_cert_path')->nullable(); // Akta Kelahiran
            $table->string('parent_id_card_path')->nullable(); // KTP Ortu
            
            // Bukti Pembayaran QRIS Pendaftaran
            $table->string('registration_payment_proof')->nullable();
            $table->timestamp('registration_payment_verified_at')->nullable();
            $table->foreignId('registration_payment_verified_by')->nullable()->constrained('users')->onDelete('set null');

            // Jadwal Kegiatan Offline (Observasi Dasar & Psikotes)
            $table->date('observation_date')->nullable();
            $table->string('observation_time')->nullable();
            $table->string('observation_location')->nullable();
            $table->date('psychotest_date')->nullable();
            $table->string('psychotest_time')->nullable();
            $table->string('psychotest_location')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->onDelete('set null');

            // Hasil Observasi & Evaluasi
            $table->string('evaluation_result')->nullable(); // accepted, reserve, rejected
            $table->text('evaluation_notes')->nullable();
            $table->string('evaluation_document_path')->nullable(); // PDF Rapor / Hasil Observasi
            $table->timestamp('evaluated_at')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');

            // Daftar Ulang (Virtual Account Bank Jatim)
            $table->string('virtual_account_number')->nullable();
            $table->decimal('total_admission_fee', 12, 2)->nullable();
            $table->decimal('min_down_payment', 12, 2)->nullable();
            $table->decimal('paid_admission_amount', 12, 2)->default(0);
            $table->date('down_payment_deadline')->nullable();
            $table->date('full_payment_deadline')->nullable();
            $table->string('re_registration_payment_proof')->nullable();
            $table->string('admission_payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->timestamp('admission_payment_verified_at')->nullable();
            $table->foreignId('admission_payment_verified_by')->nullable()->constrained('users')->onDelete('set null');

            // Konversi ke Data Siswa Aktif Akademik
            $table->foreignId('enrolled_student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->timestamp('enrolled_at')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spmb_applicants');
        Schema::dropIfExists('spmb_settings');
    }
};

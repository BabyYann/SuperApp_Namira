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
        Schema::table('spmb_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('spmb_settings', 'selection_date')) {
                $table->date('selection_date')->nullable()->after('end_date')
                    ->comment('Tanggal pelaksanaan seleksi akademik & observasi');
            }
            if (!Schema::hasColumn('spmb_settings', 'announcement_date')) {
                $table->date('announcement_date')->nullable()->after('selection_date')
                    ->comment('Tanggal jadwal publikasi pengumuman kelulusan');
            }
            if (!Schema::hasColumn('spmb_settings', 'announcement_released')) {
                $table->boolean('announcement_released')->default(false)->after('announcement_date')
                    ->comment('Flag rilis manual pengumuman oleh panitia');
            }
        });

        Schema::table('spmb_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('spmb_applicants', 'is_inden')) {
                $table->boolean('is_inden')->default(false)->after('category')
                    ->comment('Penanda pendaftar dari data inden');
            }
            if (!Schema::hasColumn('spmb_applicants', 'academic_score_details')) {
                $table->json('academic_score_details')->nullable()->after('evaluation_notes')
                    ->comment('Rincian nilai ujian: Bahasa Indonesia, Matematika, Wawancara, Mengaji');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spmb_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('spmb_applicants', 'academic_score_details')) {
                $table->dropColumn('academic_score_details');
            }
            if (Schema::hasColumn('spmb_applicants', 'is_inden')) {
                $table->dropColumn('is_inden');
            }
        });

        Schema::table('spmb_settings', function (Blueprint $table) {
            if (Schema::hasColumn('spmb_settings', 'announcement_released')) {
                $table->dropColumn('announcement_released');
            }
            if (Schema::hasColumn('spmb_settings', 'announcement_date')) {
                $table->dropColumn('announcement_date');
            }
            if (Schema::hasColumn('spmb_settings', 'selection_date')) {
                $table->dropColumn('selection_date');
            }
        });
    }
};

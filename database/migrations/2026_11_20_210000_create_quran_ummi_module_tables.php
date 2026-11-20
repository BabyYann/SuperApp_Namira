<?php

use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\SystemSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Jilid / Tingkat Program Al-Qur'an (Ummi) per Unit
        Schema::create('quran_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['unit_id', 'name'], 'quran_levels_unit_name_unique');
        });

        // 2. Master Rubrik Materi & Hafalan per Jilid
        Schema::create('quran_rubric_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('quran_levels')->cascadeOnDelete();
            $table->string('category', 30); // materi, surah_pendek, doa_harian, hadist
            $table->string('title', 255);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['level_id', 'category', 'sort_order'], 'quran_rubrics_level_cat_idx');
        });

        // 3. Master Sesi Waktu Mengaji per Unit
        Schema::create('quran_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('name', 100); // e.g. Sesi 1 (Pagi)
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->string('days', 100)->nullable(); // e.g. Senin - Jumat
            $table->string('target_note', 150)->nullable(); // e.g. Kelas 1 & 2
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Kelompok / Kelas Mengaji (Halaqah)
        Schema::create('quran_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('level_id')->constrained('quran_levels')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('quran_sessions')->nullOnDelete();
            $table->string('name', 120);
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assistant_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room_name', 100)->nullable();
            $table->unsignedInteger('max_quota')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['unit_id', 'academic_year_id', 'level_id'], 'quran_groups_unit_year_level_idx');
        });

        // 5. Penempatan Siswa di Kelompok Mengaji
        Schema::create('quran_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('quran_groups')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('quran_levels')->cascadeOnDelete();
            $table->string('status', 20)->default('active'); // active, promoted, transferred, completed
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'student_id', 'status'], 'quran_enrollments_student_status_idx');
            $table->index(['group_id', 'status'], 'quran_enrollments_group_status_idx');
        });

        // 6. Riwayat Tracking Kenaikan Jilid & Perpindahan Kelompok
        Schema::create('quran_level_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('semester', 20)->default('ganjil');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_level_id')->nullable()->constrained('quran_levels')->nullOnDelete();
            $table->foreignId('to_level_id')->constrained('quran_levels')->cascadeOnDelete();
            $table->foreignId('from_group_id')->nullable()->constrained('quran_groups')->nullOnDelete();
            $table->foreignId('to_group_id')->nullable()->constrained('quran_groups')->nullOnDelete();
            $table->foreignId('from_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 20)->default('promoted'); // initial, promoted, transferred
            $table->date('changed_at');
            $table->string('exam_score', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'changed_at'], 'quran_histories_student_date_idx');
        });

        // 7. Penilaian Rapor Al-Qur'an per Semester (Snapshot Jilid Terakhir Semester)
        Schema::create('quran_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('semester', 20)->default('ganjil');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('quran_levels')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('quran_groups')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('scores')->nullable();
            $table->text('teacher_note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'student_id', 'academic_year_id', 'semester'], 'quran_assessments_unique_sem');
        });

        // 8. Pengaturan Rapor & Kunci Periode per Unit
        Schema::create('quran_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->unique()->constrained('units')->cascadeOnDelete();
            $table->string('school_name')->nullable();
            $table->string('school_address')->nullable();
            $table->string('city_name', 100)->nullable();
            $table->string('kepala_sekolah')->nullable();
            $table->string('kepala_tpq')->nullable();
            $table->string('kepala_tpq_title')->nullable();
            $table->longText('ttd_kepala_tpq')->nullable();
            $table->longText('ttd_kepala_sekolah')->nullable();
            $table->json('ttd_config')->nullable();
            $table->boolean('lock_input_nilai')->default(false);
            $table->boolean('lock_cetak_rapor')->default(false);
            $table->boolean('allow_teacher_promote')->default(true);
            $table->date('report_date')->nullable();
            $table->timestamps();
        });

        // 9. Register Roles & Feature Flag
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'koordinator_alquran', 'guard_name' => 'web']);

        if (Schema::hasTable('system_settings')) {
            SystemSetting::updateOrCreate(
                ['key' => 'feature_quran'],
                ['key' => 'feature_quran', 'value' => '1', 'type' => 'boolean', 'group' => 'features']
            );
        }

        // 10. Seed default Ummi levels, rubrics, and settings for all non-Daycare/non-Pavlov school units
        if (Schema::hasTable('units')) {
            $setup = new QuranSetupService();
            Unit::all()->each(function (Unit $unit) use ($setup) {
                if (!$unit->isDaycare() && strtoupper((string) $unit->code) !== 'YAYASAN') {
                    $setup->ensureUnitDefaults($unit);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_settings');
        Schema::dropIfExists('quran_assessments');
        Schema::dropIfExists('quran_level_histories');
        Schema::dropIfExists('quran_enrollments');
        Schema::dropIfExists('quran_groups');
        Schema::dropIfExists('quran_sessions');
        Schema::dropIfExists('quran_rubric_items');
        Schema::dropIfExists('quran_levels');

        Role::whereIn('name', ['guru_alquran', 'koordinator_alquran'])->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

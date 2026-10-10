<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Modules\Yayasan\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Master Ekstrakurikuler
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('name');
            $table->string('category')->default('olahraga'); // olahraga, seni_budaya, keagamaan, sains_teknologi, kepanduan, lainnya
            $table->string('day_of_week')->nullable(); // Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu
            $table->string('start_time', 10)->nullable(); // e.g. "14:30"
            $table->string('end_time', 10)->nullable();   // e.g. "16:00"
            $table->foreignId('room_id')->nullable()->constrained('sarpar_rooms')->nullOnDelete();
            $table->string('location_name')->nullable();
            $table->integer('max_quota')->default(30);
            $table->boolean('is_mandatory')->default(false);
            $table->string('gender_restriction')->default('all'); // all, male_only, female_only
            $table->json('target_levels')->nullable(); // ["1","2","3","4","5","6"] or null
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Pembina & Pelatih Ekskul
        Schema::create('extracurricular_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_title')->default('Pelatih Utama'); // Pelatih Utama, Asisten Pelatih, Pembina Pendamping
            $table->boolean('is_primary')->default(true);
            $table->string('phone')->nullable();
            $table->string('institution')->nullable(); // Asal klub/sanggar/internal
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['extracurricular_id', 'user_id']);
        });

        // 3. Data Anggota Siswa (Members per Academic Year & Semester)
        Schema::create('extracurricular_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('semester')->default('ganjil'); // ganjil, genap
            $table->date('joined_date')->nullable();
            $table->string('status')->default('active'); // active, inactive, dropped
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['extracurricular_id', 'student_id', 'academic_year_id', 'semester'], 'ex_member_unique');
        });

        // 4. Sesi Kegiatan Latihan (Pertemuan / Jurnal Latihan)
        Schema::create('extracurricular_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->string('topic');
            $table->text('notes')->nullable();
            $table->text('photo_paths')->nullable(); // JSON or delimited paths
            $table->string('status')->default('completed'); // completed, scheduled, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Presensi Kehadiran Siswa per Sesi
        Schema::create('extracurricular_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('extracurricular_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status')->default('hadir'); // hadir, izin, sakit, alpha
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
        });

        // 6. Penilaian Rapor Ekskul Akhir Semester
        Schema::create('extracurricular_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('semester')->default('ganjil');
            $table->string('predicate')->default('A'); // A / Sangat Baik, B / Baik, C / Cukup, D / Kurang
            $table->text('description'); // Narasi capaian kurikulum
            $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['extracurricular_id', 'student_id', 'academic_year_id', 'semester'], 'ex_grade_unique');
        });

        // 7. Register Role & System Feature Setting
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'pelatih_ekskul', 'guard_name' => 'web']);
        
        SystemSetting::updateOrCreate(
            ['key' => 'feature_extracurricular'],
            ['key' => 'feature_extracurricular', 'value' => '1', 'type' => 'boolean', 'group' => 'features']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracurricular_grades');
        Schema::dropIfExists('extracurricular_attendances');
        Schema::dropIfExists('extracurricular_sessions');
        Schema::dropIfExists('extracurricular_members');
        Schema::dropIfExists('extracurricular_instructors');
        Schema::dropIfExists('extracurriculars');

        Role::where('name', 'pelatih_ekskul')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

<?php

use App\Models\User;
use App\Modules\Quran\Models\QuranAssessment;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add scope ('level'|'grade') and grade_level ('1'..'6') to quran_rubric_items, make level_id nullable
        Schema::table('quran_rubric_items', function (Blueprint $table) {
            if (!Schema::hasColumn('quran_rubric_items', 'scope')) {
                $table->string('scope', 20)->default('level')->after('unit_id');
            }
            if (!Schema::hasColumn('quran_rubric_items', 'grade_level')) {
                $table->string('grade_level', 20)->nullable()->after('level_id');
            }
        });

        // Make level_id nullable cleanly across MySQL & SQLite
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('quran_rubric_items', function (Blueprint $table) {
                $table->dropForeign(['level_id']);
            });
            Schema::table('quran_rubric_items', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->change();
                $table->foreign('level_id')->references('id')->on('quran_levels')->cascadeOnDelete();
            });
        } else {
            Schema::table('quran_rubric_items', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->change();
            });
        }

        Schema::table('quran_rubric_items', function (Blueprint $table) {
            $table->index(['unit_id', 'scope', 'grade_level', 'category'], 'quran_rubrics_grade_scope_idx');
        });

        // 2. Reorder Tajwid (8) -> Ghorib (9) -> Munaqosyah (10) -> Tahfidz (11) & seed SD grade-scoped rubrics
        $setup = new QuranSetupService();
        $units = Unit::all();

        foreach ($units as $unit) {
            if ($unit->isDaycare() || strtoupper((string) $unit->code) === 'YAYASAN') {
                continue;
            }

            $hasLevels = QuranLevel::where('unit_id', $unit->id)->exists();
            if (!$hasLevels) {
                continue;
            }

            // Update ordering: Tajwid=8, Ghorib=9, Tahfidz=11
            QuranLevel::where('unit_id', $unit->id)->where('name', 'Tajwid')->update(['sort_order' => 8]);
            QuranLevel::where('unit_id', $unit->id)->where('name', 'Ghorib')->update(['sort_order' => 9]);
            QuranLevel::where('unit_id', $unit->id)->where('name', 'Tahfidz')->update(['sort_order' => 11]);

            // Add Munaqosyah (sort_order = 10) if missing
            $munaqosyah = QuranLevel::firstOrCreate(
                ['unit_id' => $unit->id, 'name' => 'Munaqosyah'],
                ['code' => 'MQ', 'sort_order' => 10, 'is_active' => true]
            );
            if ($munaqosyah->sort_order !== 10) {
                $munaqosyah->update(['sort_order' => 10]);
            }

            // Seed default Munaqosyah rubrics if empty
            if (!QuranRubricItem::where('level_id', $munaqosyah->id)->exists()) {
                foreach (['Fashohah', 'Tartil', 'Tajwid', 'Ghorib'] as $idx => $title) {
                    QuranRubricItem::create([
                        'unit_id' => $unit->id,
                        'scope' => QuranRubricItem::SCOPE_LEVEL,
                        'level_id' => $munaqosyah->id,
                        'grade_level' => null,
                        'category' => QuranRubricItem::CATEGORY_MATERI,
                        'title' => $title,
                        'sort_order' => $idx + 1,
                        'is_active' => true,
                    ]);
                }
                QuranRubricItem::create([
                    'unit_id' => $unit->id,
                    'scope' => QuranRubricItem::SCOPE_LEVEL,
                    'level_id' => $munaqosyah->id,
                    'grade_level' => null,
                    'category' => QuranRubricItem::CATEGORY_SURAH,
                    'title' => 'Juz 30',
                    'sort_order' => 1,
                    'is_active' => true,
                ]);
            }

            // For SD units: switch Hadist & Doa Harian to grade-scoped (Kelas 1 - 6)
            if ($setup->isSdUnit($unit)) {
                $hasAssessments = QuranAssessment::where('unit_id', $unit->id)->exists();
                if (!$hasAssessments) {
                    QuranRubricItem::where('unit_id', $unit->id)
                        ->where('scope', QuranRubricItem::SCOPE_LEVEL)
                        ->whereIn('category', [QuranRubricItem::CATEGORY_DOA, QuranRubricItem::CATEGORY_HADIST])
                        ->delete();
                }

                $setup->seedSdGradeRubrics($unit);
            }
        }

        // 3. Carefully migrate the 11 pure Ummi teachers in SD Namira (code = 'SD') from 'teacher' -> 'guru_alquran'
        //    and assign 'koordinator_alquran' to Nur Halimah, while preserving other roles (humas_unit, panitia_spmb, etc.)
        $sdUnit = Unit::where('code', 'SD')->first();
        if ($sdUnit) {
            $teacherRole = Role::where('name', 'teacher')->where('guard_name', 'web')->first();
            $guruQuranRole = Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
            $koordQuranRole = Role::firstOrCreate(['name' => 'koordinator_alquran', 'guard_name' => 'web']);

            $pureUmmiEmails = [
                'hnurhalimah091@gmail.com',       // 1. Nur Halimah (Koordinator + Pengampu)
                'jannahrehanatil@gmail.com',      // 2. Rehanatil Jannah
                'nurainifriscaananda@gmail.com',  // 3. Nur Aini Trischa Ananda
                'novitaagustinningsih16@gmail.com', // 4. Novita Agustinningsih
                'syarifahaminatul@gmail.com',     // 5. Siti Aminatul Qomariyah
                'hj@namira.school',               // 6. Hj. Muthmainnah
                'syarifsya726@gmail.com',         // 7. Sarif
                'ivamutmainah.1507@gmail.com',    // 8. Iva Mutma'inah S.Pd
                'meirindazm@gmail.com',           // 9. Meirinda Zahratul M. S.Pd (keeps humas_unit)
                'putriagustini1303@gmail.com',    // 10. Putri Agustini S.Sos (keeps panitia_spmb)
                'helmimufida45@gmail.com',        // 11. Helmi Mufida
            ];

            $ummiUsers = User::whereIn('email', $pureUmmiEmails)->get();
            foreach ($ummiUsers as $u) {
                // Remove ONLY 'teacher' role on SD unit
                if ($teacherRole) {
                    DB::table('model_has_roles')
                        ->where('role_id', $teacherRole->id)
                        ->where('model_type', User::class)
                        ->where('model_id', $u->id)
                        ->where('team_id', $sdUnit->id)
                        ->delete();
                }

                // Assign 'guru_alquran' on SD unit
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $guruQuranRole->id,
                    'model_type' => User::class,
                    'model_id' => $u->id,
                    'team_id' => $sdUnit->id,
                ], []);

                // Assign 'koordinator_alquran' to Nur Halimah
                if (strtolower($u->email) === 'hnurhalimah091@gmail.com') {
                    DB::table('model_has_roles')->updateOrInsert([
                        'role_id' => $koordQuranRole->id,
                        'model_type' => User::class,
                        'model_id' => $u->id,
                        'team_id' => $sdUnit->id,
                    ], []);
                }
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // Delete grade-scoped rubrics before reverting schema
        DB::table('quran_rubric_items')->where('scope', 'grade')->delete();

        Schema::table('quran_rubric_items', function (Blueprint $table) {
            $table->dropIndex('quran_rubrics_grade_scope_idx');
            $table->dropColumn(['scope', 'grade_level']);
        });
    }
};

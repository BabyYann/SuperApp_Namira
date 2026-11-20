<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Quran\Models\QuranAssessment;
use App\Modules\Quran\Models\QuranEnrollment;
use App\Modules\Quran\Models\QuranGroup;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranLevelHistory;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Models\QuranSession;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Quran\Services\QuranSetupService;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuranModuleTest extends TestCase
{
    use RefreshDatabase;

    private Unit $sdUnit;
    private Unit $daycareUnit;
    private Unit $pavlovUnit;
    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['super_admin_yayasan', 'admin_unit', 'teacher', 'guru_alquran', 'koordinator_alquran'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $this->activeYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->sdUnit = Unit::create([
            'name' => 'SD Namira',
            'code' => 'SD',
            'category' => 'SD',
            'level' => 'Nasional',
            'unit_type' => 'formal_school',
        ]);

        $this->daycareUnit = Unit::create([
            'name' => 'Day Care Kraksaan',
            'code' => 'DAY-KRA',
            'category' => 'Daycare',
            'level' => 'Nasional',
            'unit_type' => 'daycare',
        ]);

        $this->pavlovUnit = Unit::create([
            'name' => 'PAVLOV',
            'code' => 'PVO',
            'category' => 'Lainnya',
            'level' => 'Nasional',
            'unit_type' => 'formal_school',
        ]);

        (new QuranSetupService())->ensureUnitDefaults($this->sdUnit);
    }

    private function makeUserWithRoles(array $roles, int $unitId): User
    {
        $user = User::factory()->create();
        setPermissionsTeamId($unitId);
        foreach ($roles as $roleName) {
            $user->assignRole($roleName);
        }

        return $user;
    }

    public function test_default_curriculum_seeded_with_10_ordered_levels_and_rubrics(): void
    {
        $levels = QuranLevel::where('unit_id', $this->sdUnit->id)->orderBy('sort_order')->pluck('name')->toArray();

        $this->assertEquals([
            'Jilid 1', 'Jilid 2', 'Jilid 3', 'Jilid 4', 'Jilid 5', 'Jilid 6',
            'Al-Qur\'an', 'Ghorib', 'Tajwid', 'Tahfidz',
        ], $levels);

        $jilid1 = QuranLevel::where('unit_id', $this->sdUnit->id)->where('name', 'Jilid 1')->firstOrFail();
        $this->assertGreaterThan(0, QuranRubricItem::where('level_id', $jilid1->id)->where('category', 'materi')->count());
        $this->assertGreaterThan(0, QuranRubricItem::where('level_id', $jilid1->id)->where('category', 'surah_pendek')->count());
        $this->assertGreaterThan(0, QuranRubricItem::where('level_id', $jilid1->id)->where('category', 'doa_harian')->count());
        $this->assertGreaterThan(0, QuranRubricItem::where('level_id', $jilid1->id)->where('category', 'hadist')->count());
    }

    public function test_daycare_and_pavlov_units_are_blocked_from_quran_module(): void
    {
        $this->assertTrue($this->sdUnit->hasFeature('quran'));
        $this->assertFalse($this->daycareUnit->hasFeature('quran'));
        $this->assertFalse($this->pavlovUnit->hasFeature('quran'));

        $daycareTeacher = $this->makeUserWithRoles(['guru_alquran'], $this->daycareUnit->id);
        $this->actingAs($daycareTeacher)
            ->withSession(['active_unit_id' => $this->daycareUnit->id])
            ->get(route('quran.index'))
            ->assertStatus(403);

        $pavlovTeacher = $this->makeUserWithRoles(['guru_alquran'], $this->pavlovUnit->id);
        $this->actingAs($pavlovTeacher)
            ->withSession(['active_unit_id' => $this->pavlovUnit->id])
            ->get(route('quran.index'))
            ->assertStatus(403);
    }

    public function test_multi_session_multi_group_cross_classroom_enrollment_and_double_promotion_in_one_semester(): void
    {
        $coordinator = $this->makeUserWithRoles(['koordinator_alquran'], $this->sdUnit->id);
        $guruA = $this->makeUserWithRoles(['guru_alquran'], $this->sdUnit->id);
        $guruB = $this->makeUserWithRoles(['teacher', 'guru_alquran'], $this->sdUnit->id);

        // Create 2 sessions (Pagi & Siang)
        $this->actingAs($coordinator)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.sessions.store'), [
                'name' => 'Sesi 1 (Pagi)',
                'start_time' => '07:30',
                'end_time' => '08:30',
                'target_note' => 'Kelas 1 & 2',
            ])->assertRedirect();

        $this->actingAs($coordinator)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.sessions.store'), [
                'name' => 'Sesi 3 (Siang)',
                'start_time' => '12:30',
                'end_time' => '13:30',
                'target_note' => 'Kelas 5 & 6',
            ])->assertRedirect();

        $sesiPagi = QuranSession::where('name', 'Sesi 1 (Pagi)')->firstOrFail();
        $sesiSiang = QuranSession::where('name', 'Sesi 3 (Siang)')->firstOrFail();

        $jilid2 = QuranLevel::where('unit_id', $this->sdUnit->id)->where('name', 'Jilid 2')->firstOrFail();
        $jilid3 = QuranLevel::where('unit_id', $this->sdUnit->id)->where('name', 'Jilid 3')->firstOrFail();
        $jilid4 = QuranLevel::where('unit_id', $this->sdUnit->id)->where('name', 'Jilid 4')->firstOrFail();

        // Same teacher (guruA) handles 2 different sessions / levels; Jilid 3 has 2 groups with different teachers
        $gJilid2 = QuranGroup::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'level_id' => $jilid2->id,
            'session_id' => $sesiPagi->id,
            'name' => 'Jilid 2 Pagi',
            'teacher_id' => $guruA->id,
        ]);

        $gJilid3A = QuranGroup::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'level_id' => $jilid3->id,
            'session_id' => $sesiSiang->id,
            'name' => 'Jilid 3 A',
            'teacher_id' => $guruA->id,
        ]);

        $gJilid3B = QuranGroup::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'level_id' => $jilid3->id,
            'session_id' => $sesiSiang->id,
            'name' => 'Jilid 3 B',
            'teacher_id' => $guruB->id,
        ]);

        $gJilid4 = QuranGroup::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'level_id' => $jilid4->id,
            'session_id' => $sesiSiang->id,
            'name' => 'Jilid 4 Siang',
            'teacher_id' => $guruB->id,
        ]);

        // Cross-classroom students: Kelas 2 & Kelas 6 placed in the same Jilid 2 group
        $kls2 = Classroom::create(['unit_id' => $this->sdUnit->id, 'academic_year_id' => $this->activeYear->id, 'name' => '2A', 'level' => '2']);
        $kls6 = Classroom::create(['unit_id' => $this->sdUnit->id, 'academic_year_id' => $this->activeYear->id, 'name' => '6A', 'level' => '6']);

        $student1 = Student::create([
            'user_id' => User::factory()->create()->id,
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'classroom_id' => $kls2->id,
            'nis' => 'SD001',
            'nisn' => '0012345678',
            'full_name' => 'Ahmad Zaki',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'user_id' => User::factory()->create()->id,
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'classroom_id' => $kls6->id,
            'nis' => 'SD002',
            'nisn' => '0012345679',
            'full_name' => 'Budi Santoso',
            'gender' => 'L',
            'status' => 'active',
        ]);

        // Bulk enroll both into Jilid 2
        $this->actingAs($coordinator)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.groups.enroll', $gJilid2->id), [
                'student_ids' => [$student1->id, $student2->id],
                'started_at' => '2026-07-15',
            ])->assertRedirect();

        $this->assertEquals(2, $gJilid2->activeEnrollments()->count());

        // Promote student1 TWICE within Semester Ganjil: Jilid 2 -> Jilid 3 B (guruB) -> Jilid 4 (guruB)
        $this->actingAs($guruA)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.students.promote', $student1->id), [
                'target_group_id' => $gJilid3B->id,
                'change_type' => 'promoted',
                'changed_at' => '2026-09-10',
                'exam_score' => 'A (93)',
                'notes' => 'Lulus naik jilid 3',
            ])->assertRedirect();

        $this->actingAs($guruB)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.students.promote', $student1->id), [
                'target_group_id' => $gJilid4->id,
                'change_type' => 'promoted',
                'changed_at' => '2026-11-05',
                'exam_score' => 'A (95)',
                'notes' => 'Lulus cepat naik jilid 4',
            ])->assertRedirect();

        // Only 1 active enrollment for student1 at Jilid 4
        $activeEnrollments = QuranEnrollment::where('student_id', $student1->id)
            ->where('status', 'active')
            ->get();
        $this->assertCount(1, $activeEnrollments);
        $this->assertEquals($jilid4->id, $activeEnrollments->first()->level_id);
        $this->assertEquals($gJilid4->id, $activeEnrollments->first()->group_id);

        // 3 chronological history records (initial + 2 promotions)
        $histories = QuranLevelHistory::where('student_id', $student1->id)->orderBy('id')->get();
        $this->assertCount(3, $histories);
        $this->assertEquals('initial', $histories[0]->change_type);
        $this->assertEquals('promoted', $histories[1]->change_type);
        $this->assertEquals($jilid2->id, $histories[1]->from_level_id);
        $this->assertEquals($jilid3->id, $histories[1]->to_level_id);
        $this->assertEquals($guruB->id, $histories[1]->to_teacher_id);
        $this->assertEquals('promoted', $histories[2]->change_type);
        $this->assertEquals($jilid3->id, $histories[2]->from_level_id);
        $this->assertEquals($jilid4->id, $histories[2]->to_level_id);

        // Verify history JSON endpoint
        $this->actingAs($coordinator)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->getJson(route('quran.students.history', $student1->id))
            ->assertOk()
            ->assertJsonCount(3, 'histories');
    }

    public function test_assessment_saving_period_locking_and_f4_report_snapshot(): void
    {
        $coordinator = $this->makeUserWithRoles(['koordinator_alquran'], $this->sdUnit->id);
        $guru = $this->makeUserWithRoles(['guru_alquran'], $this->sdUnit->id);

        $jilid4 = QuranLevel::where('unit_id', $this->sdUnit->id)->where('name', 'Jilid 4')->firstOrFail();
        $jilid5 = QuranLevel::where('unit_id', $this->sdUnit->id)->where('name', 'Jilid 5')->firstOrFail();

        $group4 = QuranGroup::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'level_id' => $jilid4->id,
            'name' => 'Jilid 4 A',
            'teacher_id' => $guru->id,
        ]);

        $group5 = QuranGroup::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'level_id' => $jilid5->id,
            'name' => 'Jilid 5 A',
            'teacher_id' => $guru->id,
        ]);

        $kls = Classroom::create(['unit_id' => $this->sdUnit->id, 'academic_year_id' => $this->activeYear->id, 'name' => '4A', 'level' => '4']);
        $student = Student::create([
            'user_id' => User::factory()->create()->id,
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'classroom_id' => $kls->id,
            'nis' => 'SD010',
            'nisn' => '0099887766',
            'full_name' => 'Siti Aisyah',
            'gender' => 'P',
            'status' => 'active',
        ]);

        QuranEnrollment::create([
            'unit_id' => $this->sdUnit->id,
            'academic_year_id' => $this->activeYear->id,
            'student_id' => $student->id,
            'group_id' => $group4->id,
            'level_id' => $jilid4->id,
            'status' => 'active',
            'started_at' => '2026-07-15',
        ]);

        $materiItem = QuranRubricItem::where('level_id', $jilid4->id)->where('category', 'materi')->firstOrFail();
        $surahItem = QuranRubricItem::where('level_id', $jilid4->id)->where('category', 'surah_pendek')->firstOrFail();

        // Guru saves Semester Ganjil scores
        $this->actingAs($guru)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.assessments.store'), [
                'group_id' => $group4->id,
                'academic_year_id' => $this->activeYear->id,
                'semester' => 'ganjil',
                'entries' => [
                    [
                        'student_id' => $student->id,
                        'grades' => [
                            (string) $materiItem->id => 'A',
                            (string) $surahItem->id => 'B',
                        ],
                        'teacher_note' => 'Alhamdulillah sangat semangat dan tartil.',
                    ],
                ],
            ])->assertRedirect();

        $assessment = QuranAssessment::where('student_id', $student->id)->firstOrFail();
        $this->assertEquals($jilid4->id, $assessment->level_id);
        $this->assertEquals('A', $assessment->scores[(string) $materiItem->id]['grade']);

        // Promote student to Jilid 5 AFTER Semester Ganjil grading — Semester Ganjil report must still display Jilid 4!
        $this->actingAs($coordinator)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.students.promote', $student->id), [
                'target_group_id' => $group5->id,
                'change_type' => 'promoted',
                'changed_at' => '2026-12-20',
            ])->assertRedirect();

        // Print view renders Jilid 4 snapshot & teacher note
        $this->actingAs($guru)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->get(route('quran.reports.print', [
                'academic_year_id' => $this->activeYear->id,
                'semester' => 'ganjil',
                'student_ids' => $student->id,
            ]))
            ->assertOk()
            ->assertSee('Siti Aisyah')
            ->assertSee('Materi Pelajaran Jilid 4')
            ->assertSee('Alhamdulillah sangat semangat dan tartil.');

        // Lock input nilai & cetak rapor -> guru gets 403, coordinator still passes
        QuranSetting::where('unit_id', $this->sdUnit->id)->update([
            'lock_input_nilai' => true,
            'lock_cetak_rapor' => true,
        ]);

        $this->actingAs($guru)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('quran.assessments.store'), [
                'group_id' => $group5->id,
                'academic_year_id' => $this->activeYear->id,
                'semester' => 'ganjil',
                'entries' => [['student_id' => $student->id, 'grades' => []]],
            ])->assertStatus(403);

        $this->actingAs($guru)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->get(route('quran.reports.print', ['student_ids' => $student->id]))
            ->assertStatus(403);

        $this->actingAs($coordinator)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->get(route('quran.reports.print', ['student_ids' => $student->id]))
            ->assertOk();
    }

    public function test_teacher_controller_supports_standalone_quran_teacher_and_dual_role(): void
    {
        $admin = $this->makeUserWithRoles(['admin_unit'], $this->sdUnit->id);

        // 1. Standalone Guru Al-Qur'an Khusus (no 'teacher' role)
        $this->actingAs($admin)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('yayasan.teachers.store'), [
                'full_name' => 'Ustadzah Fatimah',
                'email' => 'fatimah.ummi@namira.test',
                'gender' => 'P',
                'is_regular_teacher' => false,
                'is_quran_teacher' => true,
                'is_quran_coordinator' => false,
            ])->assertRedirect();

        $fatimah = User::where('email', 'fatimah.ummi@namira.test')->firstOrFail();
        $fatimahRoles = \DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $fatimah->id)
            ->pluck('roles.name')
            ->toArray();

        $this->assertContains('guru_alquran', $fatimahRoles);
        $this->assertNotContains('teacher', $fatimahRoles);

        // 2. Dual-role Teacher + Guru Al-Qur'an
        $this->actingAs($admin)
            ->withSession(['active_unit_id' => $this->sdUnit->id])
            ->post(route('yayasan.teachers.store'), [
                'full_name' => 'Ustadz Ahmad',
                'email' => 'ahmad.dual@namira.test',
                'gender' => 'L',
                'is_regular_teacher' => true,
                'is_quran_teacher' => true,
                'is_quran_coordinator' => false,
            ])->assertRedirect();

        $ahmad = User::where('email', 'ahmad.dual@namira.test')->firstOrFail();
        $ahmadRoles = \DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $ahmad->id)
            ->pluck('roles.name')
            ->toArray();

        $this->assertContains('teacher', $ahmadRoles);
        $this->assertContains('guru_alquran', $ahmadRoles);
    }
}

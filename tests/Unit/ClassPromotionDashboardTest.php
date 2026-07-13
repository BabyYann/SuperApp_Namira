<?php

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\ClassPromotion;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use App\Models\User;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Counseling\Models\Violation;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class);

beforeEach(function () {
    DB::beginTransaction();
});

afterEach(function () {
    DB::rollBack();
});

test('promotion dashboard preview successfully validates students and returns stats', function () {
    // 1. Create an admin/user and authenticate
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    
    // Ensure role exists in the database
    DB::table('roles')->insertOrIgnore([
        ['name' => 'admin_yayasan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
    ]);
    $user->assignRole('admin_yayasan');

    // 2. Create academic years, units, classrooms
    $fromYear = AcademicYear::create([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
    ]);

    $toYear = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);

    $unit = Unit::create([
        'name' => 'Unit Test Dashboard',
        'code' => 'UTD_' . uniqid(),
        'level' => 'SD',
        'category' => 'formal',
    ]);

    $classroom = Classroom::create([
        'unit_id' => $unit->id,
        'name' => 'Classroom Test',
        'level' => '1',
    ]);

    // Set active unit in session
    session(['active_unit_id' => $unit->id]);

    // 3. Create students:
    // Student 1: Eligible
    $user1 = User::create([
        'name' => 'Eligible Student',
        'email' => 'eligible_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    $student1 = Student::create([
        'user_id' => $user1->id,
        'unit_id' => $unit->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $fromYear->id,
        'full_name' => 'Eligible Student',
        'gender' => 'L',
    ]);

    // Student 2: Blocked by Bill
    $user2 = User::create([
        'name' => 'Bill Blocked Student',
        'email' => 'bill_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    $student2 = Student::create([
        'user_id' => $user2->id,
        'unit_id' => $unit->id,
        'classroom_id' => $classroom->id,
        'academic_year_id' => $fromYear->id,
        'full_name' => 'Bill Blocked Student',
        'gender' => 'P',
    ]);
    StudentBill::create([
        'student_id' => $student2->id,
        'description' => 'SPP SPB',
        'bill_code' => 'INV-' . uniqid(),
        'original_amount' => 500000,
        'discount_amount' => 0,
        'final_amount' => 500000,
        'paid_amount' => 0,
        'status' => 'unpaid',
        'billing_date' => now(),
    ]);

    // 4. Act as user and hit preview endpoint
    $response = $this->actingAs($user)
        ->postJson(route('yayasan.promotion.preview'), [
            'from_classroom_id' => $classroom->id,
            'from_academic_year_id' => $fromYear->id,
            'to_classroom_id' => $classroom->id,
            'to_academic_year_id' => $toYear->id,
        ]);

    $response->assertStatus(200);

    // Verify response structure and summary values
    $data = $response->json();
    expect($data)->toHaveKeys(['students', 'summary', 'fromClassroom', 'fromAcademicYear']);
    
    $summary = $data['summary'];
    expect($summary['total_students'])->toBe(2);
    expect($summary['eligible_students'])->toBe(1);
    expect($summary['blocked_students'])->toBe(1);
    expect($summary['blocked_by_bill'])->toBe(1);

    // Verify student details
    $resStudents = $data['students'];
    $s1 = collect($resStudents)->firstWhere('student_id', $student1->id);
    $s2 = collect($resStudents)->firstWhere('student_id', $student2->id);

    expect($s1['status'])->toBe('eligible');
    expect($s2['status'])->toBe('blocked');
    expect($s2['violations'][0]['code'])->toBe('OUTSTANDING_BILL');
});

test('promotion dashboard preview export successfully generates CSV', function () {
    // Authenticate
    $user = User::create([
        'name' => 'Admin User 2',
        'email' => 'admin2_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    DB::table('roles')->insertOrIgnore([
        ['name' => 'admin_yayasan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
    ]);
    $user->assignRole('admin_yayasan');

    $fromYear = AcademicYear::create([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
    ]);

    $toYear = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);

    $unit = Unit::create([
        'name' => 'Unit Test Export',
        'code' => 'UTE_' . uniqid(),
        'level' => 'SD',
        'category' => 'formal',
    ]);

    $classroom = Classroom::create([
        'unit_id' => $unit->id,
        'name' => 'Classroom Export',
        'level' => '1',
    ]);

    session(['active_unit_id' => $unit->id]);

    $response = $this->actingAs($user)
        ->get(route('yayasan.promotion.export-preview', [
            'from_classroom_id' => $classroom->id,
            'from_academic_year_id' => $fromYear->id,
            'to_classroom_id' => $classroom->id,
            'to_academic_year_id' => $toYear->id,
        ]));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))->toContain('Preview_Promosi_Classroom_Export_');
});

test('promotion execution succeeds for eligible student, creates history and logs activity', function () {
    // 1. Setup admin and data
    $user = User::create([
        'name' => 'Admin Exec',
        'email' => 'admin_exec_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    DB::table('roles')->insertOrIgnore([
        ['name' => 'admin_yayasan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
    ]);
    $user->assignRole('admin_yayasan');

    $fromYear = AcademicYear::create([
        'name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => false,
    ]);
    $toYear = AcademicYear::create([
        'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
    ]);
    $unit = Unit::create([
        'name' => 'Unit Exec', 'code' => 'UE_' . uniqid(), 'level' => 'SD', 'category' => 'formal',
    ]);
    $fromClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 1A', 'level' => '1',
    ]);
    $toClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 2A', 'level' => '2',
    ]);

    session(['active_unit_id' => $unit->id]);

    $studentUser = User::create([
        'name' => 'Eligible Student Exec', 'email' => 'stud_exec_' . uniqid() . '@example.com', 'password' => bcrypt('password'),
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'unit_id' => $unit->id,
        'classroom_id' => $fromClassroom->id,
        'academic_year_id' => $fromYear->id,
        'full_name' => 'Eligible Student Exec',
        'gender' => 'L',
    ]);

    // Clear activity log before acting
    DB::table('activity_log')->delete();

    // 2. Perform promotion execution
    $response = $this->actingAs($user)
        ->post(route('yayasan.promotion.store'), [
            'from_classroom_id' => $fromClassroom->id,
            'from_academic_year_id' => $fromYear->id,
            'to_classroom_id' => $toClassroom->id,
            'to_academic_year_id' => $toYear->id,
            'promotions' => [
                [
                    'student_id' => $student->id,
                    'status' => 'naik',
                    'notes' => 'Lolos promosi akademik',
                ]
            ]
        ]);

    $response->assertRedirect(route('yayasan.promotion.history'));
    
    // 3. Verify student attributes are updated
    $student->refresh();
    expect($student->classroom_id)->toBe($toClassroom->id);
    expect($student->academic_year_id)->toBe($toYear->id);

    // 4. Verify ClassPromotion history is created
    $promoHistory = ClassPromotion::where('student_id', $student->id)->first();
    expect($promoHistory)->not->toBeNull();
    expect($promoHistory->status)->toBe('naik');
    expect($promoHistory->from_classroom_id)->toBe($fromClassroom->id);
    expect($promoHistory->to_classroom_id)->toBe($toClassroom->id);
    expect($promoHistory->validation_summary)->toBe('Eligible');
    expect($promoHistory->is_rolled_back)->toBeFalse();

    // 5. Verify Spatie Activity Log has logged the transactions
    $logs = DB::table('activity_log')->get();
    expect($logs->count())->toBeGreaterThanOrEqual(2);
    
    $startedLog = $logs->first(fn($l) => $l->description === 'Class Promotion: Started');
    $completedLog = $logs->first(fn($l) => $l->description === 'Class Promotion: Completed');

    expect($startedLog)->not->toBeNull();
    expect($completedLog)->not->toBeNull();

    $startedProperties = json_decode($startedLog->properties, true);
    $completedProperties = json_decode($completedLog->properties, true);

    expect($startedProperties['from_classroom'])->toBe('Kelas 1A');
    expect($completedProperties['total_students'])->toBe(1);
    expect($completedProperties['eligible'])->toBe(1);
});

test('promotion execution fails for blocked student, does not update database, logs failure', function () {
    // 1. Setup admin and data
    $user = User::create([
        'name' => 'Admin Exec Fail',
        'email' => 'admin_fail_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    DB::table('roles')->insertOrIgnore([
        ['name' => 'admin_yayasan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
    ]);
    $user->assignRole('admin_yayasan');

    $fromYear = AcademicYear::create([
        'name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => false,
    ]);
    $toYear = AcademicYear::create([
        'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
    ]);
    $unit = Unit::create([
        'name' => 'Unit Exec Fail', 'code' => 'UEF_' . uniqid(), 'level' => 'SD', 'category' => 'formal',
    ]);
    $fromClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 1A', 'level' => '1',
    ]);
    $toClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 2A', 'level' => '2',
    ]);

    session(['active_unit_id' => $unit->id]);

    $studentUser = User::create([
        'name' => 'Blocked Student Exec', 'email' => 'stud_fail_' . uniqid() . '@example.com', 'password' => bcrypt('password'),
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'unit_id' => $unit->id,
        'classroom_id' => $fromClassroom->id,
        'academic_year_id' => $fromYear->id,
        'full_name' => 'Blocked Student Exec',
        'gender' => 'L',
    ]);

    // Make student blocked by bill
    StudentBill::create([
        'student_id' => $student->id,
        'description' => 'SPP Bulanan',
        'bill_code' => 'INV-' . uniqid(),
        'original_amount' => 500000,
        'discount_amount' => 0,
        'final_amount' => 500000,
        'paid_amount' => 0,
        'status' => 'unpaid',
        'billing_date' => now(),
    ]);

    // Clear activity log before acting
    DB::table('activity_log')->delete();

    // 2. Perform promotion execution (should fail validation)
    $response = $this->actingAs($user)
        ->from(route('yayasan.promotion.index'))
        ->post(route('yayasan.promotion.store'), [
            'from_classroom_id' => $fromClassroom->id,
            'from_academic_year_id' => $fromYear->id,
            'to_classroom_id' => $toClassroom->id,
            'to_academic_year_id' => $toYear->id,
            'promotions' => [
                [
                    'student_id' => $student->id,
                    'status' => 'naik',
                    'notes' => 'Mencoba paksa promosi',
                ]
            ]
        ]);

    $response->assertRedirect(route('yayasan.promotion.index'));
    $response->assertSessionHas('error');

    // 3. Verify student attributes are NOT updated
    $student->refresh();
    expect($student->classroom_id)->toBe($fromClassroom->id);
    expect($student->academic_year_id)->toBe($fromYear->id);

    // 4. Verify no ClassPromotion record is created
    $promoHistory = ClassPromotion::where('student_id', $student->id)->first();
    expect($promoHistory)->toBeNull();

    // 5. Verify Spatie Activity Log logged the failure
    $logs = DB::table('activity_log')->get();
    $failedLog = $logs->first(fn($l) => $l->description === 'Class Promotion: Failed');
    expect($failedLog)->not->toBeNull();

    $failedProperties = json_decode($failedLog->properties, true);
    expect($failedProperties['errors'][$student->id]['student_name'])->toBe('Blocked Student Exec');
});

test('promotion rollback successfully restores student classroom, marks history, logs rollback', function () {
    // 1. Setup admin and data
    $user = User::create([
        'name' => 'Admin Rollback',
        'email' => 'admin_rb_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    DB::table('roles')->insertOrIgnore([
        ['name' => 'admin_yayasan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
    ]);
    $user->assignRole('admin_yayasan');

    $fromYear = AcademicYear::create([
        'name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => false,
    ]);
    $toYear = AcademicYear::create([
        'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
    ]);
    $unit = Unit::create([
        'name' => 'Unit Rollback', 'code' => 'UR_' . uniqid(), 'level' => 'SD', 'category' => 'formal',
    ]);
    $fromClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 1A', 'level' => '1',
    ]);
    $toClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 2A', 'level' => '2',
    ]);

    session(['active_unit_id' => $unit->id]);

    $studentUser = User::create([
        'name' => 'Rollback Student', 'email' => 'stud_rb_' . uniqid() . '@example.com', 'password' => bcrypt('password'),
    ]);
    
    // Simulate student already promoted to Kelas 2A
    $student = Student::create([
        'user_id' => $studentUser->id,
        'unit_id' => $unit->id,
        'classroom_id' => $toClassroom->id,
        'academic_year_id' => $toYear->id,
        'full_name' => 'Rollback Student',
        'gender' => 'P',
    ]);

    // Create promotion record to rollback
    $promotion = ClassPromotion::create([
        'student_id' => $student->id,
        'from_classroom_id' => $fromClassroom->id,
        'to_classroom_id' => $toClassroom->id,
        'from_academic_year_id' => $fromYear->id,
        'to_academic_year_id' => $toYear->id,
        'status' => 'naik',
        'notes' => 'Promosi awal',
        'promoted_by' => $user->id,
        'promoted_at' => now(),
        'validation_summary' => 'Eligible',
        'is_rolled_back' => false,
    ]);

    // Clear activity log before acting
    DB::table('activity_log')->delete();

    // 2. Perform rollback
    $response = $this->actingAs($user)
        ->from(route('yayasan.promotion.history'))
        ->post(route('yayasan.promotion.rollback', $promotion->id));

    $response->assertRedirect(route('yayasan.promotion.history'));

    // 3. Verify student attributes are restored
    $student->refresh();
    expect($student->classroom_id)->toBe($fromClassroom->id);
    expect($student->academic_year_id)->toBe($fromYear->id);

    // 4. Verify promotion record is NOT deleted, but updated with rollback metadata
    $promotion->refresh();
    expect($promotion->is_rolled_back)->toBeTrue();
    expect($promotion->rolled_back_by)->toBe($user->id);
    expect($promotion->rolled_back_at)->not->toBeNull();

    // 5. Verify Spatie Activity Log has Rollback Executed
    $logs = DB::table('activity_log')->get();
    $rollbackLog = $logs->first(fn($l) => $l->description === 'Class Promotion: Rollback Executed');
    expect($rollbackLog)->not->toBeNull();

    $rollbackProperties = json_decode($rollbackLog->properties, true);
    expect($rollbackProperties['student_name'])->toBe('Rollback Student');
});

test('promotion rollback fails if not latest promotion for student', function () {
    $user = User::create([
        'name' => 'Admin Rollback Fail',
        'email' => 'admin_rb_fail_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);
    DB::table('roles')->insertOrIgnore([
        ['name' => 'admin_yayasan', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
    ]);
    $user->assignRole('admin_yayasan');

    $fromYear = AcademicYear::create([
        'name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => false,
    ]);
    $toYear = AcademicYear::create([
        'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true,
    ]);
    $unit = Unit::create([
        'name' => 'Unit Rollback Fail', 'code' => 'URF_' . uniqid(), 'level' => 'SD', 'category' => 'formal',
    ]);
    $fromClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 1A', 'level' => '1',
    ]);
    $toClassroom = Classroom::create([
        'unit_id' => $unit->id, 'name' => 'Kelas 2A', 'level' => '2',
    ]);

    session(['active_unit_id' => $unit->id]);

    $studentUser = User::create([
        'name' => 'Rollback Fail Student', 'email' => 'stud_rbf_' . uniqid() . '@example.com', 'password' => bcrypt('password'),
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'unit_id' => $unit->id,
        'classroom_id' => $toClassroom->id,
        'academic_year_id' => $toYear->id,
        'full_name' => 'Rollback Fail Student',
        'gender' => 'P',
    ]);

    // Create an older promotion
    $promotion1 = ClassPromotion::create([
        'student_id' => $student->id,
        'from_classroom_id' => $fromClassroom->id,
        'to_classroom_id' => $toClassroom->id,
        'from_academic_year_id' => $fromYear->id,
        'to_academic_year_id' => $toYear->id,
        'status' => 'naik',
        'promoted_by' => $user->id,
        'promoted_at' => now()->subDay(),
        'is_rolled_back' => false,
    ]);

    // Create a newer promotion (which is the latest)
    $promotion2 = ClassPromotion::create([
        'student_id' => $student->id,
        'from_classroom_id' => $toClassroom->id,
        'to_classroom_id' => $toClassroom->id,
        'from_academic_year_id' => $toYear->id,
        'to_academic_year_id' => $toYear->id,
        'status' => 'tinggal',
        'promoted_by' => $user->id,
        'promoted_at' => now(),
        'is_rolled_back' => false,
    ]);

    // 2. Perform rollback on the OLDER promotion ($promotion1) - should fail
    $response = $this->actingAs($user)
        ->from(route('yayasan.promotion.history'))
        ->post(route('yayasan.promotion.rollback', $promotion1->id));

    $response->assertRedirect(route('yayasan.promotion.history'));
    $response->assertSessionHas('error');
    
    $promotion1->refresh();
    expect($promotion1->is_rolled_back)->toBeFalse();
});

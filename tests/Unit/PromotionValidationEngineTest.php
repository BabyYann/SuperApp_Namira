<?php

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Services\Promotion\PromotionValidationEngine;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Counseling\Models\Violation;
use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class);

beforeEach(function () {
    DB::beginTransaction();
});

afterEach(function () {
    DB::rollBack();
});

test('promotion validation engine blocks student with unpaid bills', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);

    $unit = Unit::create([
        'name' => 'Test Unit',
        'code' => 'TU_' . uniqid(),
        'level' => 'SD',
        'category' => 'formal',
    ]);

    $student = Student::create([
        'user_id' => $user->id,
        'unit_id' => $unit->id,
        'full_name' => 'Siswa Test',
        'gender' => 'L',
    ]);

    $engine = new PromotionValidationEngine();
    
    // 1. Initial State: No bills, should pass
    $errors = $engine->validateStudent($student);
    expect($errors)->toBeEmpty();

    // 2. Add unpaid bill: Should fail
    $bill = StudentBill::create([
        'student_id' => $student->id,
        'description' => 'SPP Test',
        'bill_code' => 'INV-TEST-' . uniqid(),
        'original_amount' => 100000,
        'discount_amount' => 0,
        'final_amount' => 100000,
        'paid_amount' => 0,
        'status' => 'unpaid',
        'billing_date' => now(),
    ]);

    $errors = $engine->validateStudent($student);
    expect($errors)->not->toBeEmpty();
    expect($errors[0])->toContain('Siswa memiliki tagihan tertunggak');

    // 3. Mark bill paid: Should pass again
    $bill->update(['status' => 'paid']);
    $errors = $engine->validateStudent($student);
    expect($errors)->toBeEmpty();
});

test('promotion validation engine blocks student with high disciplinary points', function () {
    $user = User::create([
        'name' => 'Test User 2',
        'email' => 'test2_' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);

    $unit = Unit::create([
        'name' => 'Test Unit 2',
        'code' => 'TU2_' . uniqid(),
        'level' => 'SD',
        'category' => 'formal',
    ]);

    $student = Student::create([
        'user_id' => $user->id,
        'unit_id' => $unit->id,
        'full_name' => 'Siswa Test 2',
        'gender' => 'P',
    ]);

    $engine = new PromotionValidationEngine();
    
    // 1. Initial State: No violations, should pass
    $errors = $engine->validateStudent($student);
    expect($errors)->toBeEmpty();

    // 2. Add violation: 120 points (exceeds default 100 limit) -> Should fail
    Violation::create([
        'student_id' => $student->id,
        'unit_id' => $unit->id,
        'points' => 120,
        'date' => now(),
    ]);

    $errors = $engine->validateStudent($student);
    expect($errors)->not->toBeEmpty();
    expect($errors[0])->toContain('Akumulasi skor pelanggaran siswa');
});

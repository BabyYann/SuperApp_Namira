<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('filters classrooms by active unit scope', function () {
    $unitSD = createUnit(['name' => 'SD Namira', 'code' => 'SDN']);
    $unitSMP = createUnit(['name' => 'SMP Namira', 'code' => 'SMP']);

    $user = createUserWithRole('admin_unit', $unitSD->id);

    Classroom::create(['unit_id' => $unitSD->id, 'name' => 'Kelas 1A', 'level' => '1']);
    Classroom::create(['unit_id' => $unitSMP->id, 'name' => 'Kelas 7A', 'level' => '7']);

    $response = $this->actingAs($user)
        ->get(route('yayasan.classrooms.index'));

    $response->assertOk();
    $response->assertSee('Kelas 1A');
    $response->assertDontSee('Kelas 7A');
});

it('filters students by active unit scope', function () {
    $unitSD = createUnit(['name' => 'SD Namira', 'code' => 'SDN']);
    $unitSMP = createUnit(['name' => 'SMP Namira', 'code' => 'SMP']);

    $user = createUserWithRole('admin_unit', $unitSD->id);

    $classroomSD = Classroom::create(['unit_id' => $unitSD->id, 'name' => 'Kelas 1A', 'level' => '1']);
    $classroomSMP = Classroom::create(['unit_id' => $unitSMP->id, 'name' => 'Kelas 7A', 'level' => '7']);

    $studentUser1 = User::create(['name' => 'Ahmad SD', 'email' => 'ahmad_sd_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    Student::create(['user_id' => $studentUser1->id, 'unit_id' => $unitSD->id, 'classroom_id' => $classroomSD->id, 'full_name' => 'Ahmad SD', 'gender' => 'L']);

    $studentUser2 = User::create(['name' => 'Budi SMP', 'email' => 'budi_smp_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    Student::create(['user_id' => $studentUser2->id, 'unit_id' => $unitSMP->id, 'classroom_id' => $classroomSMP->id, 'full_name' => 'Budi SMP', 'gender' => 'L']);

    $response = $this->actingAs($user)
        ->get(route('yayasan.students.index'));

    $response->assertOk();
    $response->assertSee('Ahmad SD');
    $response->assertDontSee('Budi SMP');
});

it('switches unit scope correctly', function () {
    $unit1 = createUnit(['name' => 'SD Namira', 'code' => 'SDN']);
    $unit2 = createUnit(['name' => 'SMP Namira', 'code' => 'SMP']);

    $user = createUserWithRole('super_admin_yayasan');

    Classroom::create(['unit_id' => $unit1->id, 'name' => 'Kelas 1A', 'level' => '1']);
    Classroom::create(['unit_id' => $unit2->id, 'name' => 'Kelas 7A', 'level' => '7']);

    session(['active_unit_id' => $unit1->id]);

    $response = $this->actingAs($user)
        ->post(route('yayasan.switch-unit'), ['unit_id' => $unit2->id]);

    $response->assertRedirect();
    $this->assertEquals($unit2->id, session('active_unit_id'));
});

it('does not leak data between units', function () {
    $unitA = createUnit(['name' => 'Unit A', 'code' => 'UA']);
    $unitB = createUnit(['name' => 'Unit B', 'code' => 'UB']);

    $user = createUserWithRole('admin_unit', $unitA->id);

    $classroomA = Classroom::create(['unit_id' => $unitA->id, 'name' => 'Kelas A', 'level' => '1']);
    $classroomB = Classroom::create(['unit_id' => $unitB->id, 'name' => 'Kelas B', 'level' => '1']);

    $userA = User::create(['name' => 'Siswa A', 'email' => 'siswa_a_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    Student::create(['user_id' => $userA->id, 'unit_id' => $unitA->id, 'classroom_id' => $classroomA->id, 'full_name' => 'Siswa A', 'gender' => 'L']);

    $userB = User::create(['name' => 'Siswa B', 'email' => 'siswa_b_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    Student::create(['user_id' => $userB->id, 'unit_id' => $unitB->id, 'classroom_id' => $classroomB->id, 'full_name' => 'Siswa B', 'gender' => 'L']);

    $response = $this->actingAs($user)
        ->get(route('yayasan.students.index'));

    $response->assertOk();
    $response->assertSee('Siswa A');
    $response->assertDontSee('Siswa B');
});

<?php

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Academic\Models\Subject;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->unit = createUnit();
    $this->user = createUserWithRole('admin_unit', $this->unit->id);
    $this->academicYear = createAcademicYear();
});

it('can display classroom index page', function () {
    Classroom::create(['unit_id' => $this->unit->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Kelas 1A', 'level' => '1']);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.classrooms.index'));

    $response->assertOk();
    $response->assertSee('Kelas 1A');
});

it('can create a classroom', function () {
    $response = $this->actingAs($this->user)
        ->post(route('yayasan.classrooms.store'), [
            'name' => 'Kelas 2B',
            'level' => '2',
            'academic_year_id' => $this->academicYear->id,
        ]);

    $response->assertRedirect();
});

it('can update a classroom', function () {
    $classroom = Classroom::create(['unit_id' => $this->unit->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Kelas 1A', 'level' => '1']);

    $response = $this->actingAs($this->user)
        ->put(route('yayasan.classrooms.update', $classroom->id), [
            'name' => 'Kelas 1A Updated',
            'level' => '1',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('classrooms', [
        'id' => $classroom->id,
        'name' => 'Kelas 1A Updated',
    ]);
});

it('can delete a classroom', function () {
    $classroom = Classroom::create(['unit_id' => $this->unit->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Kelas 1A', 'level' => '1']);

    $response = $this->actingAs($this->user)
        ->delete(route('yayasan.classrooms.destroy', $classroom->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('classrooms', ['id' => $classroom->id]);
});

it('can display student index page', function () {
    $classroom = Classroom::create(['unit_id' => $this->unit->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Kelas 1A', 'level' => '1']);
    $studentUser = User::create(['name' => 'Ahmad', 'email' => 'ahmad_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    Student::create(['user_id' => $studentUser->id, 'unit_id' => $this->unit->id, 'classroom_id' => $classroom->id, 'full_name' => 'Ahmad', 'gender' => 'L']);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.students.index'));

    $response->assertOk();
    $response->assertSee('Ahmad');
});

it('can create a student', function () {
    $classroom = Classroom::create(['unit_id' => $this->unit->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Kelas 1A', 'level' => '1']);

    $response = $this->actingAs($this->user)
        ->post(route('yayasan.students.store'), [
            'full_name' => 'Budi Santoso',
            'email' => 'budi_' . uniqid() . '@test.com',
            'gender' => 'L',
            'classroom_id' => $classroom->id,
            'nis' => 'NIS' . uniqid(),
        ]);

    $response->assertRedirect();
});

it('can display teacher index page', function () {
    $teacherUser = User::create(['name' => 'Pak Guru', 'email' => 'guru_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    Teacher::create(['user_id' => $teacherUser->id, 'unit_id' => $this->unit->id, 'full_name' => 'Pak Guru', 'gender' => 'L']);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.teachers.index'));

    $response->assertOk();
    $response->assertSee('Pak Guru');
});

it('can create a teacher', function () {
    $response = $this->actingAs($this->user)
        ->post(route('yayasan.teachers.store'), [
            'full_name' => 'Ibu Guru',
            'email' => 'guru_' . uniqid() . '@test.com',
            'gender' => 'P',
            'nip' => 'NIP' . uniqid(),
        ]);

    $response->assertRedirect();
});

it('can display subject index page', function () {
    Subject::create(['unit_id' => $this->unit->id, 'name' => 'Matematika']);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.subjects.index'));

    $response->assertOk();
    $response->assertSee('Matematika');
});

it('can create a subject', function () {
    $response = $this->actingAs($this->user)
        ->post(route('yayasan.subjects.store'), [
            'name' => 'Bahasa Indonesia',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subjects', [
        'unit_id' => $this->unit->id,
        'name' => 'Bahasa Indonesia',
    ]);
});

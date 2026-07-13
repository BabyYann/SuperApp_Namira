<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('allows super_admin_yayasan to access yayasan settings', function () {
    $unit = createUnit();
    $user = createUserWithRole('super_admin_yayasan', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.settings.index'));

    $response->assertOk();
});

it('blocks admin_unit from accessing yayasan settings', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.settings.index'));

    $response->assertForbidden();
});

it('blocks teacher from accessing finance module', function () {
    $unit = createUnit();
    $user = createUserWithRole('teacher', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.finance.dashboard'));

    $response->assertForbidden();
});

it('allows admin_unit to access yayasan dashboard', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.dashboard'));

    $response->assertOk();
});

it('allows teacher to access counseling module', function () {
    $unit = createUnit();
    $user = createUserWithRole('teacher', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('counseling.violations.index'));

    $response->assertOk();
});

it('blocks siswa from accessing yayasan admin routes', function () {
    $unit = createUnit();
    $user = createUserWithRole('siswa', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.units.index'));

    $response->assertForbidden();
});

it('allows staff_yayasan to access yayasan monitoring', function () {
    $unit = createUnit();
    $user = createUserWithRole('staff_yayasan', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.monitoring.index'));

    $response->assertOk();
});

it('allows staff_unit to access yayasan monitoring', function () {
    $unit = createUnit();
    $user = createUserWithRole('staff_unit', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.monitoring.index'));

    $response->assertOk();
});

it('allows koordinator_sarpar to access sarpar module', function () {
    $unit = createUnit();
    $user = createUserWithRole('koordinator_sarpar', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('sarpar.dashboard'));

    $response->assertOk();
});

it('allows teacher to access sarpar module', function () {
    $unit = createUnit();
    $user = createUserWithRole('teacher', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('sarpar.dashboard'));

    $response->assertOk();
});

it('allows siswa to access student portal', function () {
    $unit = createUnit();
    $user = createUserWithRole('siswa', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('student.dashboard'));

    $response->assertOk();
});

it('blocks teacher from accessing student portal', function () {
    $unit = createUnit();
    $user = createUserWithRole('teacher', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('student.dashboard'));

    $response->assertForbidden();
});

it('blocks unauthenticated user from accessing auth routes', function () {
    $response = $this->get(route('yayasan.dashboard'));

    $response->assertRedirect();
});

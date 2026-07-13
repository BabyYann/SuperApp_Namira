<?php

use App\Modules\Yayasan\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can display yayasan dashboard', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.dashboard'));

    $response->assertOk();
});

it('can display units index', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_yayasan');

    $response = $this->actingAs($user)
        ->get(route('yayasan.units.index'));

    $response->assertOk();
    $response->assertSee($unit->name);
});

it('can create a unit', function () {
    $user = createUserWithRole('super_admin_yayasan');

    $response = $this->actingAs($user)
        ->post(route('yayasan.units.store'), [
            'name' => 'TK Namira',
            'code' => 'TKN',
            'category' => 'TK',
            'level' => 'Nasional',
            'work_start_time' => '07:00',
            'work_end_time' => '15:00',
            'late_tolerance_minutes' => 15,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('units', [
        'name' => 'TK Namira',
        'code' => 'TKN',
    ]);
});

it('can display academic years index', function () {
    $year = createAcademicYear(['name' => '2025/2026']);
    $unit = createUnit();
    $user = createUserWithRole('admin_yayasan');

    $response = $this->actingAs($user)
        ->get(route('yayasan.academic-years.index'));

    $response->assertOk();
    $response->assertSee('2025\/2026');
});

it('can create an academic year', function () {
    $user = createUserWithRole('super_admin_yayasan');

    $response = $this->actingAs($user)
        ->post(route('yayasan.academic-years.store'), [
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => false,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('academic_years', [
        'name' => '2026/2027',
    ]);
});

it('can display users index', function () {
    $user = createUserWithRole('admin_yayasan');

    $response = $this->actingAs($user)
        ->get(route('yayasan.users.index'));

    $response->assertOk();
});

it('super_admin can access settings page', function () {
    $unit = createUnit();
    $user = createUserWithRole('super_admin_yayasan', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.settings.index'));

    $response->assertOk();
});

it('admin_unit cannot access settings page', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    $response = $this->actingAs($user)
        ->get(route('yayasan.settings.index'));

    $response->assertForbidden();
});

it('can switch unit', function () {
    $unit1 = createUnit(['name' => 'SD Namira']);
    $unit2 = createUnit(['name' => 'SMP Namira']);
    $user = createUserWithRole('super_admin_yayasan', $unit1->id);

    $response = $this->actingAs($user)
        ->post(route('yayasan.switch-unit'), ['unit_id' => $unit2->id]);

    $response->assertRedirect();
    $this->assertEquals($unit2->id, session('active_unit_id'));
});

it('dashboard redirects siswa to student portal', function () {
    $unit = createUnit();
    $user = createUserWithRole('siswa', $unit->id);

    $response = $this->actingAs($user)
        ->get('/dashboard');

    $response->assertRedirect(route('student.dashboard'));
});

it('dashboard redirects yayasan user to yayasan dashboard', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    $response = $this->actingAs($user)
        ->get('/dashboard');

    $response->assertRedirect(route('yayasan.dashboard'));
});

it('can display monitoring page', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_yayasan');

    $response = $this->actingAs($user)
        ->get(route('yayasan.monitoring.index'));

    $response->assertOk();
});

<?php

use App\Modules\Yayasan\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('blocks access to sarpar when feature is disabled', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    SystemSetting::setSetting('feature_sarpar', '0');
    Cache::forget('system_settings');

    $response = $this->actingAs($user)
        ->get(route('sarpar.dashboard'));

    $response->assertStatus(403);
});

it('allows access to sarpar when feature is enabled', function () {
    $unit = createUnit();
    $user = createUserWithRole('admin_unit', $unit->id);

    SystemSetting::setSetting('feature_sarpar', '1');
    Cache::forget('system_settings');

    $response = $this->actingAs($user)
        ->get(route('sarpar.dashboard'));

    $response->assertOk();
});

it('blocks access to counseling when feature is disabled', function () {
    $unit = createUnit();
    $user = createUserWithRole('teacher', $unit->id);

    SystemSetting::setSetting('feature_counseling', '0');
    Cache::forget('system_settings');

    $response = $this->actingAs($user)
        ->get(route('counseling.violations.index'));

    $response->assertStatus(403);
});

it('allows super_admin to bypass disabled feature flags', function () {
    $unit = createUnit();
    $user = createUserWithRole('super_admin_yayasan');

    SystemSetting::setSetting('feature_sarpar', '0');
    Cache::forget('system_settings');

    $response = $this->actingAs($user)
        ->get(route('sarpar.dashboard'));

    $response->assertOk();
});

it('blocks access to employee when feature is disabled', function () {
    $unit = createUnit();
    $user = createUserWithRole('teacher', $unit->id);

    SystemSetting::setSetting('feature_employee', '0');
    Cache::forget('system_settings');

    $response = $this->actingAs($user)
        ->get(route('employee.attendance.index'));

    $response->assertStatus(403);
});

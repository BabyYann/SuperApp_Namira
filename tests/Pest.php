<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createRole(string $name): void
{
    \Illuminate\Support\Facades\DB::table('roles')->insertOrIgnore([
        'name' => $name,
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createUserWithRole(string $role, ?int $unitId = null): \App\Models\User
{
    createRole($role);

    $user = \App\Models\User::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);

    $registrar = app(\Spatie\Permission\PermissionRegistrar::class);

    // For unit-scoped roles, set team context before assigning so model_has_roles.team_id is correct
    if ($unitId) {
        $registrar->setPermissionsTeamId($unitId);
    }

    $user->assignRole($role);

    // Reset team context so CheckUnitScope middleware handles it naturally during the request
    $registrar->setPermissionsTeamId(null);
    $registrar->forgetCachedPermissions();

    if ($unitId) {
        session(['active_unit_id' => $unitId]);
    }

    return $user;
}

function createUnit(array $overrides = []): \App\Modules\Yayasan\Models\Unit
{
    return \App\Modules\Yayasan\Models\Unit::create(array_merge([
        'name' => 'Unit ' . uniqid(),
        'code' => 'UNI_' . uniqid(),
        'level' => 'SD',
        'category' => 'formal',
    ], $overrides));
}

function createAcademicYear(array $overrides = []): \App\Modules\Yayasan\Models\AcademicYear
{
    return \App\Modules\Yayasan\Models\AcademicYear::create(array_merge([
        'name' => '2025/2026',
        'semester' => 'ganjil',
        'is_active' => true,
    ], $overrides));
}

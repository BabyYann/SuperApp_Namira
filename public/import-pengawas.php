<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Modules\Employee\Models\Staff;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Make sure role pengawas_yayasan exists
$role = Role::firstOrCreate(['name' => 'pengawas_yayasan', 'guard_name' => 'web']);

// Get Kantor Yayasan unit (or first unit as fallback)
$yayasanUnit = Unit::where('code', 'YAYASAN')->first() ?? Unit::first();

$data = [
    [
        'niy' => '2013000101',
        'name' => 'Dr. dr. Mirrah Samiyyah, M.Kes',
        'phone' => '6282141896375',
        'email' => 'mirrahsamiyah@gmail.com',
    ],
    [
        'niy' => '2013000102',
        'name' => 'Nabila Faza, S.E',
        'phone' => '628123508479',
        'email' => 'nabilafaza@gmail.com',
    ],
    [
        'niy' => '2013000103',
        'name' => 'Fara Nadhia, S.T',
        'phone' => '6281217423879',
        'email' => 'farnadhia22@gmail.com',
    ],
    [
        'niy' => '2013000104',
        'name' => 'Mursyid, S.Pd',
        'phone' => '6282332922521',
        'email' => 'foundationnamira@gmail.com',
    ],
    [
        'niy' => '2013000105',
        'name' => 'Dian Mutammima, M.Pd',
        'phone' => '6281335208980',
        'email' => 'tamialbar88@gmail.com',
    ],
    [
        'niy' => '2013000106',
        'name' => 'Ahmad Bahru Salam, S.Pd',
        'phone' => '6285733703990',
        'email' => 'bahrusalamcakul@gmail.com',
    ],
    [
        'niy' => '3190201302',
        'name' => 'Anggun Happy Ananda, S.Pd',
        'phone' => '6285331362000',
        'email' => 'Anggunhappyananda@gmail.com',
    ],
];

echo "<h2>🚀 Memulai Import Akun Pengawas Yayasan...</h2>";
echo "<ul>";

foreach ($data as $item) {
    // 1. Create or Update User
    $user = User::updateOrCreate(
        ['email' => strtolower($item['email'])],
        [
            'name' => $item['name'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]
    );

    // 2. Assign Role Global (pengawas_yayasan)
    setPermissionsTeamId(null);
    $user->syncRoles(['pengawas_yayasan']);

    // 3. Create or Update Staff Profile (store NIY in nip field)
    if ($yayasanUnit) {
        Staff::updateOrCreate(
            ['user_id' => $user->id],
            [
                'unit_id' => $yayasanUnit->id,
                'nip' => $item['niy'],
                'full_name' => $item['name'],
                'phone' => $item['phone'],
                'position' => 'Pengawas Yayasan',
                'is_active' => true,
            ]
        );
    }

    echo "<li>✅ <b>{$item['name']}</b> ({$item['email']}) — NIY: {$item['niy']} — Role: <code>pengawas_yayasan</code></li>";
}

echo "</ul>";
echo "<h3 style='color:green;'>🎉 BERHASIL! Total 7 Akun Pengawas Yayasan Telah Dibuat.</h3>";
echo "<p>Password default untuk semua akun: <code>password</code></p>";

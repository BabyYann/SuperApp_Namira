<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SampleUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Unit-Specific Users (SD Namira)
        $unitSD = Unit::where('name', 'SD Namira')->first();
        
        if ($unitSD) {
            // Guru SD
            $guru = User::firstOrCreate(
                ['email' => 'budiguru@namira.school'],
                [
                    'name' => 'Budi Guru SD',
                    'password' => Hash::make('password'),
                ]
            );
            setPermissionsTeamId($unitSD->id);
            if (!$guru->hasRole('teacher')) {
                $guru->assignRole('teacher');
            }

            // Siswa SD
            $siswa = User::firstOrCreate(
                ['email' => 'anisiswa@namira.school'],
                [
                    'name' => 'Ani Siswa SD',
                    'password' => Hash::make('password'),
                ]
            );
            setPermissionsTeamId($unitSD->id);
             if (!$siswa->hasRole('siswa')) {
                $siswa->assignRole('siswa');
            }

            // Kepala Sekolah SD
            $kepsek = User::firstOrCreate(
                ['email' => 'kepsek.sd@namira.school'],
                [
                    'name' => 'Kepala Sekolah SD Namira',
                    'password' => Hash::make('password'),
                ]
            );
            setPermissionsTeamId($unitSD->id);
            if (!$kepsek->hasRole('kepala_sekolah')) {
                $kepsek->assignRole('kepala_sekolah');
            }
            if (!$kepsek->hasRole('admin_unit')) {
                $kepsek->assignRole('admin_unit');
            }
        }

        // 2. Create Global/Yayasan User
        $unitYayasan = Unit::where('name', 'Kantor Yayasan')->first();

        if ($unitYayasan) {
            // Secretaris Yayasan (Scoped to Kantor Yayasan)
            $sekretaris = User::firstOrCreate(
                ['email' => 'sitisekretaris@namira.school'],
                [
                    'name' => 'Siti Sekretaris',
                    'password' => Hash::make('password'),
                ]
            );
            setPermissionsTeamId($unitYayasan->id);
             if (!$sekretaris->hasRole('admin_yayasan')) {
                $sekretaris->assignRole('admin_yayasan');
            }
        }
        
        // Reset Team ID
        setPermissionsTeamId(null);
    }
}

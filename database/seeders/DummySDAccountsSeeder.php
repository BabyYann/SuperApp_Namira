<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DummySDAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cari Unit SD
        $unitSD = Unit::where('name', 'LIKE', '%SD%')->first() ?? Unit::first();

        if (!$unitSD) {
            $this->command->error('Unit SD tidak ditemukan di database.');
            return;
        }

        $passwordHash = Hash::make('password');

        // ------------------------------------------------------------
        // A. KEPALA SEKOLAH SD
        // ------------------------------------------------------------
        $ksUser = User::firstOrCreate(
            ['email' => 'ks.sd@namira.school'],
            [
                'name'     => 'Kepala Sekolah SD Namira',
                'password' => $passwordHash,
            ]
        );

        setPermissionsTeamId($unitSD->id);
        if (!$ksUser->hasRole('kepala_sekolah')) {
            $ksUser->assignRole('kepala_sekolah');
        }
        if (!$ksUser->hasRole('admin_unit')) {
            $ksUser->assignRole('admin_unit');
        }

        // ------------------------------------------------------------
        // B. GURU SD
        // ------------------------------------------------------------
        $guruUser = User::firstOrCreate(
            ['email' => 'guru.sd@namira.school'],
            [
                'name'     => 'Guru SD Namira',
                'password' => $passwordHash,
            ]
        );

        setPermissionsTeamId($unitSD->id);
        if (!$guruUser->hasRole('teacher')) {
            $guruUser->assignRole('teacher');
        }

        // Simpan Profil Guru di tabel teachers
        Teacher::updateOrCreate(
            ['user_id' => $guruUser->id],
            [
                'unit_id'   => $unitSD->id,
                'nip'       => '199002152015021002',
                'full_name' => 'Guru SD Namira',
                'gender'    => 'L',
                'phone'     => '081234567890',
            ]
        );

        // ------------------------------------------------------------
        // C. SISWA SD
        // ------------------------------------------------------------
        $siswaUser = User::firstOrCreate(
            ['email' => 'siswa.sd@namira.school'],
            [
                'name'     => 'Ahmad Siswa SD',
                'password' => $passwordHash,
            ]
        );

        setPermissionsTeamId($unitSD->id);
        if (!$siswaUser->hasRole('siswa')) {
            $siswaUser->assignRole('siswa');
        }

        // Simpan Profil Siswa di tabel students
        Student::updateOrCreate(
            ['user_id' => $siswaUser->id],
            [
                'unit_id'      => $unitSD->id,
                'nis'          => '5251001',
                'nisn'         => '0081234567',
                'full_name'    => 'Ahmad Siswa SD',
                'gender'       => 'L',
                'parent_phone' => '081987654321',
            ]
        );

        setPermissionsTeamId(null);
    }
}

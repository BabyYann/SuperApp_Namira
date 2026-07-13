<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;

class AcademicProfileSeeder extends Seeder
{
    public function run(): void
    {
        $unitSD = Unit::where('name', 'SD Namira')->first();
        if (!$unitSD) return;

        // 1. Create/Update Teacher Profile for Budi
        $userGuru = User::where('email', 'budiguru@namira.school')->first();
        if ($userGuru) {
            Teacher::updateOrCreate(
                ['user_id' => $userGuru->id],
                [
                    'unit_id' => $unitSD->id,
                    'nip' => '198001012023011001',
                    'full_name' => $userGuru->name,
                    'phone' => '081234567890',
                    'gender' => 'L',
                ]
            );
        }

        // 2. Create/Update Student Profile for Ani
        $userSiswa = User::where('email', 'anisiswa@namira.school')->first();
        if ($userSiswa) {
            Student::updateOrCreate(
                ['user_id' => $userSiswa->id],
                [
                    'unit_id' => $unitSD->id,
                    'nis' => '2023001',
                    'nisn' => '00123456781', // ensure unique/different from create form test
                    'full_name' => $userSiswa->name,
                    'gender' => 'P',
                    'parent_phone' => '089876543210',
                ]
            );
        }
    }
}

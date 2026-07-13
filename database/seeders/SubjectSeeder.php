<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Subject;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define Subjects per Level
        $sdSubjects = [
            // Kelompok A (Wajib)
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI', 'group' => 'A'],
            ['name' => 'Pendidikan Pancasila & Kewarganegaraan', 'code' => 'PPKN', 'group' => 'A'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIN', 'group' => 'A'],
            ['name' => 'Matematika', 'code' => 'MTK', 'group' => 'A'],
            ['name' => 'Ilmu Pengetahuan Alam (IPA)', 'code' => 'IPA', 'group' => 'A'],
            ['name' => 'Ilmu Pengetahuan Sosial (IPS)', 'code' => 'IPS', 'group' => 'A'],
            
            // Kelompok B
            ['name' => 'Seni Budaya & Prakarya (SBdP)', 'code' => 'SBDP', 'group' => 'B'],
            ['name' => 'Pendidikan Jasmani, Olahraga & Kesehatan', 'code' => 'PJOK', 'group' => 'B'],
            
            // Mulok
            ['name' => 'Bahasa Inggris', 'code' => 'BIG', 'group' => 'Mulok'],
            ['name' => 'Bahasa Daerah (Jawa)', 'code' => 'BJW', 'group' => 'Mulok'],
            ['name' => 'Tahfidz Qur\'an', 'code' => 'TFD', 'group' => 'Mulok'],
        ];

        $smpSubjects = [
            // Kelompok A
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI', 'group' => 'A'],
            ['name' => 'Pendidikan Pancasila', 'code' => 'PKN', 'group' => 'A'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIN', 'group' => 'A'],
            ['name' => 'Matematika', 'code' => 'MTK', 'group' => 'A'],
            ['name' => 'Ilmu Pengetahuan Alam (IPA)', 'code' => 'IPA', 'group' => 'A'],
            ['name' => 'Ilmu Pengetahuan Sosial (IPS)', 'code' => 'IPS', 'group' => 'A'],
            ['name' => 'Bahasa Inggris', 'code' => 'BIG', 'group' => 'A'],
            
            // Kelompok B
            ['name' => 'Seni Budaya', 'code' => 'SBD', 'group' => 'B'],
            ['name' => 'Pendidikan Jasmani, Olahraga & Kesehatan', 'code' => 'PJOK', 'group' => 'B'],
            ['name' => 'Prakarya', 'code' => 'PKY', 'group' => 'B'],
            ['name' => 'Informatika', 'code' => 'INF', 'group' => 'B'],

            // Mulok
            ['name' => 'Bahasa Jawa', 'code' => 'BJW', 'group' => 'Mulok'],
            ['name' => 'Tahfidz', 'code' => 'TFD', 'group' => 'Mulok'],
        ];

        // 2. Fetch Units and Seed
        $sdUnits = Unit::where('level', 'SD')->get();
        foreach ($sdUnits as $unit) {
            foreach ($sdSubjects as $subject) {
                Subject::firstOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'name' => $subject['name'],
                    ],
                    [
                        'code' => $subject['code'],
                        'group' => $subject['group'],
                    ]
                );
            }
        }

        $smpUnits = Unit::where('level', 'SMP')->get();
        foreach ($smpUnits as $unit) {
            foreach ($smpSubjects as $subject) {
                Subject::firstOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'name' => $subject['name'],
                    ],
                    [
                        'code' => $subject['code'],
                        'group' => $subject['group'],
                    ]
                );
            }
        }
    }
}

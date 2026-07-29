<?php

namespace Database\Seeders;

use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::firstOrCreate(
            ['name' => '2025/2026'],
            ['semester' => 'Ganjil', 'is_active' => true]
        );
    }
}

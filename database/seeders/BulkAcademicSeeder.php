<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;

class BulkAcademicSeeder extends Seeder
{
    public function run(): void
    {
        // 1. SD Namira
        $unitSD = Unit::where('name', 'SD Namira')->first();
        if ($unitSD) {
            $this->command->info('Seeding SD Namira Data...');
            Teacher::factory()->count(10)->create(['unit_id' => $unitSD->id]);
            Student::factory()->count(30)->create(['unit_id' => $unitSD->id]);
        }

        // 2. SMP Namira
        $unitSMP = Unit::where('name', 'SMP Namira')->first();
        if ($unitSMP) {
            $this->command->info('Seeding SMP Namira Data...');
            Teacher::factory()->count(10)->create(['unit_id' => $unitSMP->id]);
            Student::factory()->count(30)->create(['unit_id' => $unitSMP->id]);
        }
    }
}

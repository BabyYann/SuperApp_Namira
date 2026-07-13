<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Academic\Models\Student;

class FinanceVaSeeder extends Seeder
{
    public function run()
    {
        $students = Student::whereNull('va_number')->orWhere('va_number', '')->get();
        $count = 0;

        foreach ($students as $student) {
            // Determine Identifier (NIS or ID as fallback)
            $identifier = $student->nis;
            if (empty($identifier)) {
                $identifier = str_pad($student->id, 5, '0', STR_PAD_LEFT);
            }

            // Determine Unit Code (2 digits)
            $unitCode = str_pad($student->unit_id, 2, '0', STR_PAD_LEFT);

            // Generate Mock VA: 8888 + Unit + Identifier
            $student->va_number = '8888' . $unitCode . $identifier;
            $student->save();
            $count++;
        }

        $this->command->info("Successfully generated VA Numbers for {$count} students.");
    }
}

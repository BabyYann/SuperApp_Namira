<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Academic Year Exists
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2025/2026'],
            ['semester' => 'Ganjil', 'is_active' => true]
        );

        // 2. Seed SD Classes
        $unitSD = Unit::where('name', 'SD Namira')->first();
        if ($unitSD) {
            $levelsSD = ['1', '2', '3', '4', '5', '6'];
            $sections = ['A', 'B'];

            foreach ($levelsSD as $level) {
                foreach ($sections as $section) {
                    $className = $level . ' ' . $section; // e.g., "1 A"
                    
                    // Find a random teacher to be Wali Kelas
                    $teacher = Teacher::where('unit_id', $unitSD->id)->inRandomOrder()->first();

                    $classroom = Classroom::firstOrCreate(
                        [
                            'unit_id' => $unitSD->id,
                            'academic_year_id' => $academicYear->id,
                            'name' => $className,
                        ],
                        [
                            'level' => $level,
                            'homeroom_teacher_id' => $teacher?->id,
                        ]
                    );

                    // Assign some students to this class
                    $students = Student::where('unit_id', $unitSD->id)
                                       ->whereNull('classroom_id')
                                       ->inRandomOrder()
                                       ->take(5)
                                       ->get();
                                       
                    foreach ($students as $student) {
                        $student->update(['classroom_id' => $classroom->id]);
                    }
                }
            }
        }

        // 3. Seed SMP Classes
        $unitSMP = Unit::where('name', 'SMP Namira')->first();
        if ($unitSMP) {
            $levelsSMP = ['7', '8', '9'];
            $sections = ['A', 'B']; // e.g. 7A, 7B

            foreach ($levelsSMP as $level) {
                foreach ($sections as $section) {
                    $className = $level . $section; // e.g., "7A"
                    
                    $teacher = Teacher::where('unit_id', $unitSMP->id)->inRandomOrder()->first();

                    $classroom = Classroom::firstOrCreate(
                        [
                            'unit_id' => $unitSMP->id,
                            'academic_year_id' => $academicYear->id,
                            'name' => $className,
                        ],
                        [
                            'level' => $level,
                            'homeroom_teacher_id' => $teacher?->id,
                        ]
                    );

                    // Assign some students
                    $students = Student::where('unit_id', $unitSMP->id)
                                       ->whereNull('classroom_id')
                                       ->inRandomOrder()
                                       ->take(5)
                                       ->get();

                    foreach ($students as $student) {
                        $student->update(['classroom_id' => $classroom->id]);
                    }
                }
            }
        }
    }
}

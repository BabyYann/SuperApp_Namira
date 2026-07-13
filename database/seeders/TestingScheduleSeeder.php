<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\ClassSchedule;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing schedules to avoid duplicates/clashes for testing
        // DB::table('class_schedules')->truncate(); // Optional: Keep existing if any

        $units = Unit::all();

        foreach ($units as $unit) {
            $classrooms = Classroom::where('unit_id', $unit->id)->get();
            $subjects = Subject::where('unit_id', $unit->id)->get();
            $teachers = Teacher::where('unit_id', $unit->id)->get();

            if ($classrooms->isEmpty() || $subjects->isEmpty() || $teachers->isEmpty()) {
                continue;
            }

            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $timeSlots = [
                ['07:00', '08:00'],
                ['08:00', '09:00'],
                ['09:30', '10:30'], // Break 09:00-09:30
                ['10:30', '11:30'],
            ];

            foreach ($classrooms as $classroom) {
                foreach ($days as $day) {
                    // Shuffle subjects and teachers for variety
                    $shuffledSubjects = $subjects->shuffle();
                    $shuffledTeachers = $teachers->shuffle();

                    foreach ($timeSlots as $index => $slot) {
                        if ($shuffledSubjects->isEmpty()) break;

                        $subject = $shuffledSubjects->pop();
                        $teacher = $shuffledTeachers->isNotEmpty() ? $shuffledTeachers->random() : $teachers->first();

                        ClassSchedule::create([
                            'unit_id' => $unit->id,
                            'classroom_id' => $classroom->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'day' => $day,
                            'start_time' => $slot[0],
                            'end_time' => $slot[1],
                        ]);
                    }
                }
            }
        }
    }
}

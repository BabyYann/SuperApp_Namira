<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nis' => $this->faker->unique()->numerify('2024####'),
            'nisn' => $this->faker->unique()->numerify('00########'),
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'parent_name' => $this->faker->name(),
            'parent_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'pob' => $this->faker->city(),
            'dob' => $this->faker->date(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Student $student) {
            $user = $student->user;

            // Standardize Email
            $user->update([
                'email' => strtolower(str_replace(' ', '.', $student->full_name)) . '.' . $student->id . '@siswa.namira.school',
                'name' => $student->full_name,
            ]);

            // Assign Role Scoped to Unit
            if ($student->unit_id) {
                setPermissionsTeamId($student->unit_id);
                $user->assignRole('siswa');
                setPermissionsTeamId(null); // Reset
            }
        });
    }
}

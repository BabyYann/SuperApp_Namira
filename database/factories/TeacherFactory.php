<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => $this->faker->unique()->numerify('19##########'),
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'phone' => $this->faker->phoneNumber(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Teacher $teacher) {
            $user = $teacher->user;
            
            // Standardize Email
            $user->update([
                'email' => strtolower(str_replace(' ', '.', $teacher->full_name)) . '.' . $teacher->id . '@guru.namira.school',
                'name'  => $teacher->full_name,
            ]);

            // Assign Role Scoped to Unit
            if ($teacher->unit_id) {
                setPermissionsTeamId($teacher->unit_id);
                $user->assignRole('guru');
                setPermissionsTeamId(null); // Reset
            }
        });
    }
}

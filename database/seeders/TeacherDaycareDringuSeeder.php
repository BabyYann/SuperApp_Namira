<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherDaycareDringuSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::whereIn('code', ['DAY-DRI', 'TPA-DRI', 'DAY'])
            ->orWhere('name', 'LIKE', '%Day%')
            ->orWhere('name', 'LIKE', '%TPA%')
            ->first();
        if (!$unit) {
            echo "Unit Day Care tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Nabilla Ilamalia',
    'NIY' => '',
    'email' => 'nabilla.ilamalia@namira.school',
    'no hp' => '',
    'unit' => 'TPA NAMIRA DRINGU',
  ),
  1 => 
  array (
    'nama' => 'Silvia Anggrayni',
    'NIY' => '',
    'email' => 'silvia@namira.school',
    'no hp' => '',
    'unit' => 'TPA NAMIRA DRINGU',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no hp']);
            $gender = 'P';

            $password = $niy ? $niy : 'guru123';

            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
            }

            if ($roleId) {
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                    'team_id' => $unitId,
                ]);
            }

            Teacher::updateOrCreate([
                'user_id' => $user->id,
                'unit_id' => $unitId,
            ], [
                'full_name' => $name,
                'nip' => $niy,
                'gender' => $gender,
                'phone' => $phone,
            ]);
        }
        echo "Berhasil mengimpor 2 Staf Day Care Dringu!\n";
    }
}

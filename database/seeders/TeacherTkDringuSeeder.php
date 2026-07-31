<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherTkDringuSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'TK-DRI')
            ->orWhere('name', 'LIKE', '%TK%Dringu%')
            ->first();
        if (!$unit) {
            echo "Unit TK Namira Dringu tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Surya Dini Yusonia',
    'NIY' => '',
    'email' => 'surya@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  1 => 
  array (
    'nama' => 'Selawati Masruroh',
    'NIY' => '',
    'email' => 'selawati@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  2 => 
  array (
    'nama' => 'Helyas Vintan Agesti',
    'NIY' => '',
    'email' => 'helyas@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  3 => 
  array (
    'nama' => 'Islavia Feria Devi',
    'NIY' => '',
    'email' => 'islavia@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  4 => 
  array (
    'nama' => 'Ruli Puji Lestari',
    'NIY' => '',
    'email' => 'ruli@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  5 => 
  array (
    'nama' => 'Salfiya',
    'NIY' => '',
    'email' => 'salfiya@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  6 => 
  array (
    'nama' => 'Nabila Cipta Navira Savitri',
    'NIY' => '',
    'email' => 'nabila@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  7 => 
  array (
    'nama' => 'Dwi Ayu Rosidah',
    'NIY' => '',
    'email' => 'dwi.rosidah@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  8 => 
  array (
    'nama' => 'Deswinta Febrianti',
    'NIY' => '',
    'email' => 'deswinta@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
  9 => 
  array (
    'nama' => 'Riska Ariyani Pratiwi',
    'NIY' => '',
    'email' => 'riska@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA DRINGU',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no_hp']);
            $gender = 'P'; // All female names listed

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
        echo "Berhasil mengimpor 10 Guru TK Namira Dringu!\n";
    }
}

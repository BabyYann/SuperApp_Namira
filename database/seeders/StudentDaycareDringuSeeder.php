<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentDaycareDringuSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'DAY')
            ->orWhere('name', 'LIKE', '%Day Care%')
            ->first();

        if (!$unit) {
            echo "Unit Day Care (DAY) tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'siswa')->orWhere('name', 'student')->first();
        $roleId = $role ? $role->id : null;

        $studentsData = array (
  0 => 
  array (
    'full_name' => 'Muhammad Hannan Al Faris',
    'nickname' => 'Hannan',
    'pob' => 'Probolinggo',
    'dob' => '2025-10-01',
    'address' => 'Jl. Raden Soejoso no 279, Kalirejo, Dringu',
    'parent_name' => 'Agus Supriyantoro',
    'email' => 'muhammad@namira.school',
  ),
  1 => 
  array (
    'full_name' => 'Aisyah Syareena Zafira',
    'nickname' => 'Reena',
    'pob' => 'Probolinggo',
    'dob' => '2024-02-04',
    'address' => 'Jl. Slamet Riyadi, Kanigaran',
    'parent_name' => 'Muhammad Iqbal Maulana',
    'email' => 'aisyah@namira.school',
  ),
  2 => 
  array (
    'full_name' => 'Muhammad Bintang Sirius Bena Idhang',
    'nickname' => 'Bintang',
    'pob' => 'Probolinggo',
    'dob' => '2022-02-12',
    'address' => 'Triwung Lor, Kademangan',
    'parent_name' => 'Adi Tri Bima Soleh',
    'email' => 'muhammad.2@namira.school',
  ),
  3 => 
  array (
    'full_name' => 'Jelita Nada Delingga',
    'nickname' => 'Jelita',
    'pob' => 'Probolinggo',
    'dob' => '2020-12-14',
    'address' => 'Tongas wetan, Tongas',
    'parent_name' => 'Angga Prasetiyo',
    'email' => 'jelita@namira.school',
  ),
  4 => 
  array (
    'full_name' => 'Adzril Rafif Alfarezi Harianto',
    'nickname' => 'Adzril',
    'pob' => 'Probolinggo',
    'dob' => '2021-09-26',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Didik Harianto',
    'email' => 'adzril@namira.school',
  ),
  5 => 
  array (
    'full_name' => 'Najwa Fairuz Korina Salsabika',
    'nickname' => 'Najwa',
    'pob' => 'Probolinggo',
    'dob' => '2020-05-31',
    'address' => 'Lidahwetan, Lakarsantri',
    'parent_name' => 'Aflakh',
    'email' => 'najwa@namira.school',
  ),
  6 => 
  array (
    'full_name' => 'Unna Mikhayla Azzahra',
    'nickname' => 'Unna',
    'pob' => 'Lumajang',
    'dob' => '2020-12-27',
    'address' => 'Graha Pabean Asri',
    'parent_name' => 'Fandik Sanjaya',
    'email' => 'unna@namira.school',
  ),
);

        foreach ($studentsData as $item) {
            $email = trim($item['email']);
            $name = trim($item['full_name']);
            $pob = trim($item['pob']);
            $dob = !empty($item['dob']) ? $item['dob'] : null;
            $address = trim($item['address']);
            $parentName = trim($item['parent_name']);

            $password = 'siswa123';

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

            Student::updateOrCreate([
                'user_id' => $user->id,
                'unit_id' => $unitId,
            ], [
                'full_name' => $name,
                'pob' => $pob,
                'dob' => $dob,
                'address' => $address,
                'parent_name' => $parentName,
            ]);
        }
        echo "Berhasil mengimpor 7 Siswa Day Care Namira Dringu!\n";
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherPaudKraksaanSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::whereIn('code', ['KB-KRA', 'PAUD-KRA'])
            ->orWhere('name', 'LIKE', '%KB%Kraksaan%')
            ->orWhere('name', 'LIKE', '%PAUD%Kraksaan%')
            ->first();
        if (!$unit) {
            echo "Unit KB / PAUD Namira Kraksaan tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Nur Asia, S. Pd',
    'NIY' => '11931603',
    'email' => 'asianur90@gmail.com',
    'no hp' => '0823 3051 2794',
    'unit' => 'PG Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  1 => 
  array (
    'nama' => 'Dwi Indah Puji Astutik, S. Ap',
    'NIY' => '11951905',
    'email' => 'dwieindah13579@gmail.com',
    'no hp' => '0852 3610 8090',
    'unit' => 'PG Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  2 => 
  array (
    'nama' => 'Tania Nafisah Sehba,',
    'NIY' => '11022407',
    'email' => 'tanianafisah7@gmail.com',
    'no hp' => '081515288041',
    'unit' => 'PG Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  3 => 
  array (
    'nama' => 'Durrotun Nafilah',
    'NIY' => '11022408',
    'email' => 'durrotunnafilah80@gmail.com',
    'no hp' => '082359339513',
    'unit' => '',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  4 => 
  array (
    'nama' => 'Nur Dina Maulida',
    'NIY' => '',
    'email' => 'nurdinamaulidia16@gmail.com',
    'no hp' => '0822 3234 4035',
    'unit' => '',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  5 => 
  array (
    'nama' => 'Ainul Inayah',
    'NIY' => '',
    'email' => 'inayahainul36@gmail.com',
    'no hp' => '082233678276',
    'unit' => '',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  6 => 
  array (
    'nama' => 'Inka Maulidina',
    'NIY' => '',
    'email' => 'inka@namira.school',
    'no hp' => '',
    'unit' => '',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  7 => 
  array (
    'nama' => 'Istifadah',
    'NIY' => '',
    'email' => 'istifadah@namira.school',
    'no hp' => '',
    'unit' => '',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  8 => 
  array (
    'nama' => 'Laily Faridatul Awalin',
    'NIY' => '',
    'email' => 'laily@namira.school',
    'no hp' => '',
    'unit' => '',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no hp']);
            $gender = 'P'; // All PAUD teachers listed are female

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
        echo "Berhasil mengimpor 9 Guru PAUD Namira Kraksaan!\n";
    }
}

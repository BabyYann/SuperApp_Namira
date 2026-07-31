<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherDaycareSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::whereIn('code', ['DAY', 'TPA', 'DAYCARE'])
            ->orWhere('name', 'LIKE', '%Day%')
            ->orWhere('name', 'LIKE', '%TPA%')
            ->first();
        if (!$unit) {
            echo "Unit Day Care / TPA tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Badriyah',
    'NIY' => '91901301',
    'email' => 'badriyah@namira.school',
    'no hp' => '',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  1 => 
  array (
    'nama' => 'Murniati, S.M',
    'NIY' => '91971602',
    'email' => 'murniati@namira.school',
    'no hp' => '082315022331',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  2 => 
  array (
    'nama' => 'Dwi Cahya Ningsih, S.M',
    'NIY' => '91971703',
    'email' => 'dwi.cahya@namira.school',
    'no hp' => '',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  3 => 
  array (
    'nama' => 'Nurul Jannah',
    'NIY' => '91901704',
    'email' => 'nuruljannah5486@gmail.com',
    'no hp' => '082330503892',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  4 => 
  array (
    'nama' => 'Toni Arso Akbar',
    'NIY' => '92801907',
    'email' => 'toni@namira.school',
    'no hp' => '085232628298',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  5 => 
  array (
    'nama' => 'Siti Nura',
    'NIY' => '91012008',
    'email' => 'noragebangan@gmail.com',
    'no hp' => '085237183429',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  6 => 
  array (
    'nama' => 'Mahfud Diono',
    'NIY' => '',
    'email' => 'mahfud@namira.school',
    'no hp' => '',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  7 => 
  array (
    'nama' => 'Indi Desi Hasanah',
    'NIY' => '91042310',
    'email' => 'iindi4803@gmail.com',
    'no hp' => '085755087910',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  8 => 
  array (
    'nama' => 'Joni',
    'NIY' => '',
    'email' => 'joni@namira.school',
    'no hp' => '',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  9 => 
  array (
    'nama' => 'Alifia Nailus Dzikria',
    'NIY' => '',
    'email' => 'alifia@namira.school',
    'no hp' => '',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  10 => 
  array (
    'nama' => 'Sofiatun Hasanah',
    'NIY' => '91052313',
    'email' => 'sofiatulhasanah361@gmail.com',
    'no hp' => '082325750806',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  11 => 
  array (
    'nama' => 'Rizki Ayu Syahtika',
    'NIY' => '91992514',
    'email' => 'rizkyayusyahtika@gmail.com',
    'no hp' => '085935094033',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  12 => 
  array (
    'nama' => 'Nawal Savina',
    'NIY' => '91062515',
    'email' => 'nawalsavina02@gmail.com',
    'no hp' => '089517033430',
    'unit' => 'TPA Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no hp']);
            $gender = str_contains(strtolower($name), 'joni') || str_contains(strtolower($name), 'toni') || str_contains(strtolower($name), 'mahfud') ? 'L' : 'P';

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
        echo "Berhasil mengimpor 13 Guru/Staf Day Care (TPA Kraksaan)!\n";
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherPavlovSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::whereIn('code', ['PAVLOV', 'PAV', 'PAVLOV-KRA'])
            ->orWhere('name', 'LIKE', '%Pavlov%')
            ->first();
        if (!$unit) {
            echo "Unit Pavlov Center tidak ditemukan, membuat unit Pavlov Center...\n";
            $unit = Unit::create([
                'name' => 'Pavlov Center',
                'code' => 'PAVLOV',
                'category' => 'non-formal',
                'level' => 'NON-FORMAL'
            ]);
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Inayah',
    'NIY' => '41841902',
    'email' => 'inayahzamzami84@gmail.com',
    'no_hp' => '085234293443',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  1 => 
  array (
    'nama' => 'Ummil Atiqoh',
    'NIY' => '41931903',
    'email' => 'ummilatiqoh@gmail.com',
    'no_hp' => '081357897098',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  2 => 
  array (
    'nama' => 'Fatimatus Sahro',
    'NIY' => '41992105',
    'email' => 'fatimatussahro1011@gmail.com',
    'no_hp' => '085269396801',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  3 => 
  array (
    'nama' => 'Khairun Nisak',
    'NIY' => '42972106',
    'email' => 'anisnisak422@gmail.com',
    'no_hp' => '082330623382',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  4 => 
  array (
    'nama' => 'Erina Kholidhatus Sholihah',
    'NIY' => '41012207',
    'email' => 'erinasholihah@gmail.com',
    'no_hp' => '085607026588',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  5 => 
  array (
    'nama' => 'Siti Nurhalisah',
    'NIY' => '41012211',
    'email' => 'sitinurkholisah143@gmail.com',
    'no_hp' => '083892373324',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  6 => 
  array (
    'nama' => 'Anis Srifa Ambami',
    'NIY' => '41002313',
    'email' => 'anisrifaambami@gmail.com',
    'no_hp' => '089520864248',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  7 => 
  array (
    'nama' => 'Aprilia Safianti',
    'NIY' => '41992314',
    'email' => 'apriliasafianti4@gmail.com',
    'no_hp' => '082244063358',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  8 => 
  array (
    'nama' => 'Nadita Arifianti Meilani',
    'NIY' => '41052315',
    'email' => 'naditarifiantimeilani@gmail.com',
    'no_hp' => '085601612322',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  9 => 
  array (
    'nama' => 'Lailatul Mukarromah',
    'NIY' => '41002317',
    'email' => 'lailamukarromah44@gmail.com',
    'no_hp' => '082247317975',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  10 => 
  array (
    'nama' => 'Weni Mushonifah',
    'NIY' => '41002318',
    'email' => 'wenimusonnifah@gmail.com',
    'no_hp' => '085850669118',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  11 => 
  array (
    'nama' => 'Nadhifatul Qolbiyah',
    'NIY' => '41002419',
    'email' => 'nadhifatulqolbiyah622@gmail.com',
    'no_hp' => '082334062180',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  12 => 
  array (
    'nama' => 'Kurratul Aini',
    'NIY' => '41992420',
    'email' => 'kurratulaini145@yahoo.com',
    'no_hp' => '082228547474',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  13 => 
  array (
    'nama' => 'Inayatul Maula',
    'NIY' => '41012422',
    'email' => 'inytl.maula18@gmail.com',
    'no_hp' => '082245812260',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  14 => 
  array (
    'nama' => 'Ike Putri Nur Aida',
    'NIY' => '41012523',
    'email' => 'ikeputrinuraida@gmail.com',
    'no_hp' => '085746202577',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  15 => 
  array (
    'nama' => 'Radita Rodiana',
    'NIY' => '41022524',
    'email' => 'raditarodiana85@gmail.com',
    'no_hp' => '082245812759',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  16 => 
  array (
    'nama' => 'Qurrotu Aini',
    'NIY' => '41022525',
    'email' => 'qurrotu.aini.004@gmail.com',
    'no_hp' => '082140263050',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  17 => 
  array (
    'nama' => 'Hairul Zahroni',
    'NIY' => '',
    'email' => 'hairul@namira.school',
    'no_hp' => '',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  18 => 
  array (
    'nama' => 'Intan Ike Susanti',
    'NIY' => '41032527',
    'email' => 'intanintan1751@gmail.com',
    'no_hp' => '085234501158',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  19 => 
  array (
    'nama' => 'Emilia Monica Ramlan',
    'NIY' => '41212528',
    'email' => 'emiliam.ramlan@gmail.com',
    'no_hp' => '085258835152',
    'unit' => 'Pavlov Namira Kraksaan',
    'ket' => '',
    'keterangan_email' => 'Email asli',
  ),
  20 => 
  array (
    'nama' => 'Fitriyah Rizki Amalia',
    'NIY' => '',
    'email' => 'fitriyah@namira.school',
    'no_hp' => '',
    'unit' => 'pavlov',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  21 => 
  array (
    'nama' => 'Masruhin',
    'NIY' => '',
    'email' => 'masruhin@namira.school',
    'no_hp' => '',
    'unit' => 'pavlov',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no_hp']);
            $gender = str_contains(strtolower($name), 'masruhin') || str_contains(strtolower($name), 'hairul') ? 'L' : 'P';

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
        echo "Berhasil mengimpor 22 Guru Pavlov Center!\n";
    }
}

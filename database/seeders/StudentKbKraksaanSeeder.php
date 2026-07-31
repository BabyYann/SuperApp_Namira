<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentKbKraksaanSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'KB-KRA')
            ->orWhere(function ($q) {
                $q->where('name', 'LIKE', '%KB%')->where('name', 'LIKE', '%Kraksaan%');
            })
            ->first();

        if (!$unit) {
            echo "Unit KB Namira Kraksaan (KB-KRA) tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'siswa')->orWhere('name', 'student')->first();
        $roleId = $role ? $role->id : null;

        $studentsData = array (
  0 => 
  array (
    'full_name' => 'Afrita Kirana Putri',
    'email' => 'afrita@namira.school',
  ),
  1 => 
  array (
    'full_name' => 'Afyaz Zevanya Al Asyraf',
    'email' => 'afyaz@namira.school',
  ),
  2 => 
  array (
    'full_name' => 'Ahmad Farhan Al Haq',
    'email' => 'ahmad@namira.school',
  ),
  3 => 
  array (
    'full_name' => 'Alfahreezan Emran Elshodiq',
    'email' => 'alfahreezan@namira.school',
  ),
  4 => 
  array (
    'full_name' => 'Andzelika Nadhira A.M',
    'email' => 'andzelika@namira.school',
  ),
  5 => 
  array (
    'full_name' => 'Asma Syahidah Amanina',
    'email' => 'asma@namira.school',
  ),
  6 => 
  array (
    'full_name' => 'Athirah Samahita Yusuf',
    'email' => 'athirah@namira.school',
  ),
  7 => 
  array (
    'full_name' => 'Berlian Ramadhani Wibisono',
    'email' => 'berlian@namira.school',
  ),
  8 => 
  array (
    'full_name' => 'Byantara Abimana Pradipta',
    'email' => 'byantara@namira.school',
  ),
  9 => 
  array (
    'full_name' => 'Eliza Benazir Kamal',
    'email' => 'eliza@namira.school',
  ),
  10 => 
  array (
    'full_name' => 'Emir Zafran',
    'email' => 'emir@namira.school',
  ),
  11 => 
  array (
    'full_name' => 'Evan Ditia Aditama',
    'email' => 'evan@namira.school',
  ),
  12 => 
  array (
    'full_name' => 'Fabian Muhammad A.',
    'email' => 'fabian@namira.school',
  ),
  13 => 
  array (
    'full_name' => 'Fathan Athalla Fathurrahman',
    'email' => 'fathan@namira.school',
  ),
  14 => 
  array (
    'full_name' => 'Fazeela Naira',
    'email' => 'fazeela@namira.school',
  ),
  15 => 
  array (
    'full_name' => 'Fazira Noura Savrinadeya',
    'email' => 'fazira@namira.school',
  ),
  16 => 
  array (
    'full_name' => 'Haninah Abqoriyah',
    'email' => 'haninah@namira.school',
  ),
  17 => 
  array (
    'full_name' => 'Jihan Mufidah',
    'email' => 'jihan@namira.school',
  ),
  18 => 
  array (
    'full_name' => 'Julian Qyara Humaira Prasetya',
    'email' => 'julian@namira.school',
  ),
  19 => 
  array (
    'full_name' => 'Kanaya Kayshila M',
    'email' => 'kanaya@namira.school',
  ),
  20 => 
  array (
    'full_name' => 'Kavya Zaahira Excellia Syaban',
    'email' => 'kavya@namira.school',
  ),
  21 => 
  array (
    'full_name' => 'Khaivan Archie Aldabi',
    'email' => 'khaivan@namira.school',
  ),
  22 => 
  array (
    'full_name' => 'Kyan Alvarendra Wicaksono',
    'email' => 'kyan@namira.school',
  ),
  23 => 
  array (
    'full_name' => 'Layzal Atthariz Umam',
    'email' => 'layzal@namira.school',
  ),
  24 => 
  array (
    'full_name' => 'M.Alvaro Nur Rayyanza',
    'email' => 'malvaro@namira.school',
  ),
  25 => 
  array (
    'full_name' => 'Mafaza Hilya Sholehah Hasby',
    'email' => 'mafaza@namira.school',
  ),
  26 => 
  array (
    'full_name' => 'Maulana Hasan Habiburrohman',
    'email' => 'maulana@namira.school',
  ),
  27 => 
  array (
    'full_name' => 'Mazaya Calula Dara Almahyra',
    'email' => 'mazaya@namira.school',
  ),
  28 => 
  array (
    'full_name' => 'Mikhayla Keysha Azzahra',
    'email' => 'mikhayla@namira.school',
  ),
  29 => 
  array (
    'full_name' => 'Mochammad Algaf Ghaitsan',
    'email' => 'mochammad@namira.school',
  ),
  30 => 
  array (
    'full_name' => 'Mohammad Faroby Ar Ramdany',
    'email' => 'mohammad@namira.school',
  ),
  31 => 
  array (
    'full_name' => 'Muhammad Arkana Atharazka Wicaksono',
    'email' => 'muhammad@namira.school',
  ),
  32 => 
  array (
    'full_name' => 'Muhammad Arsya',
    'email' => 'muhammad.2@namira.school',
  ),
  33 => 
  array (
    'full_name' => 'Muhammad Gibran Romadhoni',
    'email' => 'muhammad.3@namira.school',
  ),
  34 => 
  array (
    'full_name' => 'Muhammad Hasan Malik',
    'email' => 'muhammad.4@namira.school',
  ),
  35 => 
  array (
    'full_name' => 'Muhammad Umar Alfaruq',
    'email' => 'muhammad.5@namira.school',
  ),
  36 => 
  array (
    'full_name' => 'Nadine Salsabila Azzahra',
    'email' => 'nadine@namira.school',
  ),
  37 => 
  array (
    'full_name' => 'Nadisya Khanzara Elshanum',
    'email' => 'nadisya@namira.school',
  ),
  38 => 
  array (
    'full_name' => 'Qianna Arumi Humaira Arrasy',
    'email' => 'qianna@namira.school',
  ),
  39 => 
  array (
    'full_name' => 'Rafan Daniyal Haiti',
    'email' => 'rafan@namira.school',
  ),
  40 => 
  array (
    'full_name' => 'Raihana Amira Shanum',
    'email' => 'raihana@namira.school',
  ),
  41 => 
  array (
    'full_name' => 'Rania Ayda Mehar',
    'email' => 'rania@namira.school',
  ),
  42 => 
  array (
    'full_name' => 'Rayyan Rafasya Habibi',
    'email' => 'rayyan@namira.school',
  ),
  43 => 
  array (
    'full_name' => 'Sabitah Gantari A',
    'email' => 'sabitah@namira.school',
  ),
  44 => 
  array (
    'full_name' => 'Syafino Ersya Abayomi',
    'email' => 'syafino@namira.school',
  ),
  45 => 
  array (
    'full_name' => 'Tsabit Rezildan Al Khawarizmi',
    'email' => 'tsabit@namira.school',
  ),
  46 => 
  array (
    'full_name' => 'Veerendra Kaivan Abrisham',
    'email' => 'veerendra@namira.school',
  ),
  47 => 
  array (
    'full_name' => 'Zahira Hikmah',
    'email' => 'zahira@namira.school',
  ),
  48 => 
  array (
    'full_name' => 'Zaskia Fina Sholihatunnisa',
    'email' => 'zaskia@namira.school',
  ),
  49 => 
  array (
    'full_name' => 'Zea Rizkya Shaaira A',
    'email' => 'zea@namira.school',
  ),
);

        foreach ($studentsData as $item) {
            $email = trim($item['email']);
            $name = trim($item['full_name']);

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
            ]);
        }
        echo "Berhasil mengimpor 50 Siswa KB Namira Kraksaan!\n";
    }
}

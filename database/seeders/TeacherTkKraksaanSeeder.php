<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherTkKraksaanSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'TK-KRA')->orWhere('name', 'LIKE', '%TK%Kraksaan%')->first();
        if (!$unit) {
            echo "Unit TK Namira Kraksaan tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Eva Maghfirah, S. Pd',
    'NIY' => '21761301',
    'email' => 'firah.ifamaghfirah@gmail.com',
    'no_hp' => '0852 5728 2845',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  1 => 
  array (
    'nama' => 'Oemi Maktoem, S.Pd',
    'NIY' => '21881402',
    'email' => 'verinmaktumah@gmail.com',
    'no_hp' => '0823 3043 6690',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  2 => 
  array (
    'nama' => 'Siti Fatimah',
    'NIY' => '21951403',
    'email' => 'sfatimah8810@gmail.com',
    'no_hp' => '0823 3094 7254',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  3 => 
  array (
    'nama' => 'Mustika Ratu Ningsih, S. Pd',
    'NIY' => '21901504',
    'email' => 'khusnulkhotimahtika@gmail.com',
    'no_hp' => '0822 5788 7417',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  4 => 
  array (
    'nama' => 'Nur Istiqomatul Firdausiah, S.M',
    'NIY' => '22961505',
    'email' => 'nur@namira.school',
    'no_hp' => '0823 0231 3443',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  5 => 
  array (
    'nama' => 'Ayu Mayang Dini, S.S',
    'NIY' => '21901506',
    'email' => 'ayu@namira.school',
    'no_hp' => '0823 3877 8377',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  6 => 
  array (
    'nama' => 'Nita Lia Adini Dwi M, S.Psi',
    'NIY' => '21901607',
    'email' => 'nitaliaadinia@gmail.com',
    'no_hp' => '0821 4081 2315',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  7 => 
  array (
    'nama' => 'Halifah',
    'NIY' => '22721608',
    'email' => 'halifah@namira.school',
    'no_hp' => '0852 3100 4327',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  8 => 
  array (
    'nama' => 'Kaulam Ma’rufa, S.Pd',
    'NIY' => '21961609',
    'email' => 'marufakaulam@gmail.com',
    'no_hp' => '0822 3504 2196',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  9 => 
  array (
    'nama' => 'Rosyidah, S. Pd',
    'NIY' => '21981710',
    'email' => 'rosyidah@namira.school',
    'no_hp' => '0823 3879 5422',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  10 => 
  array (
    'nama' => 'Ucik Roudlo’an Ahada Sholeh',
    'NIY' => '21971711',
    'email' => 'ucik@namira.school',
    'no_hp' => '0822 2828 0290',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  11 => 
  array (
    'nama' => 'Astiya, S.Pd',
    'NIY' => '21951712',
    'email' => 'mutyasayang88@gmail.com',
    'no_hp' => '0822 6150 4809',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  12 => 
  array (
    'nama' => 'Muh. Muhyi',
    'NIY' => '22911713',
    'email' => 'uyichan@gmail.com',
    'no_hp' => '0822 4557 3416',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  13 => 
  array (
    'nama' => 'Intan Pradasari',
    'NIY' => '21961914',
    'email' => 'ienthanchupchuphu@gmail.com',
    'no_hp' => '0812 7458 7480',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  14 => 
  array (
    'nama' => 'Umroh, S.Pd',
    'NIY' => '21941915',
    'email' => 'umroh4016@gmail.com',
    'no_hp' => '0822 7152 1781',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  15 => 
  array (
    'nama' => 'Nur Dina Maulidia',
    'NIY' => '22011916',
    'email' => 'nurdinamaulidia16@gmail.com',
    'no_hp' => '0822 3234 4035',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  16 => 
  array (
    'nama' => 'Rafi Firman Maulana',
    'NIY' => '22961917',
    'email' => 'rafi@namira.school',
    'no_hp' => '0812 3642 3369',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  17 => 
  array (
    'nama' => 'Hanina',
    'NIY' => '21002018',
    'email' => 'hnina720@gmail.com',
    'no_hp' => '0896 5639 0999',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  18 => 
  array (
    'nama' => 'Hermanto',
    'NIY' => '22012119',
    'email' => 'hermanto@namira.school',
    'no_hp' => '082333208263',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  19 => 
  array (
    'nama' => 'Ifa Maghfiroh, S.E',
    'NIY' => '21992220',
    'email' => 'ifamagfiroh26@gmail.com',
    'no_hp' => '083857661242',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  20 => 
  array (
    'nama' => 'Ainul Inayah',
    'NIY' => '22982021',
    'email' => 'ainul@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  21 => 
  array (
    'nama' => 'Hidayatul Fitrih',
    'NIY' => '21992322',
    'email' => 'hidayatul@namira.school',
    'no_hp' => '081233794454',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  22 => 
  array (
    'nama' => 'Mainumah',
    'NIY' => '22691823',
    'email' => 'fsatya586@gmail.com',
    'no_hp' => '082331530162',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  23 => 
  array (
    'nama' => 'Alferiyan',
    'NIY' => '21052424',
    'email' => 'alferiyan@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  24 => 
  array (
    'nama' => 'Fitri Ayu, S S.Ag',
    'NIY' => '22022425',
    'email' => 'fitriayu309@gmail.com',
    'no_hp' => '085708196358',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  25 => 
  array (
    'nama' => 'Moh. Risqi Ainul Yakin',
    'NIY' => '21062426',
    'email' => 'ikyyyriski151@gmail.com',
    'no_hp' => '082315528639',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  26 => 
  array (
    'nama' => 'Avin Maulana',
    'NIY' => '21052427',
    'email' => 'afinimaulana690@gmail.com',
    'no_hp' => '081389801968',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  27 => 
  array (
    'nama' => 'Anisah',
    'NIY' => '',
    'email' => 'anisah@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
  28 => 
  array (
    'nama' => 'Ika Nur Arifah M',
    'NIY' => '',
    'email' => 'ika@namira.school',
    'no_hp' => '',
    'unit' => 'TK NAMIRA KRAKSAAN',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no_hp']);
            $gender = str_contains(strtolower($name), 'muh') || str_contains(strtolower($name), 'rafi') || str_contains(strtolower($name), 'hermanto') || str_contains(strtolower($name), 'alferiyan') || str_contains(strtolower($name), 'risqi') || str_contains(strtolower($name), 'avin') ? 'L' : 'P';

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
        echo "Berhasil mengimpor 29 Guru TK Namira Kraksaan!\n";
    }
}

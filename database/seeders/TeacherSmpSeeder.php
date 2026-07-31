<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherSmpSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'SMP')->orWhere('name', 'LIKE', '%SMP%')->first();
        if (!$unit) {
            echo "Unit SMP Namira tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Fajar Adi Pamungkas, S.Pd.',
    'NIY' => '91891909',
    'email' => 'pamungkas.fajar26@gmail.com',
    'no hp' => '082338398026',
    'unit' => 'SMP NAMIRA',
  ),
  1 => 
  array (
    'nama' => 'Rima Kesuma, S.Si.',
    'NIY' => '91821903',
    'email' => 'ummififa31@gmail.com',
    'no hp' => '082280091982',
    'unit' => 'SMP NAMIRA',
  ),
  2 => 
  array (
    'nama' => 'Erna Junaidah, S.Pd.',
    'NIY' => '91921904',
    'email' => 'ernaidah@gmail.com',
    'no hp' => '082301281263',
    'unit' => 'SMP NAMIRA',
  ),
  3 => 
  array (
    'nama' => 'Anik Darwati, S.Pd.I.',
    'NIY' => '91861913',
    'email' => 'anikdarwati48@gmail.com',
    'no hp' => '085236418848',
    'unit' => 'SMP NAMIRA',
  ),
  4 => 
  array (
    'nama' => 'Wahyu Agus Heriadi',
    'NIY' => '92721910',
    'email' => 'heriadi15agustus@gmail.com',
    'no hp' => '081336237717',
    'unit' => 'SMP NAMIRA',
  ),
  5 => 
  array (
    'nama' => 'Muriyanto',
    'NIY' => '92591919',
    'email' => 'muriyanto@namira.school',
    'no hp' => '085253611643',
    'unit' => 'SMP NAMIRA',
  ),
  6 => 
  array (
    'nama' => 'Sinta Pratiwi, S.Pd.',
    'NIY' => '91951917',
    'email' => 'shentapratiwi@gmail.com',
    'no hp' => '085704072802',
    'unit' => 'SMP NAMIRA',
  ),
  7 => 
  array (
    'nama' => 'Kartika Wulan, S.Pd.',
    'NIY' => '91931918',
    'email' => 'kartikawulan2015@gmail.com',
    'no hp' => '085649131573',
    'unit' => 'SMP NAMIRA',
  ),
  8 => 
  array (
    'nama' => 'R. Budiono',
    'NIY' => '92672021',
    'email' => 'r.budiono1967@gmail.com',
    'no hp' => '082228654141',
    'unit' => 'SMP NAMIRA',
  ),
  9 => 
  array (
    'nama' => 'Ghufronuddaroini',
    'NIY' => '92892022',
    'email' => 'ghufron.n2020@gmail.com',
    'no hp' => '085234361347',
    'unit' => 'SMP NAMIRA',
  ),
  10 => 
  array (
    'nama' => 'Darwin Djeni, S.Pd, M.Sc.',
    'NIY' => '91902027',
    'email' => 'darwindjeni15@gmail.com',
    'no hp' => '082264605878',
    'unit' => 'SMP NAMIRA',
  ),
  11 => 
  array (
    'nama' => 'Alfido Fauzy Zakaria, S.Pd., M.Pd.',
    'NIY' => '91942028',
    'email' => 'alfidofauzy@gmail.com',
    'no hp' => '081559837707',
    'unit' => 'SMP NAMIRA',
  ),
  12 => 
  array (
    'nama' => 'Dandi Pratama Putrawattimena',
    'NIY' => '92992133',
    'email' => 'dp0513463@gmail.com',
    'no hp' => '0895334324850',
    'unit' => 'SMP NAMIRA',
  ),
  13 => 
  array (
    'nama' => 'Dimas Eko Cahyono, M.Pd.',
    'NIY' => '91982135',
    'email' => 'samsamid38@gmail.com',
    'no hp' => '085290088187',
    'unit' => 'SMP NAMIRA',
  ),
  14 => 
  array (
    'nama' => 'Nurul Kurniyasih, S.Kom.I.',
    'NIY' => '91932134',
    'email' => 'kurniasihnurul2016@gmail.com',
    'no hp' => '085875499144',
    'unit' => 'SMP NAMIRA',
  ),
  15 => 
  array (
    'nama' => 'Muhammad Fathur Rozi, S.Pd., M.Pd.',
    'NIY' => '91972137',
    'email' => 'rozi8917@gmail.com',
    'no hp' => '085334222252',
    'unit' => 'SMP NAMIRA',
  ),
  16 => 
  array (
    'nama' => 'Rizki Dwi Karunia Sari, S.Pd.',
    'NIY' => '91992138',
    'email' => 'rizkidks23@gmail.com',
    'no hp' => '0895632660986',
    'unit' => 'SMP NAMIRA',
  ),
  17 => 
  array (
    'nama' => 'Nadia Pramadianti, S.M.',
    'NIY' => '92982139',
    'email' => 'npramadianti83@gmail.com',
    'no hp' => '085294893887',
    'unit' => 'SMP NAMIRA',
  ),
  18 => 
  array (
    'nama' => 'Natasha Dwike Yuni Astutik, S.Pd.',
    'NIY' => '91962242',
    'email' => 'natashadwikeyuni@gmail.com',
    'no hp' => '08980586411',
    'unit' => 'SMP NAMIRA',
  ),
  19 => 
  array (
    'nama' => 'Kartika Ardi Chumairoh, S.Pd.',
    'NIY' => '91982243',
    'email' => 'kartikachumairoh@gmail.com',
    'no hp' => '081327691026',
    'unit' => 'SMP NAMIRA',
  ),
  20 => 
  array (
    'nama' => 'Ma\'sum Ali Ridlwan, S.Pd.',
    'NIY' => '91922244',
    'email' => 'maksumridlwan74@gmail.com',
    'no hp' => '08887056492',
    'unit' => 'SMP NAMIRA',
  ),
  21 => 
  array (
    'nama' => 'Siti Hanifah, S.Pd.I.',
    'NIY' => '91922247',
    'email' => 'sitihanifah.17lj@gmail.com',
    'no hp' => '082318320550',
    'unit' => 'SMP NAMIRA',
  ),
  22 => 
  array (
    'nama' => 'Laila Nur Hamidah, M.Pd.I.',
    'NIY' => '91922248',
    'email' => 'ayla.hamidah@gmail.com',
    'no hp' => '085791903154',
    'unit' => 'SMP NAMIRA',
  ),
  23 => 
  array (
    'nama' => 'Anya Veda Eine Putri, S.Tr. Sos.',
    'NIY' => '91992246',
    'email' => 'bknamiraanya@gmail.com',
    'no hp' => '081333291265',
    'unit' => 'SMP NAMIRA',
  ),
  24 => 
  array (
    'nama' => 'Imam Thobroni',
    'NIY' => '91942352',
    'email' => 'imamroni720@gmail.com',
    'no hp' => '085334573375',
    'unit' => 'SMP NAMIRA',
  ),
  25 => 
  array (
    'nama' => 'Ahmad Shiddiq, S.Pd.',
    'NIY' => '91982350',
    'email' => 'asibnuhusain@gmail.com',
    'no hp' => '089602007321',
    'unit' => 'SMP NAMIRA',
  ),
  26 => 
  array (
    'nama' => 'Faizah Ulinnuha, S.Pd.',
    'NIY' => '91972353',
    'email' => 'faizahu9@gmail.com',
    'no hp' => '089514539031',
    'unit' => 'SMP NAMIRA',
  ),
  27 => 
  array (
    'nama' => 'Dimas Sidianto',
    'NIY' => '92752355',
    'email' => 'dimas@namira.school',
    'no hp' => '085819758211',
    'unit' => 'SMP NAMIRA',
  ),
  28 => 
  array (
    'nama' => 'Robin Hedra Jaya, S.Sn.',
    'NIY' => '91932354',
    'email' => 'rhendra.jaya93@gmail.com',
    'no hp' => '082233110775',
    'unit' => 'SMP NAMIRA',
  ),
  29 => 
  array (
    'nama' => 'M. Irfani',
    'NIY' => '91992353',
    'email' => 'fanni030899@gmail.com',
    'no hp' => '082140503787',
    'unit' => 'SMP NAMIRA',
  ),
  30 => 
  array (
    'nama' => 'Rofianto, S.Kom',
    'NIY' => '91932356',
    'email' => 'antorofi48@gmail.com',
    'no hp' => '082244695970',
    'unit' => 'SMP NAMIRA',
  ),
  31 => 
  array (
    'nama' => 'Siti Maria Ulfa, S.Pd.I.',
    'NIY' => '91922457',
    'email' => 'sitimariaulfa338@gmail.com',
    'no hp' => '085707546704',
    'unit' => 'SMP NAMIRA',
  ),
  32 => 
  array (
    'nama' => 'Diana Nuriyah',
    'NIY' => '91032458',
    'email' => 'nd3221823@gmail.com',
    'no hp' => '082131007760',
    'unit' => 'SMP NAMIRA',
  ),
  33 => 
  array (
    'nama' => 'Hikal Fikri',
    'NIY' => '91042459',
    'email' => 'hikalf19@gmail.com',
    'no hp' => '0881036175600',
    'unit' => 'SMP NAMIRA',
  ),
  34 => 
  array (
    'nama' => 'Sela Agustin, S.E.',
    'NIY' => '91982460',
    'email' => 'sela@namira.school',
    'no hp' => '082139220521',
    'unit' => 'SMP NAMIRA',
  ),
  35 => 
  array (
    'nama' => 'Holil Abdullatif, S.Pd.',
    'NIY' => '91972461',
    'email' => 'holilabdullatif100@gmail.com',
    'no hp' => '085204581497',
    'unit' => 'SMP NAMIRA',
  ),
  36 => 
  array (
    'nama' => 'Rosyidatul Ainia, S.Pd.',
    'NIY' => '91022462',
    'email' => 'ainiarosyidatul03@gmail.com',
    'no hp' => '085745620031',
    'unit' => 'SMP NAMIRA',
  ),
  37 => 
  array (
    'nama' => 'Sulthaan Randy Zhaafirzi',
    'NIY' => '92222463',
    'email' => 'sulthaan@namira.school',
    'no hp' => '',
    'unit' => 'SMP NAMIRA',
  ),
  38 => 
  array (
    'nama' => 'Muhammad Zhavrilo Zahirzi',
    'NIY' => '92222464',
    'email' => 'muhammad@namira.school',
    'no hp' => '',
    'unit' => 'SMP NAMIRA',
  ),
  39 => 
  array (
    'nama' => 'Izzatul Maula',
    'NIY' => '91982566',
    'email' => 'izzatul@namira.school',
    'no hp' => '081913855027',
    'unit' => 'SMP NAMIRA',
  ),
  40 => 
  array (
    'nama' => 'Nailatul Ilmi, S.Psi.',
    'NIY' => '91032567',
    'email' => 'nailatul@namira.school',
    'no hp' => '082257083244',
    'unit' => 'SMP NAMIRA',
  ),
  41 => 
  array (
    'nama' => 'Wachid Hasyim',
    'NIY' => '91972568',
    'email' => 'hwachsym003@gmail.com',
    'no hp' => '081331017757',
    'unit' => 'SMP NAMIRA',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no hp']);
            $gender = str_contains(strtolower($name), 'siti') || str_contains(strtolower($name), 'erna') || str_contains(strtolower($name), 'kartika') || str_contains(strtolower($name), 'faizah') || str_contains(strtolower($name), 'diana') || str_contains(strtolower($name), 'nadia') || str_contains(strtolower($name), 'natasha') || str_contains(strtolower($name), 'rima') || str_contains(strtolower($name), 'anik') || str_contains(strtolower($name), 'sinta') || str_contains(strtolower($name), 'nurul') || str_contains(strtolower($name), 'laila') || str_contains(strtolower($name), 'anya') || str_contains(strtolower($name), 'sela') || str_contains(strtolower($name), 'rosyidatul') || str_contains(strtolower($name), 'izzatul') || str_contains(strtolower($name), 'nailatul') || str_contains(strtolower($name), 'ulfa') ? 'P' : 'L';

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
        echo "Berhasil mengimpor 42 Guru SMP Namira!\n";
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SDNamiraTeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachersData = [
            ["nama" => "Abdul Adjis Afifi", "NIY" => "3259201301", "email" => "abdul@namira.school", "no_hp" => "085204854927"],
            ["nama" => "Anggun Happy Ananda, S.Pd", "NIY" => "3190201302", "email" => "Anggunhappyananda@gmail.com", "no_hp" => "085331362000"],
            ["nama" => "Hj Muthmainnah", "NIY" => "3175201506", "email" => "hj@namira.school", "no_hp" => "085204610367"],
            ["nama" => "Sudar", "NIY" => "32201607", "email" => "sudar@namira.school", "no_hp" => ""],
            ["nama" => "Kholifatul Khoiriyah, S.Si", "NIY" => "3192201609", "email" => "kholifatulk084@gmail.com", "no_hp" => "087861564895"],
            ["nama" => "Hisyam Farih, S.E", "NIY" => "3291201711", "email" => "anahisyam45@gmail.com", "no_hp" => "082232243354"],
            ["nama" => "Riyadhatul Badiah, S.E", "NIY" => "3195201715", "email" => "riyahafidz07@gmail.com", "no_hp" => "085259122195"],
            ["nama" => "Meylinda Kurnia Sofiyani, S.Psi", "NIY" => "3192201821", "email" => "meylindakurnia12@gmail.com", "no_hp" => "085234588078"],
            ["nama" => "Maulidia Khoiry, S.Pd", "NIY" => "3199201824", "email" => "maulidiakhoiriy@gmail.com", "no_hp" => "082331530162"],
            ["nama" => "Husnul Sri Maulidiah, S.Pd", "NIY" => "3197201933", "email" => "Husnulsrimaulidiah@gmail.com", "no_hp" => "081556823582"],
            ["nama" => "Mochammad", "NIY" => "3260201934", "email" => "mochammad@namira.school", "no_hp" => "082331530162"],
            ["nama" => "Halimatus Sa'diyah, S.Pd", "NIY" => "3196201935", "email" => "Halimasadiyah238@gmail.com", "no_hp" => "085331167567"],
            ["nama" => "Cahya Arief Khoirumah S.Pd", "NIY" => "3196202037", "email" => "khoirumahcahya1104@gmail.com", "no_hp" => "085804014742"],
            ["nama" => "Dwi Arifatun Nisa' S.Pd", "NIY" => "3196202039", "email" => "dwi@namira.school", "no_hp" => "082316283056"],
            ["nama" => "Siti Anisa S.Hum", "NIY" => "3197202041", "email" => "sitiianisaa456@gmail.com", "no_hp" => "085230217949"],
            ["nama" => "Agung Prassetiyo", "NIY" => "3198202142", "email" => "agungprassetiyo511@gmail.com", "no_hp" => "085217208502"],
            ["nama" => "Azkiyah Amalina S.Pd", "NIY" => "3197202143", "email" => "azkiyahamalina79@gmail.com", "no_hp" => "085335821035"],
            ["nama" => "Rosyidah S.Pd", "NIY" => "3198201744", "email" => "rosyidahnamira123@gmail.com", "no_hp" => "082338795422"],
            ["nama" => "Mia Nurhidayati S.E", "NIY" => "3198202247", "email" => "mianurhidayati7@gmail.com", "no_hp" => "081359564307"],
            ["nama" => "Siti Aminatul Qomariyah", "NIY" => "3101202249", "email" => "syarifahaminatul@gmail.com", "no_hp" => "081233171193"],
            ["nama" => "Khusnul Hotimah S.Pd", "NIY" => "3100202251", "email" => "khusnulhotimah1123@gmail.com", "no_hp" => "081357135188"],
            ["nama" => "Nur Halimah", "NIY" => "3198202252", "email" => "Hnurhalimah091@gmail.com", "no_hp" => "085290443736"],
            ["nama" => "Fajar Ridwan Abilillah S.Pd", "NIY" => "3100202253", "email" => "fajar@namira.school", "no_hp" => "0895630439320"],
            ["nama" => "Halifah", "NIY" => "3272201654", "email" => "halifah@namira.school", "no_hp" => ""],
            ["nama" => "Ike Nurjannah S.Pd", "NIY" => "3100202355", "email" => "ikenurjannah618@gmail.com", "no_hp" => "085608029378"],
            ["nama" => "Muhammad Farid S.Pd", "NIY" => "3100202356", "email" => "faridjenny24@gmail.com", "no_hp" => "085234789280"],
            ["nama" => "Rehanatil Jannah", "NIY" => "3101202357", "email" => "jannahrehanatil@gmail.com", "no_hp" => "082337975497"],
            ["nama" => "Iva Mutma'inah S.Pd", "NIY" => "3100202358", "email" => "ivamutmainah.1507@gmail.com", "no_hp" => "082330345815"],
            ["nama" => "Alfina Ananda Putri S.Pd", "NIY" => "3101202359", "email" => "putrialnanda12@gmail.com", "no_hp" => "082245621324"],
            ["nama" => "Firdani Sholeh Pradana S.Pd", "NIY" => "3190202360", "email" => "dani.firdani@gmail.com", "no_hp" => "082335345167"],
            ["nama" => "Ahmad Baidhowi S.Pd", "NIY" => "3197202461", "email" => "ahmadbaidhowi108@gmail.com", "no_hp" => "81336535501"],
            ["nama" => "Shofiyah Husein S.Pd", "NIY" => "3102202462", "email" => "shofiyahhusein682@gmail.com", "no_hp" => "085732439937"],
            ["nama" => "Abd Hannan", "NIY" => "3291202463", "email" => "abd@namira.school", "no_hp" => "082269523244"],
            ["nama" => "Yazid Mubtafi S.Pd", "NIY" => "3101202464", "email" => "yazimubtafi7@gmail.com", "no_hp" => "083137368121"],
            ["nama" => "Intan Maufirah", "NIY" => "3101202465", "email" => "syfintan847@gmail.com", "no_hp" => "085853664685"],
            ["nama" => "Helmi Mufidah", "NIY" => "3102202466", "email" => "helmimufida05@gmail.com", "no_hp" => "083157513651"],
            ["nama" => "Dandik Nofian Putra Pratama", "NIY" => "3299202467", "email" => "dandik@namira.school", "no_hp" => "081280356087"],
            ["nama" => "Nadifah S.Pd", "NIY" => "3103202468", "email" => "ndf.5403@gmail.com", "no_hp" => "085233858252"],
            ["nama" => "Mamluatul Hasanah S.Pd", "NIY" => "3100202469", "email" => "mamluatulhasanah1520@gmail.com", "no_hp" => "085850797267"],
            ["nama" => "Nur Aini Trischa Ananda", "NIY" => "3100202470", "email" => "nurainifriscaananda@gmail.com", "no_hp" => "083845072546"],
            ["nama" => "Hermawan Diva Ardi Wijaya", "NIY" => "3102202471", "email" => "hermawan@namira.school", "no_hp" => "083852801326"],
            ["nama" => "Putri Agustini S.Sos", "NIY" => "3101202472", "email" => "putriagustini1303@gmail.com", "no_hp" => "082266837207"],
            ["nama" => "Muhammad Syarifudin S.Pd", "NIY" => "3101202473", "email" => "muhammadsyarifudin032001@gmail.com", "no_hp" => "085648235862"],
            ["nama" => "Deny Setiawan S.Pd", "NIY" => "3102202474", "email" => "setiawandeny1602@gmail.com", "no_hp" => "081259894411"],
            ["nama" => "Hasbullah S.Pd.I", "NIY" => "3102202475", "email" => "hasbulcs1@gmail.com", "no_hp" => "085231224112"],
            ["nama" => "SARIF", "NIY" => "3105202576", "email" => "syarifsya726@gmail.com", "no_hp" => "083155792854"],
            ["nama" => "Meirinda Zahratul M. S.Pd", "NIY" => "3102202577", "email" => "meirindazm@gmail.com", "no_hp" => "081259081907"],
            ["nama" => "Rian Hidayad S. Kom", "NIY" => "3102202578", "email" => "rianbru18@gmail.com", "no_hp" => "082140560121"],
            ["nama" => "Ahmad Kamil Fadoli S.Pd", "NIY" => "3196202579", "email" => "kamilfadoli20@gmail.com", "no_hp" => "082318246720"],
            ["nama" => "Astutik, S.Pd.I", "NIY" => "3180201380", "email" => "astutik7749@gmail.com", "no_hp" => "082330221399"]
        ];

        $sdUnit = Unit::where('name', 'like', '%SD%')->orWhere('code', 'SD')->first() ?? Unit::first();
        if (!$sdUnit) return;

        foreach ($teachersData as $t) {
            $email = strtolower(trim($t['email']));
            $name = trim($t['nama']);
            $niy = trim($t['NIY']);
            $phone = trim($t['no_hp']);

            $femaleKeywords = ['Anggun', 'Hj', 'Muthmainnah', 'Kholifatul', 'Riyadhatul', 'Badiah', 'Meylinda', 'Maulidia', 'Husnul', 'Halimatus', 'Sa\'diyah', 'Dwi', 'Nisa', 'Siti', 'Anisa', 'Azkiyah', 'Rosyidah', 'Mia', 'Aminatul', 'Khusnul', 'Hotimah', 'Nur', 'Halifah', 'Ike', 'Rehanatil', 'Iva', 'Alfina', 'Shofiyah', 'Intan', 'Helmi', 'Mufidah', 'Nadifah', 'Mamluatul', 'Aini', 'Trischa', 'Putri', 'Astutik', 'Meirinda'];
            $gender = 'L';
            foreach ($femaleKeywords as $kw) {
                if (stripos($name, $kw) !== false) {
                    $gender = 'P';
                    break;
                }
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($niy ?: 'guru123'),
                    'email_verified_at' => now(),
                ]
            );

            setPermissionsTeamId($sdUnit->id);
            if (!$user->hasRole('teacher')) {
                $user->assignRole('teacher');
            }

            Teacher::updateOrCreate(
                ['user_id' => $user->id, 'unit_id' => $sdUnit->id],
                [
                    'full_name' => $name,
                    'nip' => $niy,
                    'gender' => $gender,
                    'phone' => $phone ?: null,
                ]
            );
        }
    }
}

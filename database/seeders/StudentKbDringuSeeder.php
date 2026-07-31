<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentKbDringuSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'KB-DRI')
            ->orWhere(function ($q) {
                $q->where('name', 'LIKE', '%KB%')->where('name', 'LIKE', '%Dringu%');
            })
            ->first();

        if (!$unit) {
            echo "Unit KB Namira Dringu (KB-DRI) tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'siswa')->orWhere('name', 'student')->first();
        $roleId = $role ? $role->id : null;

        $studentsData = array (
  0 => 
  array (
    'full_name' => 'Anneliecia Mumtaza Syakila',
    'nickname' => 'Aliecia',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-11-11',
    'address' => 'Perum Jenggrong Cityland Blok D No. 04',
    'parent_name' => 'Muhammad Hasanudin',
    'email' => 'anneliecia@namira.school',
  ),
  1 => 
  array (
    'full_name' => 'Clemira Salma Leena',
    'nickname' => 'Clemira',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2023-07-11',
    'address' => 'Perum Mutiara Insani Residence B1 Jorongan, Leces',
    'parent_name' => 'Sigit Wida Hartono',
    'email' => 'clemira@namira.school',
  ),
  2 => 
  array (
    'full_name' => 'Yazdan Kahfindra Prasetya',
    'nickname' => 'Yazdan',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2023-03-14',
    'address' => 'Perum The Icon No. 7 Kalirejo, dringu',
    'parent_name' => 'Indra Prasetya',
    'email' => 'yazdan@namira.school',
  ),
  3 => 
  array (
    'full_name' => 'Uzair Muhammad Khuwailid',
    'nickname' => 'Uzair',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2023-01-20',
    'address' => 'Jl. Cokroaminoto Gg. Mawar 2 Rt 9 Rw 4 No. 7 kanigaran',
    'parent_name' => 'Deta Budi Ladesma',
    'email' => 'uzair@namira.school',
  ),
  4 => 
  array (
    'full_name' => 'Kayana Isvara Putih',
    'nickname' => 'Nana',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-07-04',
    'address' => 'Perum Griya Kalirejo Kav 12 RT 03 RW 02',
    'parent_name' => 'Zakaria',
    'email' => 'kayana@namira.school',
  ),
  5 => 
  array (
    'full_name' => 'Mavaza Shofwah Qolbiya Mahendra',
    'nickname' => 'Vaza',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2023-08-04',
    'address' => 'Randuputih, Dringu',
    'parent_name' => 'Ade Mahendra',
    'email' => 'mavaza@namira.school',
  ),
  6 => 
  array (
    'full_name' => 'Dhensanaya Jennaira Nooralayn',
    'nickname' => 'Aira',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Malang',
    'dob' => '2023-07-10',
    'address' => 'Curahsawo, Gending',
    'parent_name' => 'Dhehan Febrianto',
    'email' => 'dhensanaya@namira.school',
  ),
  7 => 
  array (
    'full_name' => 'Diana Feisya Selvia',
    'nickname' => 'Feisya',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => '22 Juli 2022',
    'dob' => '',
    'address' => 'Dusun krajan bandaran, Dringu',
    'parent_name' => 'Abdul Malik',
    'email' => 'diana@namira.school',
  ),
  8 => 
  array (
    'full_name' => 'Wangi Sekar Melati',
    'nickname' => 'Sekar',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-08-28',
    'address' => 'Mranggon Lawang, Dringu',
    'parent_name' => 'Sena Dio Vananda',
    'email' => 'wangi@namira.school',
  ),
  9 => 
  array (
    'full_name' => 'Shaquille Leondra Pramana',
    'nickname' => 'Leon',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-08-06',
    'address' => 'Jrebeng Wetan, Kedopok',
    'parent_name' => 'Adi Ambar Pramono',
    'email' => 'shaquille@namira.school',
  ),
  10 => 
  array (
    'full_name' => 'Arrasya Keenandra Prayoga',
    'nickname' => 'Rasya',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-12-16',
    'address' => 'Kedungdalem, Dringu',
    'parent_name' => 'Yoga Dwi Satria',
    'email' => 'arrasya@namira.school',
  ),
  11 => 
  array (
    'full_name' => 'Muhammad Affandra El Hasiq',
    'nickname' => 'Fandra',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-07-25',
    'address' => 'Perum Sonas Tegalrejo Raya Blok C-12 Dringu',
    'parent_name' => 'Rahman Fadillah Sughandi',
    'email' => 'muhammad@namira.school',
  ),
  12 => 
  array (
    'full_name' => 'Emran Ryuga Arrosyid',
    'nickname' => 'Ryuga',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-11-29',
    'address' => 'Perum Griya Asri Kentjana, Randupitu, Gending',
    'parent_name' => 'Emha Ridwan Arrosyid',
    'email' => 'emran@namira.school',
  ),
  13 => 
  array (
    'full_name' => 'Zheandra Alfarizi Rizki Muhammad',
    'nickname' => 'Zhean',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2023-10-04',
    'address' => 'Dusun Tambak Pesisir RT 14 RW 05 Dringu',
    'parent_name' => 'Andre Rizki Mulyono',
    'email' => 'zheandra@namira.school',
  ),
  14 => 
  array (
    'full_name' => 'Nashatyar Azalia Mehrunnisa Saqi',
    'nickname' => 'Nasha',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-09-19',
    'address' => 'Wiroborang, Mayangan',
    'parent_name' => 'Agung Prambastyar',
    'email' => 'nashatyar@namira.school',
  ),
  15 => 
  array (
    'full_name' => 'Atharrazka Zayn Bansir',
    'nickname' => 'Zayn',
    'section' => 'DATA PESERTA DIDIK KB A NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Malang',
    'dob' => '2022-11-09',
    'address' => 'Perum Kalirejo Permai D-36 Dringu',
    'parent_name' => 'Abdurrahman Ribhi Bansir',
    'email' => 'atharrazka@namira.school',
  ),
  16 => 
  array (
    'full_name' => 'Yislam Kahfindra Prasetya',
    'nickname' => 'Yislam',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2023-03-14',
    'address' => 'Perum The Icon No. 7 Kalirejo, dringu',
    'parent_name' => 'Indra Prasetya',
    'email' => 'yislam@namira.school',
  ),
  17 => 
  array (
    'full_name' => 'Devano Denendra Ramadon',
    'nickname' => 'Devano',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-04-21',
    'address' => 'RT 10 RW 04, Randuputih, Dringu',
    'parent_name' => 'Renda Adi Saputro',
    'email' => 'devano@namira.school',
  ),
  18 => 
  array (
    'full_name' => 'Zhio Al Fatih Ramadhan',
    'nickname' => 'Zhio',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-04-30',
    'address' => 'Jl. Brawijaya 1 07/02, Wiroborang',
    'parent_name' => 'Fajar Adi Pamungkas',
    'email' => 'zhio@namira.school',
  ),
  19 => 
  array (
    'full_name' => 'Muhammad Hafidz Athallah Nur Ramadhan',
    'nickname' => 'Hafidz',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-04-27',
    'address' => 'Jl. Serayu RT 2 RW 1 Jrebeng Kulon, Kedopok',
    'parent_name' => 'M.M. sugeng Widodo',
    'email' => 'muhammad.2@namira.school',
  ),
  20 => 
  array (
    'full_name' => 'Guinandra Yahya Sugiarto',
    'nickname' => 'Guinandra',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Lampung',
    'dob' => '2022-06-11',
    'address' => 'Jl. Ronggojalu Gg. Kenanga Perum Permata Kedungdalem asri, Dringu',
    'parent_name' => 'Guntur Sugiarto',
    'email' => 'guinandra@namira.school',
  ),
  21 => 
  array (
    'full_name' => 'Arsakha Sachio Kaindra',
    'nickname' => 'Chio',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-02-28',
    'address' => 'Jl. Raya Dringu, Kedungdalem, Dringu',
    'parent_name' => 'Zainul Usman',
    'email' => 'arsakha@namira.school',
  ),
  22 => 
  array (
    'full_name' => 'Danish Erabbani Sumantiasa',
    'nickname' => 'Danish',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Malang',
    'dob' => '2022-03-29',
    'address' => 'Pajurangan, Gending',
    'parent_name' => 'Puji Oktavi Sumantiasa',
    'email' => 'danish@namira.school',
  ),
  23 => 
  array (
    'full_name' => 'Ashraff Syauqi Win Andaru',
    'nickname' => 'Ashraff',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-05-05',
    'address' => 'Kalisalam, Dringu',
    'parent_name' => 'Herwin Jaya',
    'email' => 'ashraff@namira.school',
  ),
  24 => 
  array (
    'full_name' => 'Muhammad Hasan Atharrazka',
    'nickname' => 'Azka',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Gresik',
    'dob' => '2022-06-21',
    'address' => 'Sumber Taman, Wonoasih',
    'parent_name' => 'Moh. Hasan Chotibul Umam Thoha',
    'email' => 'muhammad.3@namira.school',
  ),
  25 => 
  array (
    'full_name' => 'Safeeya Almahyra Bala’masy',
    'nickname' => 'Safeeya',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-05-20',
    'address' => 'Pabean, Dringu',
    'parent_name' => 'Mubarak Fauzi Bala’masy',
    'email' => 'safeeya@namira.school',
  ),
  26 => 
  array (
    'full_name' => 'Ahmad Royhan Albiruni Cahyono',
    'nickname' => 'Albi',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Bondowoso',
    'dob' => '2022-01-20',
    'address' => 'Wonosari, Bondowoso',
    'parent_name' => 'Dimas Eko Cahyono',
    'email' => 'ahmad@namira.school',
  ),
  27 => 
  array (
    'full_name' => 'Ahmad Rifa’i Fathan Fauzi',
    'nickname' => 'Fathan',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-08-23',
    'address' => 'Sumber Kerang, Gending',
    'parent_name' => 'Ahmad Fauzi',
    'email' => 'ahmad.2@namira.school',
  ),
  28 => 
  array (
    'full_name' => 'Milea Arumi Zamrosi',
    'nickname' => 'Arumi',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-07-01',
    'address' => 'Sumber Taman, Wonoasih',
    'parent_name' => 'Achmad Zamrosi',
    'email' => 'milea@namira.school',
  ),
  29 => 
  array (
    'full_name' => 'Ibrahim Evano Hariyanto',
    'nickname' => 'Ibrahim',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-06-04',
    'address' => 'Kedungdalem, Dringu',
    'parent_name' => 'Nanang Hariyanto',
    'email' => 'ibrahim@namira.school',
  ),
  30 => 
  array (
    'full_name' => 'Annasya Savrinadeya Ahmad',
    'nickname' => 'Nasya',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-03-19',
    'address' => 'Wiroborang, Mayangan',
    'parent_name' => 'Ahmad Shiddiq',
    'email' => 'annasya@namira.school',
  ),
  31 => 
  array (
    'full_name' => 'Khalisa Hanin Kurnia',
    'nickname' => 'Khalisa',
    'section' => 'DATA PESERTA DIDIK KB B NAMIRA SCHOOL II',
    'unit_code' => 'KB-DRI',
    'unit_name' => 'KB Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-01-19',
    'address' => 'Tegalrejo, Dringu',
    'parent_name' => 'Jerry Kurniansyah Rifaldi',
    'email' => 'khalisa@namira.school',
  ),
);

        foreach ($studentsData as $item) {
            $email = trim($item['email']);
            $name = trim($item['full_name']);
            $pob = trim($item['pob']);
            $dob = !empty($item['dob']) ? $item['dob'] : null;
            $address = trim($item['address']);
            $parentName = trim($item['parent_name']);
            $section = trim($item['section']);

            // Parse classroom from section (e.g. DATA PESERTA DIDIK KB A -> KB A)
            $classroomName = null;
            if (preg_match('/(KB\s+[A-Z0-9]+|TK\s+[A-Z0-9]+)/i', $section, $m)) {
                $classroomName = strtoupper(trim($m[1]));
            }

            $classroomId = null;
            if (!empty($classroomName)) {
                // Determine level (e.g. KB, TK A, TK B)
                $level = $classroomName;
                if (str_contains($classroomName, 'KB')) {
                    $level = 'KB';
                } elseif (str_contains($classroomName, 'TK A')) {
                    $level = 'TK A';
                } elseif (str_contains($classroomName, 'TK B')) {
                    $level = 'TK B';
                }

                // Auto-create classroom if not exists
                $cls = Classroom::firstOrCreate([
                    'unit_id' => $unitId,
                    'name' => $classroomName,
                ], [
                    'level' => $level,
                ]);

                $classroomId = $cls->id;
            }

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
                'classroom_id' => $classroomId,
            ]);
        }
        echo "Berhasil mengimpor 32 Siswa KB Namira Dringu beserta pemetaan Kelas!\n";
    }
}

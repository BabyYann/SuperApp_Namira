<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentTkDringuSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'TK-DRI')
            ->orWhere(function ($q) {
                $q->where('name', 'LIKE', '%TK%')->where('name', 'LIKE', '%Dringu%');
            })
            ->first();

        if (!$unit) {
            echo "Unit TK Namira Dringu (TK-DRI) tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'siswa')->orWhere('name', 'student')->first();
        $roleId = $role ? $role->id : null;

        $studentsData = array (
  0 => 
  array (
    'full_name' => 'Arumi Nasha Ardila',
    'nickname' => 'Arumi',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Bondowoso',
    'dob' => '2022-03-22',
    'address' => 'Jl. Kaliamas Perum Kalirejo Permai Blok A22, Dringu',
    'parent_name' => 'Wahyu Indra Kurniawan',
    'email' => 'arumi@namira.school',
  ),
  1 => 
  array (
    'full_name' => 'Alisha Atthaya Savina',
    'nickname' => 'Caca',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-11-17',
    'address' => 'Mranggon Lawang, Dringu',
    'parent_name' => 'Azizul Hidayatullah',
    'email' => 'alisha@namira.school',
  ),
  2 => 
  array (
    'full_name' => 'Ersya Keinara Rakhman',
    'nickname' => 'Nara',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-06-01',
    'address' => 'Perum Mutiara Village B9, Jl. Mastrip Kedopok',
    'parent_name' => 'Fakhruddin Rakhman Adam',
    'email' => 'ersya@namira.school',
  ),
  3 => 
  array (
    'full_name' => 'Elrumi Atharrizqy Prasetya',
    'nickname' => 'El',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-06-15',
    'address' => 'Sumber kerang, Gending',
    'parent_name' => 'Dony Prasetya',
    'email' => 'elrumi@namira.school',
  ),
  4 => 
  array (
    'full_name' => 'Adea Maylaffayza Ramadhisa',
    'nickname' => 'Adea',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-01-19',
    'address' => 'Sukabumi, Mayangan',
    'parent_name' => 'Rofianto',
    'email' => 'adea@namira.school',
  ),
  5 => 
  array (
    'full_name' => 'Nayyara Syahbani Shidqia',
    'nickname' => 'Nayyara',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-03-22',
    'address' => 'Perum Sumber Taman Indah Jl. Taman Kenanga No. 8 Probolinggo',
    'parent_name' => 'Mochammad Nabris Sidqi',
    'email' => 'nayyara@namira.school',
  ),
  6 => 
  array (
    'full_name' => 'Kenzo Adskhan Syaifullah',
    'nickname' => 'Kenzo',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-02-22',
    'address' => 'Pabean, Dringu',
    'parent_name' => 'Syaiful Arifin',
    'email' => 'kenzo@namira.school',
  ),
  7 => 
  array (
    'full_name' => 'Elleanor De Sharoon Maheswari Adhadina',
    'nickname' => 'Elleanor',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Nganjuk',
    'dob' => '2021-09-14',
    'address' => 'Perum New Kartika Regency Blok A9',
    'parent_name' => 'Reesky Riko Adhadina',
    'email' => 'elleanor@namira.school',
  ),
  8 => 
  array (
    'full_name' => 'Mikhail Renard Aryasatya',
    'nickname' => 'Mikha',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Jember',
    'dob' => '2021-08-01',
    'address' => 'Perum Dinas PT. Sasa Inti Gending',
    'parent_name' => 'Arief Lutfianto',
    'email' => 'mikhail@namira.school',
  ),
  9 => 
  array (
    'full_name' => 'Sayyidah Mafaza Almahyra',
    'nickname' => 'Alma',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-01-28',
    'address' => 'Tuggalpangger, Pungging, Mojokerto',
    'parent_name' => 'Sinung Trah Utomo',
    'email' => 'sayyidah@namira.school',
  ),
  10 => 
  array (
    'full_name' => 'Muhammad Ukkasyah Nuddaroin',
    'nickname' => 'Ukkasyah',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-12-24',
    'address' => 'Triwung Lor',
    'parent_name' => 'Ghufronuddaroini',
    'email' => 'muhammad.4@namira.school',
  ),
  11 => 
  array (
    'full_name' => 'Rayyan Shauqi Bachtiar',
    'nickname' => 'Rayyan',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-05-20',
    'address' => 'Wiroborang, Mayangan',
    'parent_name' => 'M. Darwis Bachtiar',
    'email' => 'rayyan@namira.school',
  ),
  12 => 
  array (
    'full_name' => 'Muhammad Bintang Sirius Bena Idhang',
    'nickname' => 'Bintang',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-02-12',
    'address' => 'Triwung Lor, Kademangan',
    'parent_name' => 'Adi Tri Bima Soleh',
    'email' => 'muhammad.5@namira.school',
  ),
  13 => 
  array (
    'full_name' => 'Muhammad Hisyam Al Farisi',
    'nickname' => 'Hisyam',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-11-01',
    'address' => 'Jati, Mayangan',
    'parent_name' => 'Sofwan Aziz',
    'email' => 'muhammad.6@namira.school',
  ),
  14 => 
  array (
    'full_name' => 'Aisyah Kiara Raudhah',
    'nickname' => 'Kiara',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-10-11',
    'address' => 'Jl. Panglima Sudirman 495',
    'parent_name' => 'Arif Yudi Riyanto',
    'email' => 'aisyah@namira.school',
  ),
  15 => 
  array (
    'full_name' => 'Gendis Binar Athaya',
    'nickname' => 'Gendis',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Ponorogo',
    'dob' => '2022-02-04',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Gufron Maulana Ali',
    'email' => 'gendis@namira.school',
  ),
  16 => 
  array (
    'full_name' => 'Ameerra Mecca Tifany',
    'nickname' => 'Ameera',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-05-27',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Rizal Alfanani',
    'email' => 'ameerra@namira.school',
  ),
  17 => 
  array (
    'full_name' => 'Hayya Haura Arham',
    'nickname' => 'Hayya',
    'section' => 'DATA PESERTA DIDIK TK A1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-01-04',
    'address' => 'Jl. S.Parmanno 94, Jati, Mayangan',
    'parent_name' => 'Abdur Rozzaq Hamdani AR',
    'email' => 'hayya@namira.school',
  ),
  18 => 
  array (
    'full_name' => 'Elbarsya Muhammad Kenji',
    'nickname' => 'Elbarsya',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-05-23',
    'address' => 'Jl. Srikandi Gg Gumuk 4',
    'parent_name' => 'Aji Teguh Prakoso',
    'email' => 'elbarsya@namira.school',
  ),
  19 => 
  array (
    'full_name' => 'Ahmad Akbar Albiansyah',
    'nickname' => 'Bian',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-05-12',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Muhammad Iqbal Romadhon',
    'email' => 'ahmad.3@namira.school',
  ),
  20 => 
  array (
    'full_name' => 'Alifia Hasha Rabbani',
    'nickname' => 'Hasya',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-08-23',
    'address' => 'Kalisalam, Dringu',
    'parent_name' => 'Ramadhan',
    'email' => 'alifia@namira.school',
  ),
  21 => 
  array (
    'full_name' => 'Irfani Alman Hidayat',
    'nickname' => 'Irfan',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-11-28',
    'address' => 'Ketapang, Kademangan',
    'parent_name' => 'Frans Hidayat',
    'email' => 'irfani@namira.school',
  ),
  22 => 
  array (
    'full_name' => 'Alesha Maida Kurniawan',
    'nickname' => 'Alesha',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Jakarta',
    'dob' => '2021-06-26',
    'address' => 'Karang Dampit, Kraksaan',
    'parent_name' => 'Aldi Kurniawan',
    'email' => 'alesha@namira.school',
  ),
  23 => 
  array (
    'full_name' => 'Mahreen Daniya Alesha',
    'nickname' => 'Mahreen',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-07-23',
    'address' => 'Wiroborang',
    'parent_name' => 'Azies Purnomo',
    'email' => 'mahreen@namira.school',
  ),
  24 => 
  array (
    'full_name' => 'Shafana Bestari Danuardara',
    'nickname' => 'Dara',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Garut',
    'dob' => '2021-04-13',
    'address' => 'Jrebeng Kulon, Kedopok',
    'parent_name' => 'Wahyu Andrian Kusuma',
    'email' => 'shafana@namira.school',
  ),
  25 => 
  array (
    'full_name' => 'Abizar Zhafran Putra',
    'nickname' => 'Abizar',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2022-01-18',
    'address' => 'Sumber Taman, Wonoasih',
    'parent_name' => 'David Mahagandi',
    'email' => 'abizar@namira.school',
  ),
  26 => 
  array (
    'full_name' => 'Queena Azzahra Maudya Ranuh',
    'nickname' => 'Queena',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Surabaya',
    'dob' => '2021-08-28',
    'address' => 'Klampis Ngasem, Sukolilo, Surabaya',
    'parent_name' => 'Dr. IGN Iswan Rahmadi Ranuh',
    'email' => 'queena@namira.school',
  ),
  27 => 
  array (
    'full_name' => 'Arunika Xaquila Azlavi',
    'nickname' => 'Aru',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-07-01',
    'address' => 'Perumahan Kalirejo A-2, Dringu',
    'parent_name' => 'Mohammad Rizal Azlavi',
    'email' => 'arunika@namira.school',
  ),
  28 => 
  array (
    'full_name' => 'Adzril Rafif Alfarezi Harianto',
    'nickname' => 'Adzril',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-09-26',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Didik Harianto',
    'email' => 'adzril@namira.school',
  ),
  29 => 
  array (
    'full_name' => 'Balqis Callista putri',
    'nickname' => 'Balqis',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-05-07',
    'address' => 'Kedungdalem, Dringu',
    'parent_name' => 'Taufik Ismail Majid',
    'email' => 'balqis@namira.school',
  ),
  30 => 
  array (
    'full_name' => 'Azkia Qaireen Putri Nopita',
    'nickname' => 'Azkia',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Proboliinggo',
    'dob' => '2021-08-17',
    'address' => 'Triwung Lor, Kademangan',
    'parent_name' => 'Aang Nopita',
    'email' => 'azkia@namira.school',
  ),
  31 => 
  array (
    'full_name' => 'Nur Fauziah Muchlis',
    'nickname' => 'Fauziah',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Karawang',
    'dob' => '2021-07-20',
    'address' => 'Perum Arum Permai',
    'parent_name' => 'Fauzan Muchlis',
    'email' => 'nur@namira.school',
  ),
  32 => 
  array (
    'full_name' => 'Muhammad Elfathan Mubarok',
    'nickname' => 'Elfathan',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-05-29',
    'address' => 'Randuputih, Dringu',
    'parent_name' => 'Moch. Imron',
    'email' => 'muhammad.7@namira.school',
  ),
  33 => 
  array (
    'full_name' => 'Sekar Aullia Prastiyono',
    'nickname' => 'Aullia',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-06-05',
    'address' => 'Jorongan, Leces',
    'parent_name' => 'Rully Eko Prastiyono',
    'email' => 'sekar@namira.school',
  ),
  34 => 
  array (
    'full_name' => 'Zubair Al-Hawary',
    'nickname' => 'Zubair',
    'section' => 'DATA PESERTA DIDIK TK A2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-08-01',
    'address' => 'Pabean, dringu',
    'parent_name' => 'Gempoer Laksana Dewa',
    'email' => 'zubair@namira.school',
  ),
  35 => 
  array (
    'full_name' => 'Adnan Khiar Ardhani Wicaksono',
    'nickname' => 'Adnan',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-08-08',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Ivan Hari Wicaksono',
    'email' => 'adnan@namira.school',
  ),
  36 => 
  array (
    'full_name' => 'Aisyah Putri Aurel',
    'nickname' => 'Aisyah',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-04-28',
    'address' => 'Jati, Mayangan',
    'parent_name' => 'Aurel Al Faurel',
    'email' => 'aisyah.2@namira.school',
  ),
  37 => 
  array (
    'full_name' => 'Azsyauqie Zavier Syafi Athallah',
    'nickname' => 'Azqie',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-06-13',
    'address' => 'Sumber Wetan, Kedopok',
    'parent_name' => 'Muhammad Hasanudin',
    'email' => 'azsyauqie@namira.school',
  ),
  38 => 
  array (
    'full_name' => 'Dania Azzahra Putri',
    'nickname' => 'Dania',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-09-05',
    'address' => 'Sumber Taman, Wonoasih',
    'parent_name' => 'David Mahagandi',
    'email' => 'dania@namira.school',
  ),
  39 => 
  array (
    'full_name' => 'Farhan Alfarizi Setiawan',
    'nickname' => 'Farhan',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Jember',
    'dob' => '2020-04-28',
    'address' => 'Jember Kidul, Kaliwates',
    'parent_name' => 'Wahyu Relly Setiawan',
    'email' => 'farhan@namira.school',
  ),
  40 => 
  array (
    'full_name' => 'Jelita Nada Delingga',
    'nickname' => 'Jelita',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-12-14',
    'address' => 'Tongas wetan, Tongas',
    'parent_name' => 'Angga Prasetiyo',
    'email' => 'jelita@namira.school',
  ),
  41 => 
  array (
    'full_name' => 'Jihan Aprilia Zahrrani',
    'nickname' => 'Jihan',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-04-10',
    'address' => 'Tamansari,Dringu',
    'parent_name' => 'Muhammad Ego',
    'email' => 'jihan@namira.school',
  ),
  42 => 
  array (
    'full_name' => 'Kayyisa Elysia Wijayanto',
    'nickname' => 'Yisa',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-03-31',
    'address' => 'Wiroborang, Mayangan',
    'parent_name' => 'Rizal Setia Wijayanto',
    'email' => 'kayyisa@namira.school',
  ),
  43 => 
  array (
    'full_name' => 'Mikhayla Queenara Aiko Bawono Badriansyah',
    'nickname' => 'Aiko',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-03-12',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Cahyono Badriansyah',
    'email' => 'mikhayla@namira.school',
  ),
  44 => 
  array (
    'full_name' => 'Muhammad Ar-rafi fathian Ranuh',
    'nickname' => 'Rafi',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Surabaya',
    'dob' => '2019-11-29',
    'address' => 'Klampis Ngasem, Sukolilo',
    'parent_name' => 'IGN Iswan Rahmadi Ranuh',
    'email' => 'muhammad.8@namira.school',
  ),
  45 => 
  array (
    'full_name' => 'Muhammad Chandra Adi Winoto',
    'nickname' => 'Chandra',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-08-20',
    'address' => 'Liprak Kulon, Banyuanyar',
    'parent_name' => 'Mochamad Noer Yayan',
    'email' => 'muhammad.9@namira.school',
  ),
  46 => 
  array (
    'full_name' => 'Muhammad Zefa Althafandra',
    'nickname' => 'Zefa',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-06-10',
    'address' => 'Kedungdalem, Dringu',
    'parent_name' => 'Harllan Yollan Dana',
    'email' => 'muhammad.10@namira.school',
  ),
  47 => 
  array (
    'full_name' => 'Najwa Fairuz Korina Salsabika',
    'nickname' => 'Najwa',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-05-31',
    'address' => 'Lidahwetan, Lakarsantri',
    'parent_name' => 'Aflakh',
    'email' => 'najwa@namira.school',
  ),
  48 => 
  array (
    'full_name' => 'Nathan Naushad Altair',
    'nickname' => 'Nathan',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-04-24',
    'address' => 'Sumberagung, Dringu',
    'parent_name' => 'Melvin Febrianus Syakban',
    'email' => 'nathan@namira.school',
  ),
  49 => 
  array (
    'full_name' => 'Shayna Alina Abrianna',
    'nickname' => 'Alina',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-06-26',
    'address' => 'Jorongan, Leces',
    'parent_name' => 'Sigit Wida Hartono',
    'email' => 'shayna@namira.school',
  ),
  50 => 
  array (
    'full_name' => 'Ziyanah Atqiya',
    'nickname' => 'Ziya',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Situbondo',
    'dob' => '2020-07-03',
    'address' => 'Mimbaan, Panji',
    'parent_name' => 'Ranggi Agung Pernama',
    'email' => 'ziyanah@namira.school',
  ),
  51 => 
  array (
    'full_name' => 'Fatih Nizam Al Ghifari',
    'nickname' => 'Fatih',
    'section' => 'DATA PESERTA DIDIK TK B1 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-08-26',
    'address' => 'Curahsawo, Gending',
    'parent_name' => 'Dhany Prasetyo',
    'email' => 'fatih@namira.school',
  ),
  52 => 
  array (
    'full_name' => 'Unna Mikhayla Azzahra',
    'nickname' => 'Unna',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Lumajang',
    'dob' => '2020-12-27',
    'address' => 'Graha Pabean Asri',
    'parent_name' => 'Fandik Sanjaya',
    'email' => 'unna@namira.school',
  ),
  53 => 
  array (
    'full_name' => 'Almaa Uzma Medina',
    'nickname' => 'Almaa',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Kediri',
    'dob' => '2020-06-15',
    'address' => 'AwarAwar, Asembagus',
    'parent_name' => 'Ananda Citra Gama',
    'email' => 'almaa@namira.school',
  ),
  54 => 
  array (
    'full_name' => 'Atharazka Kenzie Al Ghifari',
    'nickname' => 'Kenzie',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Kediri',
    'dob' => '2020-05-31',
    'address' => 'Jati, Mayangan',
    'parent_name' => 'Adhitya Nur Rachmad',
    'email' => 'atharazka@namira.school',
  ),
  55 => 
  array (
    'full_name' => 'Davindra Arshaka Malik Akbar',
    'nickname' => 'Davin',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-01-02',
    'address' => 'Sumberagung, Dringu',
    'parent_name' => 'Naufal Labib Fawwarul Akbar',
    'email' => 'davindra@namira.school',
  ),
  56 => 
  array (
    'full_name' => 'Giandra Yumna Sugiarto',
    'nickname' => 'Yumna',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-06-06',
    'address' => 'Sumber Taman, Wonoasih',
    'parent_name' => 'Guntur Sugiarto',
    'email' => 'giandra@namira.school',
  ),
  57 => 
  array (
    'full_name' => 'Khalil Hanif Kasyafani',
    'nickname' => 'Hanif',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-04-11',
    'address' => 'Kalirejo, Dringu',
    'parent_name' => 'Galih Pratama',
    'email' => 'khalil@namira.school',
  ),
  58 => 
  array (
    'full_name' => 'Muhammad Fahreza Jaelani',
    'nickname' => 'Reza',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-12-29',
    'address' => 'Tegalrejo, Dringu',
    'parent_name' => 'Dioalib Utama',
    'email' => 'muhammad.11@namira.school',
  ),
  59 => 
  array (
    'full_name' => 'Muhammad Fathir Fauzi',
    'nickname' => 'Fathir',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-09-30',
    'address' => 'Sumber Kerang, Gending',
    'parent_name' => 'Ahmad Fauzi',
    'email' => 'muhammad.12@namira.school',
  ),
  60 => 
  array (
    'full_name' => 'Muhammad Gafi Jabarullah',
    'nickname' => 'Gafi',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2019-04-13',
    'address' => 'Kanigaran',
    'parent_name' => 'Dadan Arisandi',
    'email' => 'muhammad.13@namira.school',
  ),
  61 => 
  array (
    'full_name' => 'Muhammad Ibnu Sina Al Faruq',
    'nickname' => 'Faruq',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-02-14',
    'address' => 'Sukabumi, Mayangan',
    'parent_name' => 'Saiful Rachman',
    'email' => 'muhammad.14@namira.school',
  ),
  62 => 
  array (
    'full_name' => 'Muhammad Razka Attaqy Setyawan',
    'nickname' => 'Razka',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-07-26',
    'address' => 'Wiroborang, Mayangan',
    'parent_name' => 'Denny Setiawan',
    'email' => 'muhammad.15@namira.school',
  ),
  63 => 
  array (
    'full_name' => 'Rafa Yusroh Purnomo',
    'nickname' => 'Rafa',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Mojokerto',
    'dob' => '2020-07-29',
    'address' => 'Tanggalrejo, Mojoagung',
    'parent_name' => 'Edi Purnomo',
    'email' => 'rafa@namira.school',
  ),
  64 => 
  array (
    'full_name' => 'Rania Almahyra Farasya',
    'nickname' => 'Rania',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Situbondo',
    'dob' => '2021-06-04',
    'address' => 'Gudang, Asembagus',
    'parent_name' => 'Hasan Basri',
    'email' => 'rania@namira.school',
  ),
  65 => 
  array (
    'full_name' => 'Tanisha Almahyra',
    'nickname' => 'Tanisha',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2020-09-30',
    'address' => 'Mranggon Lawang, Dringu',
    'parent_name' => 'Hendri Susanto Wibowo',
    'email' => 'tanisha@namira.school',
  ),
  66 => 
  array (
    'full_name' => 'Tasneem Maheera',
    'nickname' => 'Tasneem',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Jepara',
    'dob' => '2021-03-07',
    'address' => 'Curahgrinting, Kanigaran',
    'parent_name' => 'Agus Hadianto',
    'email' => 'tasneem@namira.school',
  ),
  67 => 
  array (
    'full_name' => 'Zia Anindita Putri',
    'nickname' => 'Zia',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-01-26',
    'address' => 'Jati, Mayangan',
    'parent_name' => 'Effendi Sufaryono, SH',
    'email' => 'zia@namira.school',
  ),
  68 => 
  array (
    'full_name' => 'Reynand Arsenio Pramudya',
    'nickname' => 'Reynand',
    'section' => 'DATA PESERTA DIDIK TK B2 NAMIRA SCHOOL II',
    'unit_code' => 'TK-DRI',
    'unit_name' => 'TK Namira Dringu',
    'pob' => 'Probolinggo',
    'dob' => '2021-05-26',
    'address' => 'Pabean, Dringu',
    'parent_name' => 'Angga Pramudya',
    'email' => 'reynand@namira.school',
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
                $classroomName = $m[1];
            }

            $classroomId = null;
            if (!empty($classroomName)) {
                $cls = Classroom::where('unit_id', $unitId)
                    ->where('name', $classroomName)
                    ->first();
                if ($cls) {
                    $classroomId = $cls->id;
                }
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
        echo "Berhasil mengimpor 69 Siswa TK Namira Dringu!\n";
    }
}

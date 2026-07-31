<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherKbDringuSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::whereIn('code', ['KB-DRI', 'PAUD-DRI'])
            ->orWhere('name', 'LIKE', '%KB%Dringu%')
            ->orWhere('name', 'LIKE', '%PAUD%Dringu%')
            ->first();
        if (!$unit) {
            echo "Unit KB Namira Dringu tidak ditemukan.\n";
            return;
        }
        $unitId = $unit->id;

        $role = DB::table('roles')->where('name', 'teacher')->first();
        $roleId = $role ? $role->id : null;

        $teachersData = array (
  0 => 
  array (
    'nama' => 'Dwi Maulianita',
    'NIY' => '',
    'email' => 'dwi@namira.school',
    'no_hp' => '',
    'unit' => 'KB NAMIRA DRINGU',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  1 => 
  array (
    'nama' => 'Fatimatus Zahro',
    'NIY' => '',
    'email' => 'fatimatus@namira.school',
    'no_hp' => '',
    'unit' => 'KB NAMIRA DRINGU',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  2 => 
  array (
    'nama' => 'Agustyana Dyah Pitaloka',
    'NIY' => '',
    'email' => 'agustyana@namira.school',
    'no_hp' => '',
    'unit' => 'KB NAMIRA DRINGU',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  3 => 
  array (
    'nama' => 'Ine Meilina Putri',
    'NIY' => '',
    'email' => 'ine@namira.school',
    'no_hp' => '',
    'unit' => 'KB NAMIRA DRINGU',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
  4 => 
  array (
    'nama' => 'Rensia Yuliati Pratama',
    'NIY' => '',
    'email' => 'rensia@namira.school',
    'no_hp' => '',
    'unit' => 'KB NAMIRA DRINGU',
    'ket' => '',
    'keterangan_email' => 'Email dummy',
  ),
);

        foreach ($teachersData as $item) {
            $email = trim($item['email']);
            $name = trim($item['nama']);
            $niy = trim($item['NIY']);
            $phone = trim($item['no_hp']);
            $gender = 'P'; // All female names listed

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
        echo "Berhasil mengimpor 5 Guru KB Namira Dringu!\n";
    }
}

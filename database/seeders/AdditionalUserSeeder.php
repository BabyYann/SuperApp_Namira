<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdditionalUserSeeder extends Seeder
{
    public function run()
    {
        // 1. Ensure Roles Exist
        $roles = [
            'staff_yayasan',      // Staf biasa
            'koordinator_kurikulum', 
            'staff_admin_keuangan',
            'finance',
            'wali_kelas'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Create Sample Users

        // A. Staf Yayasan
        $staf = User::updateOrCreate(
            ['email' => 'staf@namira.school'],
            [
                'name' => 'Staf Yayasan A',
                'password' => Hash::make('password'),
            ]
        );
        $staf->syncRoles(['staff_yayasan']);
        $this->command->info('Created/Updated User: staf@namira.school [staff_yayasan]');

        // B. Koordinator Kurikulum
        $kurikulum = User::updateOrCreate(
            ['email' => 'kurikulum@namira.school'],
            [
                'name' => 'Koordinator Kurikulum',
                'password' => Hash::make('password'),
            ]
        );
        $kurikulum->syncRoles(['koordinator_kurikulum']);
        $this->command->info('Created/Updated User: kurikulum@namira.school [koordinator_kurikulum]');

        // C. Keuangan
        $keuangan = User::updateOrCreate(
            ['email' => 'keuangan@namira.school'],
            [
                'name' => 'Staf Keuangan',
                'password' => Hash::make('password'),
            ]
        );
        $keuangan->syncRoles(['staff_admin_keuangan']);
        $this->command->info('Created/Updated User: keuangan@namira.school [staff_admin_keuangan]');
    }
}

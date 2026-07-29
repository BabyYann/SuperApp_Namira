<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin Yayasan
        $superAdmin = User::firstOrCreate(
            ['email' => 'rianbru18@gmail.com'],
            [
                'name' => 'Super Admin Yayasan',
                'password' => Hash::make('NamiraApp@2025!'),
            ]
        );
        
        $superAdmin->assignRole('super_admin_yayasan');
    }
}

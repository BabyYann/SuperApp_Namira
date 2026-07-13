<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Yayasan\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'app_name', 'value' => 'SuperApp Namira', 'type' => 'text', 'group' => 'general'],
            ['key' => 'app_logo', 'value' => '', 'type' => 'image', 'group' => 'general'],
            ['key' => 'app_favicon', 'value' => '', 'type' => 'image', 'group' => 'general'],
            
            // Contact
            ['key' => 'contact_email', 'value' => 'info@namira.school', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'contact_phone', 'value' => '+62 811-2222-3333', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'address', 'value' => 'Jl. Pendidikan No. 1, Kota', 'type' => 'text', 'group' => 'contact'],

            // System Control
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'maintenance_message', 'value' => 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', 'type' => 'text', 'group' => 'system'],

            // Feature Toggles (1 = ON, 0 = OFF)
            ['key' => 'feature_finance', 'value' => '1', 'type' => 'boolean', 'group' => 'features'],
            ['key' => 'feature_sarpar', 'value' => '1', 'type' => 'boolean', 'group' => 'features'],
            ['key' => 'feature_counseling', 'value' => '1', 'type' => 'boolean', 'group' => 'features'],
            ['key' => 'feature_academic', 'value' => '1', 'type' => 'boolean', 'group' => 'features'],
            ['key' => 'feature_employee', 'value' => '1', 'type' => 'boolean', 'group' => 'features'],
            ['key' => 'feature_student_login', 'value' => '1', 'type' => 'boolean', 'group' => 'features'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Kantor Yayasan', 'code' => 'YAYASAN', 'category' => 'formal', 'level' => 'YAYASAN'],
            ['name' => 'KB Namira Kraksaan', 'code' => 'KB-KRA', 'category' => 'formal', 'level' => 'TK'],
            ['name' => 'KB Namira Dringu', 'code' => 'KB-DRI', 'category' => 'formal', 'level' => 'TK'],
            ['name' => 'TK Namira Kraksaan', 'code' => 'TK-KRA', 'category' => 'formal', 'level' => 'TK'],
            ['name' => 'TK Namira Dringu', 'code' => 'TK-DRI', 'category' => 'formal', 'level' => 'TK'],
            ['name' => 'SD Namira', 'code' => 'SD', 'category' => 'formal', 'level' => 'SD'],
            ['name' => 'SMP Namira', 'code' => 'SMP', 'category' => 'formal', 'level' => 'SMP'],
            ['name' => 'Pavlov Center', 'code' => 'PAVLOV', 'category' => 'non-formal', 'level' => 'NON-FORMAL'],
            ['name' => 'Day Care', 'code' => 'DAY', 'category' => 'non-formal', 'level' => 'DAYCARE'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}

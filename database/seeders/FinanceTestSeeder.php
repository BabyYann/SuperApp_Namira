<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Finance\Models\FinanceType;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\Unit;

class FinanceTestSeeder extends Seeder
{
    public function run()
    {
        // 1. Get Active Unit (Assuming first unit or ID 3 based on context)
        $unit = Unit::first();
        if (!$unit) {
            $this->command->error('No Unit found! Please seed units first.');
            return;
        }

        // 2. Create Finance Types
        $spp = FinanceType::firstOrCreate(
            ['name' => 'SPP Bulanan', 'unit_id' => $unit->id],
            [
                'amount' => 150000,
                'billing_cycle' => 'monthly',
                'is_active' => true
            ]
        );

        $gedung = FinanceType::firstOrCreate(
            ['name' => 'Uang Gedung', 'unit_id' => $unit->id],
            [
                'amount' => 1000000,
                'billing_cycle' => 'once',
                'is_active' => true
            ]
        );

        $this->command->info('Finance Types created/verified.');

        // 3. Create Finance Accounts
        FinanceAccount::firstOrCreate(
            ['account_number' => '1234567890'],
            [
                'bank_name' => 'Bank Jatim',
                'account_name' => 'Yayasan Namira Ops',
                'is_active' => true
            ]
        );
        
        $this->command->info('Finance Accounts created/verified.');

        // 4. Update Students with VA Number if missing
        $students = Student::where('unit_id', $unit->id)->take(10)->get();
        foreach ($students as $index => $student) {
            if (!$student->va_number) {
                // Mock VA: 8888 + UnitID + NIS
                $student->va_number = '8888' . str_pad($unit->id, 2, '0', STR_PAD_LEFT) . $student->nis;
                $student->save();
            }
        }
        $this->command->info("Updated {$students->count()} students with mocked VA Numbers.");
    }
}

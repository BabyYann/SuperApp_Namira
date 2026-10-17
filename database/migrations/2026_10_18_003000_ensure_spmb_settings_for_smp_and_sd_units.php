<?php

use App\Modules\SPMB\Models\SpmbSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('spmb_settings') && Schema::hasTable('units')) {
            // 1. Ensure SMP Namira SPMB setting exists and is active
            $smp = Unit::where('code', 'SMP')->orWhere('name', 'like', '%SMP%')->first();
            if ($smp) {
                SpmbSetting::updateOrCreate(
                    ['unit_id' => $smp->id],
                    [
                        'name' => 'Pendaftaran Jalur Inden SMP Namira',
                        'academic_year' => '2026/2027',
                        'quota' => 60,
                        'registration_fee' => 250000,
                        'bank_name' => 'Bank Jatim',
                        'bank_account_number' => '0291008899',
                        'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                        'admission_fee_total' => 8500000,
                        'min_down_payment_percentage' => 60,
                        'is_active' => true,
                        'contact_whatsapp' => '082332922521',
                    ]
                );
            }

            // 2. Ensure SD Namira SPMB setting exists and is active
            $sd = Unit::where('code', 'SD')->orWhere('name', 'like', '%SD%')->first();
            if ($sd) {
                SpmbSetting::firstOrCreate(
                    ['unit_id' => $sd->id],
                    [
                        'name' => 'Pendaftaran Jalur Inden SD Namira',
                        'academic_year' => '2026/2027',
                        'quota' => 60,
                        'registration_fee' => 250000,
                        'bank_name' => 'Bank Jatim',
                        'bank_account_number' => '0291008899',
                        'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                        'admission_fee_total' => 8500000,
                        'min_down_payment_percentage' => 60,
                        'is_active' => true,
                        'contact_whatsapp' => '082332922521',
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive rollback needed
    }
};

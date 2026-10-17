<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('spmb_settings')) {
            DB::table('spmb_settings')
                ->where('bank_account_holder', 'YAYASAN NAMIRA PROBOLINGGO')
                ->orWhereNull('bank_account_holder')
                ->orWhere('bank_account_holder', '')
                ->update([
                    'bank_account_holder' => 'NAMIRA SCHOOL KOTA PROBOLINGGO',
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('spmb_settings')) {
            DB::table('spmb_settings')
                ->where('bank_account_holder', 'NAMIRA SCHOOL KOTA PROBOLINGGO')
                ->update([
                    'bank_account_holder' => 'YAYASAN NAMIRA PROBOLINGGO',
                ]);
        }
    }
};

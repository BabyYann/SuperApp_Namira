<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('units') || !Schema::hasTable('spmb_settings')) {
            return;
        }

        $smpUnitIds = DB::table('units')
            ->where('code', 'SMP')
            ->orWhere('name', 'like', '%SMP%')
            ->pluck('id');

        if ($smpUnitIds->isEmpty()) {
            return;
        }

        DB::table('spmb_settings')
            ->whereIn('unit_id', $smpUnitIds)
            ->update([
                'min_down_payment_percentage' => 100,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('spmb_applicants')) {
            DB::table('spmb_applicants')
                ->whereIn('unit_id', $smpUnitIds)
                ->whereNotIn('status', ['partial_paid', 'fully_paid', 'enrolled', 'rejected'])
                ->whereNotNull('total_admission_fee')
                ->where('total_admission_fee', '>', 0)
                ->update([
                    'min_down_payment' => DB::raw('total_admission_fee'),
                    'full_payment_deadline' => DB::raw('COALESCE(full_payment_deadline, down_payment_deadline)'),
                    'down_payment_deadline' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('units') || !Schema::hasTable('spmb_settings')) {
            return;
        }

        $smpUnitIds = DB::table('units')
            ->where('code', 'SMP')
            ->orWhere('name', 'like', '%SMP%')
            ->pluck('id');

        if ($smpUnitIds->isEmpty()) {
            return;
        }

        DB::table('spmb_settings')
            ->whereIn('unit_id', $smpUnitIds)
            ->update([
                'min_down_payment_percentage' => 60,
                'updated_at' => now(),
            ]);
    }
};

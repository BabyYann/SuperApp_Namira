<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spmb_settings') && ! Schema::hasColumn('spmb_settings', 'closed_academic_years')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                $table->json('closed_academic_years')->nullable()->after('inden_academic_years');
            });
        }

        // Mark SD Namira Kota Probolinggo (SD-02) TA 2027/2028 as closed / full while keeping future Inden years open
        if (Schema::hasTable('units') && Schema::hasTable('spmb_settings')) {
            $sdKotaUnits = DB::table('units')
                ->where('code', 'SD-02')
                ->orWhere(function ($q) {
                    $q->where('name', 'like', '%SD%')
                        ->where('name', 'like', '%KOTA%');
                })
                ->get(['id']);

            foreach ($sdKotaUnits as $unit) {
                $setting = DB::table('spmb_settings')->where('unit_id', $unit->id)->first();
                if (! $setting) {
                    continue;
                }

                $existingClosed = [];
                if (! empty($setting->closed_academic_years)) {
                    $decoded = json_decode((string) $setting->closed_academic_years, true);
                    if (is_array($decoded)) {
                        $existingClosed = $decoded;
                    }
                }

                if (! in_array('2027/2028', $existingClosed, true)) {
                    $existingClosed[] = '2027/2028';
                }
                sort($existingClosed);

                $updates = [
                    'closed_academic_years' => json_encode(array_values(array_unique($existingClosed))),
                    'allow_future_inden' => true,
                    'updated_at' => now(),
                ];

                $existingFuture = ! empty($setting->inden_academic_years)
                    ? json_decode((string) $setting->inden_academic_years, true)
                    : null;
                if (! is_array($existingFuture) || empty($existingFuture)) {
                    $updates['inden_academic_years'] = json_encode(['2028/2029', '2029/2030', '2030/2031']);
                }

                DB::table('spmb_settings')
                    ->where('id', $setting->id)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('spmb_settings') && Schema::hasColumn('spmb_settings', 'closed_academic_years')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                $table->dropColumn('closed_academic_years');
            });
        }
    }
};

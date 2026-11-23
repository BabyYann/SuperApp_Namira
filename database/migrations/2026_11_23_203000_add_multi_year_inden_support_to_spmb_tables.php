<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add academic_year column to spmb_applicants so every applicant stores their exact target intake year
        if (Schema::hasTable('spmb_applicants')) {
            Schema::table('spmb_applicants', function (Blueprint $table) {
                if (! Schema::hasColumn('spmb_applicants', 'academic_year')) {
                    $table->string('academic_year', 20)
                        ->nullable()
                        ->after('unit_id')
                        ->comment('Target tahun ajaran masuk pendaftar, misal 2027/2028, 2028/2029, 2029/2030');
                    $table->index(['unit_id', 'academic_year'], 'spmb_applicants_unit_academic_year_idx');
                }
            });
        }

        // 2. Add future-year Inden configuration columns to spmb_settings
        if (Schema::hasTable('spmb_settings')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('spmb_settings', 'allow_future_inden')) {
                    $table->boolean('allow_future_inden')
                        ->default(true)
                        ->after('academic_year')
                        ->comment('Izinkan pendaftar memilih tahun ajaran mendatang (Jalur Inden)');
                }
                if (! Schema::hasColumn('spmb_settings', 'inden_academic_years')) {
                    $table->json('inden_academic_years')
                        ->nullable()
                        ->after('allow_future_inden')
                        ->comment('Daftar tahun ajaran mendatang yang dibuka untuk jalur inden, misal ["2028/2029","2029/2030","2030/2031"]');
                }
            });
        }

        // 3. Seed/configure default multi-year Inden options & prepare locked TK/KB settings
        if (Schema::hasTable('units') && Schema::hasTable('spmb_settings')) {
            $defaultFutureYears = json_encode(['2028/2029', '2029/2030', '2030/2031']);

            // Backfill spmb_applicants.academic_year from unit settings (fallback 2027/2028)
            if (Schema::hasTable('spmb_applicants') && Schema::hasColumn('spmb_applicants', 'academic_year')) {
                $settingsMap = DB::table('spmb_settings')->pluck('academic_year', 'unit_id');
                $unitIds = DB::table('spmb_applicants')
                    ->whereNull('academic_year')
                    ->distinct()
                    ->pluck('unit_id');

                foreach ($unitIds as $unitId) {
                    $settingYear = (string) ($settingsMap[$unitId] ?? '2027/2028');
                    if ($settingYear === '' || $settingYear === '2026/2027') {
                        $settingYear = '2027/2028';
                    }
                    DB::table('spmb_applicants')
                        ->where('unit_id', $unitId)
                        ->whereNull('academic_year')
                        ->update(['academic_year' => $settingYear]);
                }
            }

            // Enable future-year Inden on SD, SD-02, SMP
            $activeSchoolUnitIds = DB::table('units')
                ->whereIn(DB::raw('UPPER(code)'), ['SD', 'SD-02', 'SMP'])
                ->pluck('id');

            if ($activeSchoolUnitIds->isNotEmpty()) {
                DB::table('spmb_settings')
                    ->whereIn('unit_id', $activeSchoolUnitIds)
                    ->whereNull('inden_academic_years')
                    ->update([
                        'allow_future_inden' => true,
                        'inden_academic_years' => $defaultFutureYears,
                        'updated_at' => now(),
                    ]);
            }

            // Prepare TK & KB settings in the backend (LOCKED for public: is_active = false until admin unlocks!)
            $tkKbUnits = DB::table('units')
                ->where(function ($q) {
                    $q->whereIn(DB::raw('UPPER(code)'), ['TK-KRA', 'TK-DRI', 'KB-KRA', 'KB-DRI', 'TK', 'KB'])
                        ->orWhere(DB::raw('UPPER(category)'), 'TK');
                })
                ->get();

            foreach ($tkKbUnits as $tkUnit) {
                $existing = DB::table('spmb_settings')->where('unit_id', $tkUnit->id)->first();
                if (! $existing) {
                    DB::table('spmb_settings')->insert([
                        'unit_id' => $tkUnit->id,
                        'name' => 'Pendaftaran Murid Baru ' . $tkUnit->name,
                        'academic_year' => '2027/2028',
                        'allow_future_inden' => true,
                        'inden_academic_years' => $defaultFutureYears,
                        'quota' => 40,
                        'registration_fee' => 250000,
                        'bank_name' => 'Bank Jatim',
                        'bank_account_number' => null,
                        'bank_account_holder' => strtoupper($tkUnit->name),
                        'admission_fee_total' => 4500000,
                        'min_down_payment_percentage' => 60,
                        'is_active' => false, // Locked for public until unlocked by Admin in Settings
                        'announcement_released' => false,
                        'contact_whatsapp' => '082332922521',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $updates = [
                        'allow_future_inden' => true,
                        'is_active' => false, // Keep TK/KB locked for public until explicitly unlocked
                        'updated_at' => now(),
                    ];
                    if (empty($existing->inden_academic_years)) {
                        $updates['inden_academic_years'] = $defaultFutureYears;
                    }
                    if ($existing->academic_year === '2026/2027') {
                        $updates['academic_year'] = '2027/2028';
                    }
                    if (str_contains(strtolower((string) $existing->name), 'sd namira')) {
                        $updates['name'] = 'Pendaftaran Murid Baru ' . $tkUnit->name;
                    }
                    DB::table('spmb_settings')->where('id', $existing->id)->update($updates);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('spmb_settings')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                if (Schema::hasColumn('spmb_settings', 'inden_academic_years')) {
                    $table->dropColumn('inden_academic_years');
                }
                if (Schema::hasColumn('spmb_settings', 'allow_future_inden')) {
                    $table->dropColumn('allow_future_inden');
                }
            });
        }

        if (Schema::hasTable('spmb_applicants')) {
            Schema::table('spmb_applicants', function (Blueprint $table) {
                if (Schema::hasColumn('spmb_applicants', 'academic_year')) {
                    $table->dropIndex('spmb_applicants_unit_academic_year_idx');
                    $table->dropColumn('academic_year');
                }
            });
        }
    }
};

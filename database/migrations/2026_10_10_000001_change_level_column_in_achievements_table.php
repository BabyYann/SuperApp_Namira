<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `achievements` MODIFY COLUMN `level` VARCHAR(50) NOT NULL DEFAULT 'Sekolah'");
        } else {
            Schema::table('achievements', function (Blueprint $table) {
                $table->string('level', 50)->default('Sekolah')->change();
            });
        }

        // Normalize existing rows to clean Title Case format
        $mapping = [
            'sekolah' => 'Sekolah',
            'kecamatan' => 'Kecamatan',
            'kabupaten' => 'Kabupaten/Kota',
            'kabupaten/kota' => 'Kabupaten/Kota',
            'provinsi' => 'Provinsi',
            'nasional' => 'Nasional',
            'internasional' => 'Internasional',
        ];

        foreach ($mapping as $old => $new) {
            DB::table('achievements')
                ->where('level', $old)
                ->update(['level' => $new]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `achievements` MODIFY COLUMN `level` ENUM('sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional') NOT NULL DEFAULT 'sekolah'");
        } else {
            Schema::table('achievements', function (Blueprint $table) {
                $table->string('level')->change();
            });
        }
    }
};

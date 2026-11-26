<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quran_groups')) {
            return;
        }

        $driver = DB::getDriverName();

        if (!Schema::hasColumn('quran_groups', 'is_inclusive')) {
            Schema::table('quran_groups', function (Blueprint $table) {
                $table->boolean('is_inclusive')->default(false)->after('level_id');
                $table->index(['unit_id', 'is_inclusive'], 'quran_groups_unit_inclusive_idx');
            });
        }

        if ($driver === 'sqlite') {
            Schema::table('quran_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->change();
            });
        } else {
            Schema::table('quran_groups', function (Blueprint $table) {
                $table->dropForeign(['level_id']);
            });

            Schema::table('quran_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->change();
                $table->foreign('level_id')->references('id')->on('quran_levels')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('quran_groups')) {
            return;
        }

        $driver = DB::getDriverName();

        if (Schema::hasColumn('quran_groups', 'is_inclusive')) {
            Schema::table('quran_groups', function (Blueprint $table) {
                $table->dropIndex('quran_groups_unit_inclusive_idx');
                $table->dropColumn('is_inclusive');
            });
        }

        if ($driver === 'sqlite') {
            Schema::table('quran_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable(false)->change();
            });
        } else {
            Schema::table('quran_groups', function (Blueprint $table) {
                $table->dropForeign(['level_id']);
            });

            Schema::table('quran_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable(false)->change();
                $table->foreign('level_id')->references('id')->on('quran_levels')->cascadeOnDelete();
            });
        }
    }
};

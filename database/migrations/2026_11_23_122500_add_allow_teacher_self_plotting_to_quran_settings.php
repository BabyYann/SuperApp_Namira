<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quran_settings') && !Schema::hasColumn('quran_settings', 'allow_teacher_self_plotting')) {
            Schema::table('quran_settings', function (Blueprint $table) {
                $table->boolean('allow_teacher_self_plotting')
                    ->default(true)
                    ->after('allow_teacher_promote');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quran_settings') && Schema::hasColumn('quran_settings', 'allow_teacher_self_plotting')) {
            Schema::table('quran_settings', function (Blueprint $table) {
                $table->dropColumn('allow_teacher_self_plotting');
            });
        }
    }
};

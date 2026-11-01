<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spmb_settings') && !Schema::hasColumn('spmb_settings', 'whatsapp_group_link')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                $table->string('whatsapp_group_link', 500)->nullable()->after('contact_whatsapp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('spmb_settings') && Schema::hasColumn('spmb_settings', 'whatsapp_group_link')) {
            Schema::table('spmb_settings', function (Blueprint $table) {
                $table->dropColumn('whatsapp_group_link');
            });
        }
    }
};

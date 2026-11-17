<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('spmb_applicants', 'fee_breakdown')) {
            Schema::table('spmb_applicants', function (Blueprint $table) {
                $table->json('fee_breakdown')->nullable()->after('paid_admission_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('spmb_applicants', 'fee_breakdown')) {
            Schema::table('spmb_applicants', function (Blueprint $table) {
                $table->dropColumn('fee_breakdown');
            });
        }
    }
};

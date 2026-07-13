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
        Schema::table('students', function (Blueprint $table) {
            $table->string('pob')->nullable()->after('gender'); // Place of Birth
            $table->date('dob')->nullable()->after('pob');      // Date of Birth
            $table->text('address')->nullable()->after('dob');
            $table->string('parent_name')->nullable()->after('parent_phone');
            $table->string('guardian_name')->nullable()->after('parent_name');
            $table->string('guardian_phone')->nullable()->after('guardian_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['pob', 'dob', 'address', 'parent_name', 'guardian_name', 'guardian_phone']);
        });
    }
};

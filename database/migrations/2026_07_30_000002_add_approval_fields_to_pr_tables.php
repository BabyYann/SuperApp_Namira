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
        // Testimonials
        Schema::table('testimonials', function (Blueprint $table) {
            if (!Schema::hasColumn('testimonials', 'approval_status')) {
                $table->string('approval_status', 50)->default('published')->after('quote');
            }
            if (!Schema::hasColumn('testimonials', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('testimonials', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_note');
            }
            if (!Schema::hasColumn('testimonials', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Partners
        Schema::table('partners', function (Blueprint $table) {
            if (!Schema::hasColumn('partners', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null')->after('id');
            }
            if (!Schema::hasColumn('partners', 'approval_status')) {
                $table->string('approval_status', 50)->default('published')->after('website_url');
            }
            if (!Schema::hasColumn('partners', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('partners', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_note');
            }
            if (!Schema::hasColumn('partners', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('partners', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });

        // University Destinations
        Schema::table('university_destinations', function (Blueprint $table) {
            if (!Schema::hasColumn('university_destinations', 'approval_status')) {
                $table->string('approval_status', 50)->default('published')->after('description');
            }
            if (!Schema::hasColumn('university_destinations', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('university_destinations', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_note');
            }
            if (!Schema::hasColumn('university_destinations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('testimonials', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }
            if (Schema::hasColumn('testimonials', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('testimonials', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('partners', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
            if (Schema::hasColumn('partners', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('partners', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }
            if (Schema::hasColumn('partners', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('partners', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        Schema::table('university_destinations', function (Blueprint $table) {
            if (Schema::hasColumn('university_destinations', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('university_destinations', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }
            if (Schema::hasColumn('university_destinations', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('university_destinations', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};

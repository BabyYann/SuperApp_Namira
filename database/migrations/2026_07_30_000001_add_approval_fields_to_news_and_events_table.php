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
        // Modify news.status column to allow 'pending' and 'rejected'
        try {
            DB::statement("ALTER TABLE `news` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // Fallback if raw SQL differs
        }

        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('status');
            }
            if (!Schema::hasColumn('news', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_note');
            }
            if (!Schema::hasColumn('news', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        // Modify events.status column
        try {
            DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'upcoming'");
        } catch (\Throwable $e) {
            // Fallback
        }

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'approval_status')) {
                $table->string('approval_status')->default('published')->after('status');
            }
            if (!Schema::hasColumn('events', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('events', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_note');
            }
            if (!Schema::hasColumn('events', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('news', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }
            if (Schema::hasColumn('news', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('events', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('events', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }
            if (Schema::hasColumn('events', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};

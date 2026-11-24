<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_vacancies')) {
            Schema::table('job_vacancies', function (Blueprint $table) {
                if (! Schema::hasColumn('job_vacancies', 'poster_path')) {
                    $table->string('poster_path')->nullable()->after('title');
                }
                if (! Schema::hasColumn('job_vacancies', 'contact_whatsapp')) {
                    $table->string('contact_whatsapp', 30)->nullable()->after('deadline');
                }
                if (! Schema::hasColumn('job_vacancies', 'placement_address')) {
                    $table->string('placement_address', 500)->nullable()->after('contact_whatsapp');
                }
            });
        }

        if (Schema::hasTable('job_applicants')) {
            Schema::table('job_applicants', function (Blueprint $table) {
                if (! Schema::hasColumn('job_applicants', 'health_cert_path')) {
                    $table->string('health_cert_path')->nullable()->after('certificate_path');
                }
                if (! Schema::hasColumn('job_applicants', 'family_card_path')) {
                    $table->string('family_card_path')->nullable()->after('ktp_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_vacancies')) {
            Schema::table('job_vacancies', function (Blueprint $table) {
                $cols = array_filter(
                    ['poster_path', 'contact_whatsapp', 'placement_address'],
                    fn ($col) => Schema::hasColumn('job_vacancies', $col)
                );
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('job_applicants')) {
            Schema::table('job_applicants', function (Blueprint $table) {
                $cols = array_filter(
                    ['health_cert_path', 'family_card_path'],
                    fn ($col) => Schema::hasColumn('job_applicants', $col)
                );
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

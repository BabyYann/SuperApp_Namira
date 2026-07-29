<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ResetToProduction extends Command
{
    protected $signature = 'app:reset-to-production
                            {--force : Skip confirmation prompt}';

    protected $description = 'Hapus semua data dummy dan sisakan hanya akun Super Admin untuk siap production';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->warn('⚠️  PERINGATAN: Perintah ini akan menghapus SEMUA data kecuali:');
            $this->line('   - Akun Super Admin (rianbru18@gmail.com)');
            $this->line('   - Data Units (SD, SMP, dll)');
            $this->line('   - Roles & Permissions');
            $this->line('   - System Settings');
            $this->newLine();

            if (! $this->confirm('Apakah kamu yakin ingin melanjutkan? Proses ini tidak bisa dibatalkan!')) {
                $this->info('Dibatalkan.');
                return self::FAILURE;
            }
        }

        $this->info('🚀 Memulai reset database untuk production...');
        $this->newLine();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // ─── Modul Akademik ──────────────────────────────────────────────
        $this->cleanTable('teaching_journals');
        $this->cleanTable('teaching_journal_details');
        $this->cleanTable('student_attendances');
        $this->cleanTable('teacher_attendances');
        $this->cleanTable('checkin_logs');
        $this->cleanTable('schedules');
        $this->cleanTable('classrooms');
        $this->cleanTable('academic_years');
        $this->cleanTable('subjects');
        $this->cleanTable('class_promotions');
        $this->cleanTable('learning_objectives');

        // ─── Siswa ────────────────────────────────────────────────────────
        $this->cleanTable('student_documents');
        $this->cleanTable('student_parents');
        $this->cleanTable('students');

        // ─── Pegawai / Employee ───────────────────────────────────────────
        $this->cleanTable('employee_attendances');
        $this->cleanTable('employees');

        // ─── Keuangan / Finance ───────────────────────────────────────────
        $this->cleanTable('finance_transactions');
        $this->cleanTable('bill_items');
        $this->cleanTable('finance_bills');
        $this->cleanTable('finance_accounts');
        $this->cleanTable('finance_types');

        // ─── Sarpras & Aset ───────────────────────────────────────────────
        $this->cleanTable('sarpar_maintenance_logs');
        $this->cleanTable('sarpar_loan_items');
        $this->cleanTable('sarpar_loans');
        $this->cleanTable('sarpar_inventories');
        $this->cleanTable('sarpar_rooms');
        $this->cleanTable('sarpar_categories');

        // ─── BK / Konseling ───────────────────────────────────────────────
        $this->cleanTable('counseling_sessions');
        $this->cleanTable('counseling_violations');
        $this->cleanTable('counseling_achievements');

        // ─── LMS ──────────────────────────────────────────────────────────
        $this->cleanTable('lms_submissions');
        $this->cleanTable('lms_materials');
        $this->cleanTable('lms_assignments');
        $this->cleanTable('lms_courses');

        // ─── Humas / Public Relations ─────────────────────────────────────
        $this->cleanTable('alumni');
        $this->cleanTable('university_destinations');
        $this->cleanTable('news');
        $this->cleanTable('events');
        $this->cleanTable('gallery_items');
        $this->cleanTable('testimonials');

        // ─── Notifikasi ───────────────────────────────────────────────────
        $this->cleanTable('notifications');

        // ─── Hapus semua user KECUALI Super Admin ─────────────────────────
        $this->info('  🧹 Membersihkan users (kecuali Super Admin)...');
        $superAdmin = User::where('email', 'rianbru18@gmail.com')->first();

        if ($superAdmin) {
            // Hapus role semua user lain dulu
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', '!=', $superAdmin->id)
                ->delete();

            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->where('model_id', '!=', $superAdmin->id)
                ->delete();

            // Hapus user selain superadmin
            User::where('id', '!=', $superAdmin->id)->delete();

            // Pastikan password & nama superadmin sudah benar
            $superAdmin->update([
                'name'     => 'Super Admin Yayasan',
                'password' => Hash::make('NamiraApp@2025!'),
            ]);

            $this->line('  ✅ Super Admin dipertahankan: rianbru18@gmail.com');
        } else {
            $this->warn('  ⚠️  Akun superadmin tidak ditemukan! Menjalankan UserSeeder...');
            $this->call('db:seed', ['--class' => 'UserSeeder']);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ─── Bersihkan cache ──────────────────────────────────────────────
        $this->info('  🧹 Membersihkan cache...');
        $this->call('cache:clear');
        $this->call('config:clear');

        $this->newLine();
        $this->info('✅ Database berhasil direset untuk production!');
        $this->newLine();
        $this->table(
            ['Akun', 'Email', 'Password'],
            [['Super Admin Yayasan', 'rianbru18@gmail.com', 'NamiraApp@2025!']]
        );
        $this->newLine();
        $this->warn('⚠️  Segera ubah password setelah login pertama kali di production!');

        return self::SUCCESS;
    }

    private function cleanTable(string $table): void
    {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            DB::table($table)->truncate();
            $this->line("  ✅ Tabel <info>{$table}</info> dibersihkan");
        } else {
            $this->line("  ⏭️  Tabel <comment>{$table}</comment> tidak ditemukan, dilewati");
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mendeteksi dan memperbaiki inkonsistensi unit pada akun user (Double Unit Bug).
 * 
 * Kasus: User diatur ke suatu unit (mis. TK), namun masih memiliki record guru di SD
 * atau staff di unit lain, sehingga User::getUnitsAttribute() mengembalikan 2 unit berbeda
 * dan menyebabkan user muncul di dua unit sekaligus (double unit).
 */
class FixDoubleUnitCommand extends Command
{
    protected $signature = 'users:fix-double-unit
                            {--email= : Filter berdasarkan email pengguna}
                            {--user-id= : Filter berdasarkan ID pengguna}
                            {--unit-id= : Paksa seluruh profil pegawai user ke ID unit ini}
                            {--execute : Terapkan perubahan ke database (tanpa ini hanya dry-run)}';

    protected $description = 'Deteksi dan perbaiki akun pengguna yang memiliki profil di beberapa unit berbeda (double unit). Default dry-run.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $email = $this->option('email');
        $userId = $this->option('user-id');
        $forcedUnitId = $this->option('unit-id') ? (int) $this->option('unit-id') : null;

        $this->line('');
        $this->info($execute
            ? '⚙️  MODE EKSEKUSI — Perubahan akan disimpan ke database.'
            : '🔍 MODE DRY-RUN — Hanya mendeteksi. Jalankan dengan --execute untuk menerapkan perbaikan.');
        $this->line('');

        $query = User::query();
        if ($email) {
            $query->where('email', $email);
        }
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $units = Unit::all()->keyBy('id');

        $issues = [];

        foreach ($users as $user) {
            $roleTeamIds = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->whereNotNull('team_id')
                ->pluck('team_id')
                ->unique()
                ->toArray();

            $teacherRecords = DB::table('teachers')
                ->where('user_id', $user->id)
                ->get(['id', 'unit_id', 'full_name']);

            $staffRecords = DB::table('staff')
                ->where('user_id', $user->id)
                ->get(['id', 'unit_id', 'full_name']);

            $studentRecords = DB::table('students')
                ->where('user_id', $user->id)
                ->get(['id', 'unit_id', 'full_name']);

            $teacherUnitIds = $teacherRecords->pluck('unit_id')->unique()->toArray();
            $staffUnitIds = $staffRecords->pluck('unit_id')->unique()->toArray();
            $studentUnitIds = $studentRecords->pluck('unit_id')->unique()->toArray();

            // Kumpulkan seluruh unit yang terkait dengan pegawai / peran
            $employeeUnitIds = array_values(array_unique(array_filter(array_merge($roleTeamIds, $teacherUnitIds, $staffUnitIds))));

            // Konflik terjadi jika profil pegawai atau perannya menyebar di lebih dari 1 unit
            if (count($employeeUnitIds) > 1) {
                // Tentukan target unit yang benar
                $targetUnitId = $forcedUnitId;
                if (!$targetUnitId) {
                    if (count($roleTeamIds) === 1) {
                        $targetUnitId = $roleTeamIds[0];
                    } elseif (count($staffUnitIds) === 1 && !empty($staffRecords)) {
                        $targetUnitId = $staffUnitIds[0];
                    } elseif (count($teacherUnitIds) === 1 && !empty($teacherRecords)) {
                        $targetUnitId = $teacherUnitIds[0];
                    } else {
                        $targetUnitId = $employeeUnitIds[0];
                    }
                }

                $issues[] = [
                    'user' => $user,
                    'role_team_ids' => $roleTeamIds,
                    'teacher_records' => $teacherRecords,
                    'staff_records' => $staffRecords,
                    'student_records' => $studentRecords,
                    'all_employee_units' => $employeeUnitIds,
                    'target_unit_id' => $targetUnitId,
                ];
            }
        }

        if (empty($issues)) {
            $this->info('✅ Tidak ditemukan pengguna dengan konflik unit ganda (double unit). Data pegawai bersih.');
            return self::SUCCESS;
        }

        $this->warn(sprintf('Ditemukan %d akun dengan konflik unit ganda:', count($issues)));
        $this->line('');

        $fixedCount = 0;

        foreach ($issues as $issue) {
            /** @var User $user */
            $user = $issue['user'];
            $targetUnitId = $issue['target_unit_id'];
            $targetUnitName = $units[$targetUnitId]->name ?? "Unit #{$targetUnitId}";

            $this->line(str_repeat('─', 80));
            $this->line(sprintf('👤 <fg=cyan>User #%d: %s</> (%s)', $user->id, $user->name, $user->email));

            // Tampilkan detail peran & profil
            if (!empty($issue['role_team_ids'])) {
                $roleNames = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_id', $user->id)
                    ->select('roles.name', 'model_has_roles.team_id')
                    ->get()
                    ->map(fn($r) => "{$r->name} (Unit " . ($units[$r->team_id]->name ?? $r->team_id) . ")")
                    ->implode(', ');
                $this->line("   - Peran/Roles : {$roleNames}");
            }

            foreach ($issue['teacher_records'] as $tr) {
                $uName = $units[$tr->unit_id]->name ?? "Unit #{$tr->unit_id}";
                $this->line("   - Profil Guru : #{$tr->id} {$tr->full_name} · <fg=yellow>{$uName}</>");
            }

            foreach ($issue['staff_records'] as $sr) {
                $uName = $units[$sr->unit_id]->name ?? "Unit #{$sr->unit_id}";
                $this->line("   - Profil Staff: #{$sr->id} {$sr->full_name} · <fg=yellow>{$uName}</>");
            }

            $this->line(sprintf('   ➔ Target Unit : <fg=green;options=bold>%s (ID: %d)</>', $targetUnitName, $targetUnitId));

            if ($execute) {
                DB::transaction(function () use ($user, $targetUnitId) {
                    // 1. Update roles team_id (kecuali global roles)
                    $globalRoleNames = ['super_admin_yayasan', 'admin_yayasan', 'staff_yayasan', 'pengawas_yayasan', 'humas_yayasan'];
                    $globalRoleIds = DB::table('roles')->whereIn('name', $globalRoleNames)->pluck('id')->toArray();

                    DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->whereNotIn('role_id', $globalRoleIds)
                        ->update(['team_id' => $targetUnitId]);

                    // 2. Update teachers table
                    DB::table('teachers')
                        ->where('user_id', $user->id)
                        ->update([
                            'unit_id' => $targetUnitId,
                            'full_name' => $user->name,
                        ]);

                    // 3. Update staff table
                    DB::table('staff')
                        ->where('user_id', $user->id)
                        ->update([
                            'unit_id' => $targetUnitId,
                            'full_name' => $user->name,
                        ]);
                });

                $this->info("   ✓ Berhasil diperbaiki ke unit {$targetUnitName}.");
                $fixedCount++;
            } else {
                $this->line("   [DRY-RUN] Akan disinkronkan ke unit {$targetUnitName}.");
            }
        }

        $this->line(str_repeat('─', 80));
        $this->line('');

        if ($execute) {
            $this->info("🎉 Selesai! Sebanyak {$fixedCount} akun telah diperbaiki. Double unit telah teratasi.");
        } else {
            $this->comment("💡 Jalankan kembali dengan flag --execute untuk menerapkan perbaikan di atas:");
            $this->line("   php artisan users:fix-double-unit --execute");
        }

        return self::SUCCESS;
    }
}

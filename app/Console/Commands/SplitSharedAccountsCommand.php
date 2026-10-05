<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Memisahkan akun user yang "dipakai bersama" oleh lebih dari satu orang.
 *
 * Latar belakang: beberapa seeder siswa/guru mencari user berdasarkan email
 * (User::where('email', ...)->first()). Bila email yang sama dipakai oleh dua
 * orang berbeda (mis. guru TK & siswa SMP sama-sama "nabila@namira.school"),
 * record siswa ikut menempel ke akun yang sudah ada. Akibatnya satu akun
 * muncul di beberapa unit dan tidak bisa dibersihkan dari halaman Edit User.
 *
 * Aturan pemilik akun:
 *  - Jika akun punya profil guru / staff  -> akun tetap milik pegawai,
 *    SEMUA record siswa dipindah ke akun baru.
 *  - Jika akun hanya berisi siswa          -> siswa yang namanya sama dengan
 *    nama akun (atau id terkecil) tetap memiliki akun, sisanya dipindah.
 *
 * Data siswa lain (presensi, nilai, tagihan, kelas) mengacu ke student_id,
 * sehingga otomatis ikut pindah. Tidak ada data yang dihapus.
 */
class SplitSharedAccountsCommand extends Command
{
    protected $signature = 'users:split-shared-accounts
                            {--email= : Batasi hanya untuk satu email (mis. nabila@namira.school)}
                            {--execute : Benar-benar menerapkan perubahan (tanpa ini hanya dry-run)}
                            {--domain=namira.school : Domain email untuk akun siswa baru}';

    protected $description = 'Deteksi & pisahkan akun yang terhubung ke lebih dari satu orang (guru+siswa / beberapa siswa). Default dry-run.';

    private const STUDENT_ROLES = ['siswa', 'student'];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $domain = ltrim((string) $this->option('domain'), '@');

        $this->line('');
        $this->info($execute
            ? '⚙️  MODE EKSEKUSI — perubahan akan disimpan ke database.'
            : '🔍 MODE DRY-RUN — tidak ada perubahan yang disimpan. Tambahkan --execute untuk menerapkan.');
        $this->line('');

        $plan = $this->buildPlan($this->option('email'));

        if (empty($plan)) {
            $this->info('✅ Tidak ditemukan akun yang dipakai bersama. Data sudah bersih.');
            return self::SUCCESS;
        }

        $studentRoleIds = DB::table('roles')->whereIn('name', self::STUDENT_ROLES)->pluck('id', 'name');
        $studentRoleId = $studentRoleIds['siswa'] ?? $studentRoleIds['student'] ?? null;

        $reservedEmails = [];
        $rows = [];

        foreach ($plan as $item) {
            /** @var User $owner */
            $owner = $item['user'];
            $this->line(str_repeat('─', 90));
            $this->line(sprintf(
                '👤 Akun #%d  %s  <%s>  — pemilik: <fg=green>%s</>',
                $owner->id, $owner->name, $owner->email, $item['owner_label']
            ));

            foreach ($item['split'] as $student) {
                $newEmail = $this->generateUniqueEmail($student->full_name, $domain, $reservedEmails);
                $reservedEmails[] = $newEmail;
                $password = $this->defaultStudentPassword($student);

                $this->line(sprintf(
                    '   ↳ pindahkan siswa #%d %s (%s, NIS %s) → akun baru <fg=yellow>%s</> / password: %s',
                    $student->id, $student->full_name, $student->unit_name ?? '-',
                    $student->nis ?: '-', $newEmail, $password
                ));

                $rows[] = [
                    'akun_lama_id' => $owner->id,
                    'akun_lama_email' => $owner->email,
                    'akun_lama_nama' => $owner->name,
                    'student_id' => $student->id,
                    'nama_siswa' => $student->full_name,
                    'unit' => $student->unit_name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'akun_baru_id' => null,
                    'email_baru' => $newEmail,
                    'password' => $password,
                    '_student' => $student,
                ];
            }
        }

        $this->line(str_repeat('─', 90));
        $this->info(sprintf('Total: %d akun bermasalah, %d record siswa akan dipisahkan.', count($plan), count($rows)));

        if (!$execute) {
            $this->line('');
            $this->comment('Dry-run selesai. Jalankan ulang dengan --execute untuk menerapkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use (&$rows, $plan, $studentRoleId) {
            foreach ($rows as &$row) {
                $student = $row['_student'];

                $newUser = User::create([
                    'name' => $student->full_name,
                    'email' => $row['email_baru'],
                    'password' => Hash::make($row['password']),
                ]);
                // email_verified_at tidak ada di $fillable — set langsung.
                $newUser->forceFill(['email_verified_at' => now()])->save();

                DB::table('students')->where('id', $student->id)->update([
                    'user_id' => $newUser->id,
                    'updated_at' => now(),
                ]);

                if ($studentRoleId) {
                    DB::table('model_has_roles')->updateOrInsert([
                        'role_id' => $studentRoleId,
                        'model_type' => User::class,
                        'model_id' => $newUser->id,
                        'team_id' => $student->unit_id,
                    ]);
                }

                $row['akun_baru_id'] = $newUser->id;
            }
            unset($row);

            // Bersihkan role siswa di akun lama untuk unit yang sudah tidak punya record siswa.
            foreach ($plan as $item) {
                $ownerId = $item['user']->id;
                $remainingStudentUnits = DB::table('students')->where('user_id', $ownerId)->pluck('unit_id')->unique()->all();

                DB::table('model_has_roles')
                    ->where('model_id', $ownerId)
                    ->where('model_type', User::class)
                    ->whereIn('role_id', DB::table('roles')->whereIn('name', self::STUDENT_ROLES)->pluck('id'))
                    ->where(function ($q) use ($remainingStudentUnits) {
                        $q->whereNull('team_id');
                        if (!empty($remainingStudentUnits)) {
                            $q->orWhereNotIn('team_id', $remainingStudentUnits);
                        } else {
                            $q->orWhereNotNull('team_id');
                        }
                    })
                    ->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $path = $this->writeReport($rows);

        $this->line('');
        $this->info('✅ Selesai! Akun berhasil dipisahkan.');
        $this->info("📄 Laporan akun baru (berisi password, JANGAN dibagikan publik): {$path}");

        return self::SUCCESS;
    }

    /**
     * Susun daftar akun yang dipakai bersama beserta record siswa yang harus dipindah.
     */
    private function buildPlan(?string $email): array
    {
        $studentCounts = DB::table('students')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        if ($studentCounts->isEmpty()) {
            return [];
        }

        $employeeUserIds = DB::table('teachers')->whereNotNull('user_id')->pluck('user_id')
            ->merge(DB::table('staff')->whereNotNull('user_id')->pluck('user_id'))
            ->unique()
            ->flip();

        $candidateIds = $studentCounts
            ->filter(fn ($total, $userId) => $total > 1 || $employeeUserIds->has($userId))
            ->keys();

        $users = User::whereIn('id', $candidateIds)
            ->when($email, fn ($q) => $q->where('email', trim($email)))
            ->orderBy('id')
            ->get();

        $plan = [];

        foreach ($users as $user) {
            $students = DB::table('students')
                ->leftJoin('units', 'units.id', '=', 'students.unit_id')
                ->where('students.user_id', $user->id)
                ->orderBy('students.id')
                ->get(['students.*', 'units.name as unit_name']);

            if ($employeeUserIds->has($user->id)) {
                $teacher = DB::table('teachers')->leftJoin('units', 'units.id', '=', 'teachers.unit_id')
                    ->where('teachers.user_id', $user->id)->first(['units.name as unit_name']);
                $staff = DB::table('staff')->leftJoin('units', 'units.id', '=', 'staff.unit_id')
                    ->where('staff.user_id', $user->id)->first(['units.name as unit_name']);
                $unitName = $teacher->unit_name ?? $staff->unit_name ?? '-';

                $plan[] = [
                    'user' => $user,
                    'owner_label' => ($teacher ? 'Guru' : 'Staff') . " @ {$unitName}",
                    'split' => $students->all(),
                ];
                continue;
            }

            // Hanya siswa: pertahankan siswa yang namanya cocok dengan nama akun.
            $normalizedUserName = $this->normalizeName($user->name);
            $keeper = $students->first(fn ($s) => $this->normalizeName($s->full_name) === $normalizedUserName)
                ?? $students->first();

            $plan[] = [
                'user' => $user,
                'owner_label' => "Siswa {$keeper->full_name} @ " . ($keeper->unit_name ?? '-'),
                'split' => $students->reject(fn ($s) => $s->id === $keeper->id)->values()->all(),
            ];
        }

        return array_values(array_filter($plan, fn ($p) => !empty($p['split'])));
    }

    private function normalizeName(?string $name): string
    {
        return Str::of((string) $name)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();
    }

    /**
     * Email baru: dua kata pertama nama siswa, mis. "nabila.ainurrohmah@namira.school".
     * Tambah angka bila sudah dipakai.
     */
    private function generateUniqueEmail(string $fullName, string $domain, array $reserved): string
    {
        $words = array_values(array_filter(explode(' ', $this->normalizeName($fullName))));
        $base = implode('.', array_slice($words, 0, 2)) ?: 'siswa';

        $candidate = "{$base}@{$domain}";
        $i = 2;
        while (in_array($candidate, $reserved, true) || User::where('email', $candidate)->exists()) {
            $candidate = "{$base}.{$i}@{$domain}";
            $i++;
        }

        return $candidate;
    }

    /** Sama dengan pola seeder: NIS → NISN → "siswa123". */
    private function defaultStudentPassword(object $student): string
    {
        $nis = trim((string) ($student->nis ?? ''));
        $nisn = trim((string) ($student->nisn ?? ''));

        return $nis !== '' ? $nis : ($nisn !== '' ? $nisn : 'siswa123');
    }

    private function writeReport(array $rows): string
    {
        $dir = storage_path('app/reports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/split-shared-accounts-' . now()->format('Ymd-His') . '.csv';
        $fh = fopen($path, 'w');
        fputcsv($fh, ['akun_lama_id', 'akun_lama_email', 'akun_lama_nama', 'student_id', 'nama_siswa', 'unit', 'nis', 'nisn', 'akun_baru_id', 'email_baru', 'password']);

        foreach ($rows as $row) {
            unset($row['_student']);
            fputcsv($fh, array_values($row));
        }

        fclose($fh);

        return $path;
    }
}

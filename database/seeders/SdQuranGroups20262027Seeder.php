<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Quran\Models\QuranGroup;
use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranSession;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Seeder;

class SdQuranGroups20262027Seeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'SD')->first()
            ?? Unit::where('name', 'SD Namira')->first();

        if (!$unit) {
            $this->command?->error('Unit SD Namira tidak ditemukan.');
            return;
        }

        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('id')->first();

        if (!$academicYear) {
            $this->command?->error('Tahun Ajaran aktif tidak ditemukan.');
            return;
        }

        // Resolve sessions by sort_order (1=Sesi 1, 2=Sesi 2, 3=Sesi 3)
        $sessions = QuranSession::where('unit_id', $unit->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('sort_order');

        foreach ([1, 2, 3] as $sOrder) {
            if (!$sessions->has($sOrder)) {
                $this->command?->error("QuranSession sort_order={$sOrder} tidak ditemukan untuk {$unit->name}.");
                return;
            }
        }

        // Resolve 11 levels by code
        $levels = QuranLevel::where('unit_id', $unit->id)
            ->get()
            ->keyBy('code');

        // Confirmed mapping: nickname => [email, displayLabel]
        $teacherMap = [
            'Muthma' => ['email' => 'hj@namira.school',                'label' => 'Ustzh. Muthma'],
            'Icha'   => ['email' => 'nurainifriscaananda@gmail.com',   'label' => 'Ustzh. Icha'],
            'Mei'    => ['email' => 'meirindazm@gmail.com',            'label' => 'Ustzh. Mei'],
            'Novi'   => ['email' => 'novitaagustinningsih16@gmail.com','label' => 'Ustzh. Novi'],
            'Sarif'  => ['email' => 'syarifsya726@gmail.com',          'label' => 'Ust. Sarif'],
            'Iva'    => ['email' => 'ivamutmainah.1507@gmail.com',     'label' => 'Ustzh. Iva'],
            'Aam'    => ['email' => 'syarifahaminatul@gmail.com',      'label' => 'Ustzh. Aam'],
            'Helmi'  => ['email' => 'helmimufida45@gmail.com',         'label' => 'Ustzh. Helmi'],
            'Putri'  => ['email' => 'putriagustini1303@gmail.com',     'label' => 'Ustzh. Putri'],
            'Rina'   => ['email' => 'jannahrehanatil@gmail.com',       'label' => 'Ustzh. Rina'],
        ];

        $teacherUsers = [];
        foreach ($teacherMap as $nick => $meta) {
            $user = User::where('email', $meta['email'])->first();
            if (!$user) {
                $this->command?->error("Akun guru '{$nick}' ({$meta['email']}) tidak ditemukan.");
                return;
            }
            $teacherUsers[$nick] = [
                'id' => $user->id,
                'label' => $meta['label'],
            ];
        }

        // 36 confirmed groups across Sesi 1 (13), Sesi 2 (13), Sesi 3 (10)
        // Slots without students (Sesi 1 Tajwid & Tahfidz) intentionally omitted.
        $plan = [
            // === SESI 1 (13 kelompok) ===
            [1, 'J1', ['Muthma']],
            [1, 'J2', ['Icha', 'Mei', 'Novi']],
            [1, 'J3', ['Sarif', 'Iva']],
            [1, 'J4', ['Aam', 'Helmi']],
            [1, 'J5', ['Putri']],
            [1, 'J6', ['Putri']],
            [1, 'AQ', ['Muthma']],
            [1, 'GH', ['Rina']],
            [1, 'MQ', ['Rina']],

            // === SESI 2 (13 kelompok) ===
            [2, 'J2', ['Icha']],
            [2, 'J3', ['Icha', 'Sarif']],
            [2, 'J4', ['Aam', 'Helmi']],
            [2, 'J5', ['Mei']],
            [2, 'J6', ['Helmi']],
            [2, 'AQ', ['Novi', 'Putri']],
            [2, 'TJ', ['Rina']],
            [2, 'GH', ['Rina']],
            [2, 'MQ', ['Rina']],
            [2, 'TF', ['Muthma']],

            // === SESI 3 (10 kelompok) ===
            [3, 'J3', ['Icha']],
            [3, 'J4', ['Icha']],
            [3, 'J5', ['Mei']],
            [3, 'J6', ['Sarif']],
            [3, 'AQ', ['Putri', 'Helmi']],
            [3, 'GH', ['Aam']],
            [3, 'MQ', ['Rina']],
            [3, 'TF', ['Novi', 'Muthma']],
        ];

        $count = 0;
        foreach ($plan as [$sessionOrder, $levelCode, $nicks]) {
            $session = $sessions->get($sessionOrder);
            $level = $levels->get($levelCode);

            if (!$level) {
                $this->command?->error("QuranLevel code='{$levelCode}' tidak ditemukan untuk {$unit->name}.");
                return;
            }

            foreach ($nicks as $nick) {
                $t = $teacherUsers[$nick];
                $groupName = "S{$sessionOrder} · {$level->name} - {$t['label']}";

                QuranGroup::updateOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'academic_year_id' => $academicYear->id,
                        'session_id' => $session->id,
                        'level_id' => $level->id,
                        'teacher_id' => $t['id'],
                    ],
                    [
                        'name' => $groupName,
                        'max_quota' => 15,
                        'is_active' => true,
                    ]
                );
                $count++;
            }
        }

        $this->command?->info("Berhasil menyinkronkan {$count} kelompok Ummi {$unit->name} (TA {$academicYear->name}).");
    }
}

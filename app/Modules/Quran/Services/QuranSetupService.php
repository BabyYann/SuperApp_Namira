<?php

namespace App\Modules\Quran\Services;

use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Yayasan\Models\Unit;

class QuranSetupService
{
    /**
     * Complete default Ummi levels & rubrik items seeded from live ummisd.namiraschool.com.
     */
    public function getDefaultCurriculum(): array
    {
        return [
            [
                'name' => 'Jilid 1',
                'code' => 'J1',
                'sort_order' => 1,
                'materi' => [
                    'Huruf tunggal Hijaiyah',
                    'Membaca 2-3 huruf berharokat Fathah',
                    'Huruf tunggal berharokat fathah',
                ],
                'surah_pendek' => [
                    'An - Naas',
                    'Al - Falaq',
                    'Al - Ikhlas',
                    'Al - Lahab',
                ],
                'doa_harian' => [
                    'Akan belajar',
                    'Sesudah belajar',
                    'Akan tidur',
                    'Bangun tidur',
                ],
                'hadist' => [
                    'Kewajiban menuntut ilmu',
                    'Keutamaan belajar Al-Qur\'an',
                ],
            ],
            [
                'name' => 'Jilid 2',
                'code' => 'J2',
                'sort_order' => 2,
                'materi' => [
                    'Harokat kasroh, dlommah, fathatain, kasrotain, dlommatain',
                    'Huruf sambung alif sampai ya\'',
                    'Angka Arab 1-99',
                ],
                'surah_pendek' => [
                    'An - Nashr',
                    'Al - Kafirun',
                    'Al - Kautsar',
                    'Al - Ma\'un',
                ],
                'doa_harian' => [
                    'Masuk kamar mandi',
                    'Keluar kamar mandi',
                ],
                'hadist' => [
                    'Perintah sholat',
                    'Hadist niat',
                ],
            ],
            [
                'name' => 'Jilid 3',
                'code' => 'J3',
                'sort_order' => 3,
                'materi' => [
                    'Tanda baca panjang (mad thobi\'i)',
                    'Tanda baca panjang (mad wajib muttashil dan mad jaiz munfashil)',
                    'Angka arab 100-500',
                ],
                'surah_pendek' => [
                    'Qoraisy',
                    'Al - Fill',
                    'Al - Humazah',
                    'Al - Ashr',
                    'At - Takatsur',
                ],
                'doa_harian' => [
                    'Masuk kamar kecil/wc',
                    'Keluar kamar kecil/wc',
                    'Sebelum makan',
                    'Sesudah makan',
                ],
                'hadist' => [
                    'Sebaik-baik manusia',
                    'Orang islam bersaudara',
                ],
            ],
            [
                'name' => 'Jilid 4',
                'code' => 'J4',
                'sort_order' => 4,
                'materi' => [
                    'Huruf yang di sukun ditekan membacanya',
                    'Tanda tasydid/syiddah ditekan membacanya',
                    'Membedakan cara membaca huruf-huruf',
                ],
                'surah_pendek' => [
                    'Al - Qori\'ah',
                    'Al - A\'diyat',
                ],
                'doa_harian' => [
                    'Keluar rumah',
                    'Tiba dirumah',
                    'Masuk rumah',
                ],
                'hadist' => [
                    'Allah itu indah',
                    'Membaguskan bacaan al-qur\'an',
                ],
            ],
            [
                'name' => 'Jilid 5',
                'code' => 'J5',
                'sort_order' => 5,
                'materi' => [
                    'Cara membaca Waqof/mewaqofkan',
                    'Bacaan ghunnah/dengung',
                    'Bacaan ikhfa\'/samar',
                    'Bacaan idhom bighunnah',
                    'Bacaan iqlab',
                    'Membaca Lafadz Allah (tafkhim/tarqiq)',
                ],
                'surah_pendek' => [
                    'Al - Zalzalah',
                    'Al - Bayyinah',
                ],
                'doa_harian' => [
                    'Masuk masjid',
                    'Keluar masjid',
                    'Mohon ampun untuk kedua orang tua',
                    'Naik kendaraan darat',
                    'Naik kendaraan laut',
                ],
                'hadist' => [
                    'Taqwa kepada Allah',
                    'Memberi lebih baik daripada meminta',
                ],
            ],
            [
                'name' => 'Jilid 6',
                'code' => 'J6',
                'sort_order' => 6,
                'materi' => [
                    'Bacaan qolqolah (mantul)',
                    'Bacaan idghom bilaghunnah',
                    'Bacaan idzhar/jelas',
                    'Macam-macam tanda waqof/washol',
                    'Membaca nun iwadl di awal ayat dan di tengah ayat',
                    'Membaca ana, na nya di baca pendek',
                ],
                'surah_pendek' => [
                    'Al - Qodar',
                    'Al - A\'laq',
                ],
                'doa_harian' => [
                    'Sesudah adzan',
                    'Sebelum wudhu',
                    'Sesudah wudhu',
                ],
                'hadist' => [
                    'Hadist akhlak',
                    'Sholat seperti rosulullah',
                ],
            ],
            [
                'name' => 'Al-Qur\'an',
                'code' => 'AQ',
                'sort_order' => 7,
                'materi' => [
                    'Fashohah',
                    'Tartil',
                    'Penerapan Tajwid',
                    'Pemahaman Ghorib',
                ],
                'surah_pendek' => [
                    'At - Tin',
                    'Al - Insyirah',
                    'Ad - Dhuha',
                ],
                'doa_harian' => [
                    'Memulai pekerjaan yang baik',
                    'Mengakhiri pekerjaan yang baik',
                    'Memakai pakaian',
                ],
                'hadist' => [
                    'Dunia bagi orang mukmin',
                    'Pahala menunjukkan kebaikan',
                ],
            ],
            [
                'name' => 'Ghorib',
                'code' => 'GH',
                'sort_order' => 8,
                'materi' => [
                    'Fashohah',
                    'Tartil',
                    'Penerapan Tajwid',
                    'Pemahaman Ghorib',
                ],
                'surah_pendek' => [
                    'Al - Lail',
                    'As - Syams',
                ],
                'doa_harian' => [
                    'Melepas pakaian',
                    'Bercermin',
                    'Kebaikan dunia akhirat',
                    'Penyerahan diri kepada Allah',
                ],
                'hadist' => [
                    'Manusia yang paling mulia',
                    'Malu sebagian dari iman',
                ],
            ],
            [
                'name' => 'Tajwid',
                'code' => 'TJ',
                'sort_order' => 9,
                'materi' => [
                    'Kelancaran',
                    'Makhorijul Huruf',
                    'Hukum Nun Sukun dan Tanwin',
                    'Ghunnah (Nun dan Mim yang bertasdid)',
                    'Hukum Mim Syukun',
                    'Macam - Macam Idghom',
                    'Hukum Lafad Allah',
                    'Qolqolah',
                    'Idhar Wajib',
                    'Hukum Ro\'',
                    'Hukum Lam Ta\'Rif (Al)',
                    'Macam - Macam Mad (Mad Thobi\'i dan Mad Far\'i)',
                ],
                'surah_pendek' => [
                    'Al - Balad',
                    'Al - Fajr',
                ],
                'doa_harian' => [
                    'Bersikukuh dalam agama islam',
                    'Memohon kecerdasan',
                ],
                'hadist' => [
                    'Hadist agama',
                    'Memelihara anak yatim',
                ],
            ],
            [
                'name' => 'Tahfidz',
                'code' => 'TF',
                'sort_order' => 10,
                'materi' => [
                    'Fashohah',
                    'Tartil',
                    'Tajwid',
                    'Ghorib',
                ],
                'surah_pendek' => [
                    'An Naba - Al Infitor',
                    'Al Mutoffifin - Al Buruj',
                    'At Thoriq - Al Qoriah',
                    'At Takatsur - An Naas',
                    'Al - Baqarah Ayat 1 - 50',
                    'Al - Baqarah Ayat 50 - 100',
                    'Al - Baqarah Ayat 100 - 150',
                    'Al - Baqarah Ayat 150 - 200',
                    'Juz 30',
                    'Juz 1',
                    'Juz 2',
                    'Juz 3',
                    'Juz 4 Ayat 135',
                ],
                'doa_harian' => [
                    'Bersyukur',
                    'Lapang dada',
                    'Hauqolah',
                    'Hasbalah',
                ],
                'hadist' => [
                    'Bersyukur',
                    'Mengadu domba',
                    'Berbuat kebaikan',
                ],
            ],
        ];
    }

    /**
     * Ensure default Ummi levels, rubrics, and settings exist for a formal unit.
     */
    public function ensureUnitDefaults(Unit $unit): void
    {
        if ($unit->isDaycare()) {
            return;
        }

        $hasLevels = QuranLevel::where('unit_id', $unit->id)->exists();

        if (!$hasLevels) {
            foreach ($this->getDefaultCurriculum() as $levelDef) {
                $level = QuranLevel::create([
                    'unit_id' => $unit->id,
                    'name' => $levelDef['name'],
                    'code' => $levelDef['code'],
                    'sort_order' => $levelDef['sort_order'],
                    'is_active' => true,
                ]);

                $categories = [
                    QuranRubricItem::CATEGORY_MATERI => $levelDef['materi'],
                    QuranRubricItem::CATEGORY_SURAH => $levelDef['surah_pendek'],
                    QuranRubricItem::CATEGORY_DOA => $levelDef['doa_harian'],
                    QuranRubricItem::CATEGORY_HADIST => $levelDef['hadist'],
                ];

                foreach ($categories as $category => $items) {
                    foreach ($items as $idx => $title) {
                        QuranRubricItem::create([
                            'unit_id' => $unit->id,
                            'level_id' => $level->id,
                            'category' => $category,
                            'title' => trim($title),
                            'sort_order' => $idx + 1,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }

        QuranSetting::firstOrCreate(
            ['unit_id' => $unit->id],
            [
                'school_name' => $unit->name,
                'school_address' => $unit->address ?: 'Jl. Panglima Sudirman No. 170, Kraksaan',
                'city_name' => str_contains(strtolower($unit->name ?? ''), 'kota') ? 'Probolinggo' : 'Kraksaan',
                'kepala_sekolah' => $unit->principal?->name ?: (strtoupper((string) $unit->code) === 'SD' ? 'Astutik, S.Pd.I' : ''),
                'kepala_tpq' => strtoupper((string) $unit->code) === 'SD' ? 'Nur Halimah' : '',
                'kepala_tpq_title' => 'Kepala TPQ Ummi ' . $unit->name,
                'ttd_config' => [
                    'tpq_width' => 100,
                    'tpq_offset_x' => 0,
                    'tpq_offset_y' => 0,
                    'kepsek_width' => 100,
                    'kepsek_offset_x' => 0,
                    'kepsek_offset_y' => 0,
                ],
                'lock_input_nilai' => false,
                'lock_cetak_rapor' => false,
                'allow_teacher_promote' => true,
            ]
        );
    }

    /**
     * Generate Indonesian descriptive narrative for a grade on the Ummi report card.
     */
    public function buildNarrative(?string $grade, string $itemTitle, string $category): string
    {
        if (!$grade || $grade === '-') {
            return '-';
        }

        $grade = strtoupper(trim($grade));

        if ($category === QuranRubricItem::CATEGORY_SURAH) {
            return match ($grade) {
                'A' => "Ananda mampu melafalkan Surah {$itemTitle} dengan sangat lancar, tartil, dan sesuai kaidah tajwid dengan baik.",
                'B' => "Ananda telah mampu melafalkan Surah {$itemTitle} dengan lancar. Perlu pengulangan dan muroja'ah secara rutin agar hafalan semakin mutqin dan konsisten.",
                'C' => "Ananda cukup mampu melafalkan Surah {$itemTitle} dengan baik, namun masih memerlukan peningkatan dalam kelancaran dan ketepatan bacaan melalui muroja'ah yang lebih intensif.",
                'D' => "Ananda masih dalam tahap pembelajaran dalam menghafalkan Surah {$itemTitle}. Diperlukan pendampingan, latihan, dan pengulangan yang lebih rutin baik di sekolah maupun di rumah.",
                default => '-',
            };
        }

        if ($category === QuranRubricItem::CATEGORY_DOA) {
            return match ($grade) {
                'A' => "Ananda mampu menghafal dan melafalkan Doa {$itemTitle} dengan sangat fasih dan lancar.",
                'B' => "Ananda telah menghafal Doa {$itemTitle} dengan baik. Perlu pembiasaan dan pengulangan secara rutin agar hafalan semakin kuat.",
                'C' => "Ananda cukup mampu menghafal Doa {$itemTitle}, namun masih memerlukan pembiasaan dalam pelafalan dan penerapan dalam kegiatan sehari-hari.",
                'D' => "Ananda masih memerlukan bimbingan dan latihan lebih lanjut dalam menghafal serta melafalkan Doa {$itemTitle} dengan baik dan benar.",
                default => '-',
            };
        }

        if ($category === QuranRubricItem::CATEGORY_HADIST) {
            return match ($grade) {
                'A' => "Ananda mampu menghafal dan melafalkan Hadits {$itemTitle} dengan sangat baik, fasih, dan lancar.",
                'B' => "Ananda telah menghafal Hadits {$itemTitle} dengan baik. Perlu pengulangan secara rutin agar hafalan semakin mutqin.",
                'C' => "Ananda cukup mampu menghafal Hadits {$itemTitle}, namun masih perlu meningkatkan kelancaran melalui pengulangan yang lebih konsisten.",
                'D' => "Ananda masih memerlukan bimbingan dan latihan secara bertahap dalam menghafal serta melafalkan Hadits {$itemTitle}.",
                default => '-',
            };
        }

        return match ($grade) {
            'A' => "Ananda mampu menguasai materi {$itemTitle} dengan sangat baik sesuai standar pembelajaran Ummi.",
            'B' => "Ananda telah menguasai materi {$itemTitle} dengan baik. Perlu peningkatan ketelitian dan konsistensi dalam membaca.",
            'C' => "Ananda cukup mampu memahami dan menguasai materi {$itemTitle}, namun masih perlu meningkatkan kemampuan melalui latihan membaca yang lebih rutin.",
            'D' => "Ananda masih memerlukan pendampingan dan latihan secara intensif dalam memahami serta menguasai materi {$itemTitle} sesuai target pembelajaran.",
            default => '-',
        };
    }

    /**
     * Resolve best unit logo URL for report printing.
     */
    public function resolveUnitLogoUrl(?Unit $unit): string
    {
        if ($unit?->logo_url) {
            return $unit->logo_url;
        }

        $code = strtoupper((string) ($unit?->code ?? ''));
        $name = strtolower((string) ($unit?->name ?? ''));

        $candidate = null;
        if (str_starts_with($code, 'SD') || str_contains($name, 'sd')) {
            $candidate = 'images/landing/logo-sd.png';
        } elseif (str_starts_with($code, 'SMP') || str_contains($name, 'smp')) {
            $candidate = 'images/landing/logo-smp.png';
        } elseif (str_starts_with($code, 'TK') || str_contains($name, 'tk')) {
            $candidate = 'images/landing/logo-tk.png';
        } elseif (str_starts_with($code, 'KB') || str_contains($name, 'kb')) {
            $candidate = 'images/landing/logo-kb.png';
        }

        if ($candidate && file_exists(public_path($candidate))) {
            return asset($candidate);
        }

        return asset('images/logo.png');
    }
}

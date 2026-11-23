<?php

namespace App\Modules\Quran\Services;

use App\Modules\Quran\Models\QuranLevel;
use App\Modules\Quran\Models\QuranRubricItem;
use App\Modules\Quran\Models\QuranSetting;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Support\Facades\Cache;

class QuranSetupService
{
    /**
     * 11 ordered Ummi levels (Tajwid -> Ghorib -> Munaqosyah -> Tahfidz).
     * Materi Pokok & Surah Pendek stay per Jilid.
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
                'name' => 'Tajwid',
                'code' => 'TJ',
                'sort_order' => 8,
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
                'name' => 'Ghorib',
                'code' => 'GH',
                'sort_order' => 9,
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
                'name' => 'Munaqosyah',
                'code' => 'MQ',
                'sort_order' => 10,
                'materi' => [
                    'Fashohah',
                    'Tartil',
                    'Tajwid',
                    'Ghorib',
                ],
                'surah_pendek' => [
                    'Juz 30',
                ],
                'doa_harian' => [],
                'hadist' => [],
            ],
            [
                'name' => 'Tahfidz',
                'code' => 'TF',
                'sort_order' => 11,
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
     * Grade-scoped curriculum for SD (Kelas 1 - 6): Hadist, Doa Harian, Niat Sholat & Bersuci, Bacaan Sholat.
     */
    public function getDefaultSdGradeCurriculum(): array
    {
        return [
            '1' => [
                QuranRubricItem::CATEGORY_HADIST => [
                    'Hadist Larangan Makan dan Minum Sambil Berdiri',
                    'Hadist Larangan Marah',
                    'Hadist Kebersihan',
                    'Hadist Ucap Salam',
                    'Hadist Berdo\'a',
                    'Hadist Menutup Aurat',
                ],
                QuranRubricItem::CATEGORY_DOA => [
                    'Doa Sebelum Tidur',
                    'Doa Bangun Tidur',
                    'Doa Masuk Kamar Mandi',
                    'Doa Keluar Kamar Mandi',
                    'Doa Mau Makan',
                    'Doa Sesudah Makan',
                    'Doa Untuk Kedua Orang Tua',
                    'Doa Kebaikan Dunia Akhirat',
                ],
            ],
            '2' => [
                QuranRubricItem::CATEGORY_HADIST => [
                    'Hadist Tersenyum',
                    'Hadist Kesehatan',
                    'Hadist Beramal Baik',
                    'Hadist Muslim Bersaudara',
                    'Hadist Memberi dan Meminta',
                    'Hadist Kasih Sayang',
                ],
                QuranRubricItem::CATEGORY_DOA => [
                    'Doa Masuk Masjid',
                    'Doa Keluar Masjid',
                    'Doa Masuk Kelas',
                    'Doa Keluar Kelas',
                    'Doa Turun Hujan',
                    'Doa Bercermin',
                    'Doa Memakai Pakaian',
                    'Doa Melepas Pakaian',
                ],
            ],
            '3' => [
                QuranRubricItem::CATEGORY_HADIST => [
                    'Hadist Berkata yang Benar',
                    'Hadist Bertaqwa',
                    'Hadist Menuntut Ilmu',
                    'Hadist Tolong Menolong',
                    'Hadist Menjaga Lisan',
                    'Hadist Hidup Sederhana',
                ],
                QuranRubricItem::CATEGORY_DOA => [
                    'Doa Naik Kendaraan',
                    'Doa Sebelum Belajar',
                    'Doa Sesudah Belajar',
                    'Doa Memohon Kesehatan',
                    'Doa Memohon Kecerdasan',
                    'Doa Penutup Majlis',
                    'Doa Menghilangkan Kesedihan',
                ],
            ],
            '4' => [
                QuranRubricItem::CATEGORY_HADIST => [
                    'Hadist Belajar Al-Qur\'an',
                    'Hadist Perintah Sholat',
                    'Hadist Sebaik-baiknya Manusia',
                    'Hadist Allah Itu Indah',
                ],
                QuranRubricItem::CATEGORY_NIAT_SHOLAT => [
                    'Niat Sholat Dhuha',
                    'Niat Sholat Dzuhur',
                    'Niat Sholat Ashar',
                    'Niat Sholat Maghrib',
                    'Niat Sholat Isya',
                    'Niat Sholat Tahajjud',
                    'Niat Wudhu',
                    'Do\'a Sesudah Wudhu',
                    'Niat Tayammum',
                ],
            ],
            '5' => [
                QuranRubricItem::CATEGORY_HADIST => [
                    'Hadist Akhlaq',
                    'Hadist Sholat Seperti Rosulullah',
                    'Hadist Dunia Bagi Orang Mukmin',
                    'Hadist Kebersihan Sebagian Dari Iman',
                ],
                QuranRubricItem::CATEGORY_NIAT_SHOLAT => [
                    'Niat Sholat Jama\' Dzuhur Dengan Ashar',
                    'Niat Sholat Dzuhur Qashar',
                    'Niat Sholat Idul Fitri',
                    'Niat Sholat Idul Adha',
                    'Niat Bersuci Dari Haid',
                    'Niat Bersuci Dari Hadats Besar',
                ],
            ],
            '6' => [
                QuranRubricItem::CATEGORY_BACAAN_SHOLAT => [
                    'Do\'a Iftitah',
                    'Do\'a Rukuk',
                    'Do\'a I\'tidal',
                    'Do\'a Sujud',
                    'Do\'a Duduk Diantara Dua Sujud',
                    'Do\'a Tasyahud Awal',
                    'Do\'a Tasyahud Akhir',
                    'Salam',
                    'Do\'a Qunut',
                ],
            ],
        ];
    }

    public function isSdUnit(Unit $unit): bool
    {
        $code = strtoupper((string) $unit->code);
        $cat = strtoupper((string) $unit->category);

        return $cat === 'SD' || str_starts_with($code, 'SD');
    }

    /**
     * Extract numeric grade level ('1'..'6') from classroom level or classroom name (e.g. '4A' -> '4').
     */
    public function extractGradeLevel(?string $classroomLevel, ?string $classroomName = null): ?string
    {
        foreach ([$classroomLevel, $classroomName] as $candidate) {
            if ($candidate === null || trim($candidate) === '') {
                continue;
            }
            if (preg_match('/(\d+)/', (string) $candidate, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Seed or update grade-scoped SD curriculum cleanly.
     */
    public function seedSdGradeRubrics(Unit $unit): void
    {
        if (!$this->isSdUnit($unit)) {
            return;
        }

        $hasGradeRubrics = QuranRubricItem::where('unit_id', $unit->id)
            ->where('scope', QuranRubricItem::SCOPE_GRADE)
            ->exists();

        if ($hasGradeRubrics) {
            return;
        }

        foreach ($this->getDefaultSdGradeCurriculum() as $grade => $categories) {
            foreach ($categories as $category => $items) {
                foreach ($items as $idx => $title) {
                    QuranRubricItem::create([
                        'unit_id' => $unit->id,
                        'scope' => QuranRubricItem::SCOPE_GRADE,
                        'level_id' => null,
                        'grade_level' => (string) $grade,
                        'category' => $category,
                        'title' => trim($title),
                        'sort_order' => $idx + 1,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Ensure default Ummi levels, rubrics, and settings exist for a formal unit.
     */
    public function ensureUnitDefaults(Unit $unit): void
    {
        if ($unit->isDaycare()) {
            return;
        }

        $cacheKey = 'quran_unit_defaults_ready_v1_' . $unit->id;
        if (Cache::has($cacheKey)) {
            return;
        }

        $isSd = $this->isSdUnit($unit);
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

                // SD uses grade-scoped Hadist/Doa/Niat/Bacaan Sholat; non-SD keeps level-scoped defaults
                $categories = [
                    QuranRubricItem::CATEGORY_MATERI => $levelDef['materi'],
                    QuranRubricItem::CATEGORY_SURAH => $levelDef['surah_pendek'],
                ];
                if (!$isSd) {
                    $categories[QuranRubricItem::CATEGORY_DOA] = $levelDef['doa_harian'];
                    $categories[QuranRubricItem::CATEGORY_HADIST] = $levelDef['hadist'];
                }

                foreach ($categories as $category => $items) {
                    foreach ($items as $idx => $title) {
                        QuranRubricItem::create([
                            'unit_id' => $unit->id,
                            'scope' => QuranRubricItem::SCOPE_LEVEL,
                            'level_id' => $level->id,
                            'grade_level' => null,
                            'category' => $category,
                            'title' => trim($title),
                            'sort_order' => $idx + 1,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }

        if ($isSd) {
            $this->seedSdGradeRubrics($unit);
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
                'allow_teacher_self_plotting' => true,
            ]
        );

        Cache::put($cacheKey, true, now()->addHours(12));
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
        // Avoid doubled prefix like "Doa Doa Masuk Masjid" or "Hadits Hadist Tersenyum"
        $cleanTitle = trim($itemTitle);

        if ($category === QuranRubricItem::CATEGORY_SURAH) {
            $surahName = preg_replace('/^surah\s+/i', '', $cleanTitle);

            return match ($grade) {
                'A' => "Ananda mampu melafalkan Surah {$surahName} dengan sangat lancar, tartil, dan sesuai kaidah tajwid dengan baik.",
                'B' => "Ananda telah mampu melafalkan Surah {$surahName} dengan lancar. Perlu pengulangan dan muroja'ah secara rutin agar hafalan semakin mutqin dan konsisten.",
                'C' => "Ananda cukup mampu melafalkan Surah {$surahName} dengan baik, namun masih memerlukan peningkatan dalam kelancaran dan ketepatan bacaan melalui muroja'ah yang lebih intensif.",
                'D' => "Ananda masih dalam tahap pembelajaran dalam menghafalkan Surah {$surahName}. Diperlukan pendampingan, latihan, dan pengulangan yang lebih rutin baik di sekolah maupun di rumah.",
                default => '-',
            };
        }

        if ($category === QuranRubricItem::CATEGORY_DOA) {
            $doaLabel = preg_match('/^do\'?a\b/i', $cleanTitle) ? $cleanTitle : "Doa {$cleanTitle}";

            return match ($grade) {
                'A' => "Ananda mampu menghafal dan melafalkan {$doaLabel} dengan sangat fasih dan lancar.",
                'B' => "Ananda telah menghafal {$doaLabel} dengan baik. Perlu pembiasaan dan pengulangan secara rutin agar hafalan semakin kuat.",
                'C' => "Ananda cukup mampu menghafal {$doaLabel}, namun masih memerlukan pembiasaan dalam pelafalan dan penerapan dalam kegiatan sehari-hari.",
                'D' => "Ananda masih memerlukan bimbingan dan latihan lebih lanjut dalam menghafal serta melafalkan {$doaLabel} dengan baik dan benar.",
                default => '-',
            };
        }

        if ($category === QuranRubricItem::CATEGORY_HADIST) {
            $hadistLabel = preg_match('/^hadis?ts?\b/i', $cleanTitle) ? $cleanTitle : "Hadits {$cleanTitle}";

            return match ($grade) {
                'A' => "Ananda mampu menghafal dan melafalkan {$hadistLabel} dengan sangat baik, fasih, dan lancar.",
                'B' => "Ananda telah menghafal {$hadistLabel} dengan baik. Perlu pengulangan secara rutin agar hafalan semakin mutqin.",
                'C' => "Ananda cukup mampu menghafal {$hadistLabel}, namun masih perlu meningkatkan kelancaran melalui pengulangan yang lebih konsisten.",
                'D' => "Ananda masih memerlukan bimbingan dan latihan secara bertahap dalam menghafal serta melafalkan {$hadistLabel}.",
                default => '-',
            };
        }

        if ($category === QuranRubricItem::CATEGORY_NIAT_SHOLAT) {
            return match ($grade) {
                'A' => "Ananda mampu menghafal dan melafalkan {$cleanTitle} dengan sangat fasih, lancar, dan benar.",
                'B' => "Ananda telah menghafal {$cleanTitle} dengan baik dan lancar. Perlu pembiasaan rutin agar hafalan semakin kuat.",
                'C' => "Ananda cukup mampu menghafal {$cleanTitle}, namun masih memerlukan pengulangan dan pembiasaan dalam pelafalannya.",
                'D' => "Ananda masih memerlukan bimbingan dan latihan lebih lanjut dalam menghafal serta melafalkan {$cleanTitle} dengan benar.",
                default => '-',
            };
        }

        if ($category === QuranRubricItem::CATEGORY_BACAAN_SHOLAT) {
            return match ($grade) {
                'A' => "Ananda mampu menghafal dan melafalkan bacaan {$cleanTitle} dengan sangat fasih, lancar, dan sesuai tuntunan.",
                'B' => "Ananda telah mampu melafalkan bacaan {$cleanTitle} dengan baik dan lancar. Perlu pengulangan secara rutin agar semakin mantap.",
                'C' => "Ananda cukup mampu melafalkan bacaan {$cleanTitle}, namun masih perlu meningkatkan kelancaran melalui pembiasaan ibadah sehari-hari.",
                'D' => "Ananda masih memerlukan bimbingan dan latihan lebih intensif dalam menghafal serta melafalkan bacaan {$cleanTitle}.",
                default => '-',
            };
        }

        return match ($grade) {
            'A' => "Ananda mampu menguasai materi {$cleanTitle} dengan sangat baik sesuai standar pembelajaran Ummi.",
            'B' => "Ananda telah menguasai materi {$cleanTitle} dengan baik. Perlu peningkatan ketelitian dan konsistensi dalam membaca.",
            'C' => "Ananda cukup mampu memahami dan menguasai materi {$cleanTitle}, namun masih perlu meningkatkan kemampuan melalui latihan membaca yang lebih rutin.",
            'D' => "Ananda masih memerlukan pendampingan dan latihan secara intensif dalam memahami serta menguasai materi {$cleanTitle} sesuai target pembelajaran.",
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

<?php

namespace Database\Seeders;

use App\Modules\Yayasan\Models\Holiday;
use App\Modules\Yayasan\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SdAcademicCalendar20262027Seeder extends Seeder
{
    public function run(): void
    {
        $sdUnit = Unit::where('code', 'SD')->first()
            ?? Unit::where('name', 'SD Namira')->first();

        if (!$sdUnit) {
            $this->command?->error('Unit SD Namira tidak ditemukan.');
            return;
        }

        // Color palette mapped to 6 SD Namira Academic Calendar legend categories
        $colors = [
            'libur'     => '#ef4444', // Libur / Cuti (Red)
            'keagamaan' => '#10b981', // Prog. Keagamaan (Emerald)
            'kurikulum' => '#0284c7', // Prog. Kurikulum (Sky Blue)
            'ujian'     => '#f59e0b', // Evaluasi / Ujian Kurikulum (Amber)
            'kesiswaan' => '#3b82f6', // Prog. Kesiswaan (Blue)
            'humas'     => '#8b5cf6', // Prog. Humas (Purple)
            'kelulusan' => '#0d9488', // Prog. Kelulusan (Namira Teal)
        ];

        // Entries: [startDate, endDate, description, event_type, colorKey, skipDates[]]
        $schedule = [
            // === JULI 2026 ===
            ['2026-07-13', '2026-07-17', 'MPLS (Masa Pengenalan Lingkungan Sekolah)', 'event', 'kesiswaan'],
            ['2026-07-18', '2026-07-18', 'Parenting Akbar', 'event', 'humas'],
            ['2026-07-20', '2026-07-20', 'SPMB', 'event', 'humas'],
            ['2026-07-25', '2026-07-25', 'Siber (Sinau Bersama Walimurid Kelas 1-5 dan Kelas 6)', 'event', 'humas'],

            // === AGUSTUS 2026 ===
            ['2026-08-11', '2026-08-14', 'Lomba PHBN HUT RI', 'event', 'kesiswaan'],
            ['2026-08-14', '2026-08-14', 'Upacara HUT Pramuka', 'event', 'kesiswaan'],
            ['2026-08-17', '2026-08-17', 'Upacara Bendera HUT RI', 'event', 'kesiswaan'],
            ['2026-08-18', '2026-08-18', 'Distribusi Brosur SPMB 2026/2027', 'event', 'humas'],
            ['2026-08-22', '2026-08-23', 'Pramuka PERSAMI', 'event', 'kesiswaan'],

            // === SEPTEMBER 2026 ===
            ['2026-09-03', '2026-09-03', 'Maulid Nabi Muhammad SAW', 'event', 'keagamaan'],
            ['2026-09-08', '2026-09-08', 'PHBN Hari Kepolisian Internasional (Kelas 4, 5, dan 6)', 'event', 'kesiswaan'],
            ['2026-09-10', '2026-09-10', 'Outing Class Fase C', 'event', 'kurikulum'],
            ['2026-09-15', '2026-09-15', 'HUT Namira ke-12', 'event', 'humas'],
            ['2026-09-30', '2026-09-30', 'PBN (Panggung Bakat Namira)', 'event', 'kesiswaan'],

            // === OKTOBER 2026 ===
            ['2026-10-02', '2026-10-02', 'PHBN Hari Batik (Memakai Kostum Batik)', 'event', 'kesiswaan'],
            ['2026-10-09', '2026-10-09', 'PHBN Hari Surat Menyurat', 'event', 'kesiswaan'],
            ['2026-10-17', '2026-10-17', 'SPMB Lomba Mewarnai Se-Kabupaten Probolinggo & Pembukaan SPMB 2026/2027', 'event', 'humas'],
            ['2026-10-22', '2026-10-23', 'PHBI Hari Santri (MINA 1)', 'event', 'keagamaan'],
            ['2026-10-24', '2026-10-24', 'PHBN Hari Dokter (Donor Darah PMI)', 'event', 'humas'],
            ['2026-10-28', '2026-10-28', 'PHBN Sumpah Pemuda (Upacara)', 'event', 'kesiswaan'],

            // === NOVEMBER 2026 ===
            ['2026-11-03', '2026-11-03', 'Pra Munaqosyah Ummi', 'ujian', 'keagamaan'],
            ['2026-11-14', '2026-11-14', 'PHBN Hari Pahlawan (Pawai Lomba Fashion Show) & Hari Ayah', 'event', 'kesiswaan'],
            ['2026-11-19', '2026-11-19', 'Outing Class Fase B', 'event', 'kurikulum'],
            ['2026-11-25', '2026-11-25', 'PHBN Hari Guru (Upacara)', 'event', 'kesiswaan'],
            ['2026-11-30', '2026-12-04', 'SAS Semester Ganjil', 'ujian', 'ujian'],

            // === DESEMBER 2026 ===
            ['2026-12-07', '2026-12-08', 'Remidial dan Pengayaan', 'ujian', 'ujian'],
            ['2026-12-07', '2026-12-11', 'Classmeeting & Perayaan Bulan Bahasa', 'event', 'kesiswaan'],
            ['2026-12-19', '2026-12-19', 'Psikotes Calon Murid Baru', 'event', 'humas'],
            ['2026-12-22', '2026-12-22', 'Penerimaan Rapor & PHBN Hari Ibu', 'event', 'kurikulum'],
            ['2026-12-24', '2026-12-25', 'Cuti Bersama Natal dan Tahun Baru', 'libur', 'libur'],
            ['2026-12-26', '2026-12-31', 'Libur Semester 1', 'libur', 'libur'],

            // === JANUARI 2027 ===
            ['2027-01-04', '2027-01-04', 'Goes to Perpusda', 'event', 'kurikulum'],
            ['2027-01-05', '2027-01-05', "Cuti Bersama Isra' Mi'raj", 'libur', 'libur'],
            ['2027-01-06', '2027-01-06', 'Goes to Perpusda', 'event', 'kurikulum'],
            ['2027-01-07', '2027-01-07', "PHBI Isra' Mi'raj", 'event', 'keagamaan'],
            ['2027-01-09', '2027-01-09', 'Observasi Dasar', 'event', 'humas'],
            ['2027-01-11', '2027-01-11', 'Foto Ijazah dan Foto Toga Bersama Kelas 6', 'event', 'kelulusan'],
            ['2027-01-20', '2027-01-20', 'Munaqosyah Ummi', 'ujian', 'keagamaan'],
            ['2027-01-25', '2027-01-26', 'PHBN (Hutaka dan Pantai Duta)', 'event', 'kesiswaan'],

            // === FEBRUARI 2027 ===
            ['2027-02-06', '2027-02-06', 'Cuti Bersama Imlek', 'libur', 'libur'],
            ['2027-02-08', '2027-02-10', 'Libur Awal Puasa', 'libur', 'libur'],
            ['2027-02-22', '2027-02-22', 'Safari Masjid', 'event', 'keagamaan'],
            ['2027-02-24', '2027-02-26', 'Pondok Ramadan 2027', 'event', 'keagamaan'],

            // === MARET 2027 ===
            ['2027-03-07', '2027-03-17', 'Cuti Bersama Libur Hari Raya 1448 H', 'libur', 'libur'],
            ['2027-03-25', '2027-03-25', 'Wisuda Tahfidz 2027', 'event', 'keagamaan'],
            ['2027-03-26', '2027-03-26', 'Cuti Bersama Wafat Isa Almasih', 'libur', 'libur'],
            ['2027-03-29', '2027-04-02', 'SAT Kelas 6', 'ujian', 'ujian'],

            // === APRIL 2027 ===
            ['2027-04-08', '2027-04-08', 'Outing Class Fase A', 'event', 'kurikulum'],
            ['2027-04-09', '2027-04-09', 'Manasik Haji Fase C', 'event', 'keagamaan'],
            ['2027-04-21', '2027-04-21', 'PHBN Hari Kartini (Lomba Mading 3D Per Fase)', 'event', 'kesiswaan'],
            ['2027-04-26', '2027-04-30', 'ASAJ Tulis 2027', 'ujian', 'kelulusan'],

            // === MEI 2027 ===
            ['2027-05-01', '2027-05-01', 'Cuti Bersama Hari Buruh Nasional', 'libur', 'libur'],
            // Skip 6 Mei (Cuti Bersama Kenaikan Isa Almasih) & 9 Mei (Minggu)
            ['2027-05-03', '2027-05-10', 'ASAJ Kelas 6 Praktik', 'ujian', 'kelulusan', ['2027-05-06', '2027-05-09']],
            ['2027-05-04', '2027-05-05', 'Lomba Hardiknas', 'event', 'kesiswaan'],
            ['2027-05-06', '2027-05-06', 'Cuti Bersama Kenaikan Isa Almasih', 'libur', 'libur'],
            ['2027-05-12', '2027-05-14', 'Birrul Walidain Fase A, B & C', 'event', 'keagamaan'],
            ['2027-05-17', '2027-05-18', 'Cuti Bersama Idul Adha', 'libur', 'libur'],
            ['2027-05-19', '2027-05-19', 'Qurban SD Namira', 'event', 'keagamaan'],
            ['2027-05-20', '2027-05-20', 'Hari Raya Waisak 2027', 'libur', 'libur'],
            ['2027-05-24', '2027-05-28', 'SAT Kelas 1-5', 'ujian', 'ujian'],
            ['2027-05-31', '2027-05-31', 'Remidial dan Pengayaan', 'ujian', 'ujian'],

            // === JUNI - JULI 2027 ===
            ['2027-06-01', '2027-06-01', 'Cuti Bersama Hari Kesaktian Pancasila', 'libur', 'libur'],
            ['2027-06-02', '2027-06-04', 'Classmeeting', 'event', 'kesiswaan'],
            ['2027-06-12', '2027-06-12', 'Pagelaran Seni dan Bakat', 'event', 'kesiswaan'],
            ['2027-06-15', '2027-06-15', 'Berbagi dengan Anak Yatim (Muharram)', 'event', 'keagamaan'],
            ['2027-06-17', '2027-06-17', 'Penerimaan Rapor', 'event', 'kurikulum'],
            ['2027-06-18', '2027-06-19', 'Pengambilan Seragam Murid Baru', 'event', 'humas'],
            ['2027-06-20', '2027-07-12', 'Libur Semester Genap', 'libur', 'libur'],
        ];

        $insertedOrUpdated = 0;
        foreach ($schedule as $entry) {
            [$startStr, $endStr, $description, $eventType, $colorKey] = $entry;
            $skipDates = $entry[5] ?? [];
            $colorHex = $colors[$colorKey] ?? '#6b7280';

            $cursor = Carbon::parse($startStr);
            $end = Carbon::parse($endStr);

            while ($cursor->lte($end)) {
                $dateStr = $cursor->toDateString();
                if (!in_array($dateStr, $skipDates, true)) {
                    Holiday::updateOrCreate(
                        [
                            'unit_id' => $sdUnit->id,
                            'date' => $dateStr,
                            'description' => $description,
                        ],
                        [
                            'event_type' => $eventType,
                            'color' => $colorHex,
                            'is_recurring' => false,
                        ]
                    );
                    $insertedOrUpdated++;
                }
                $cursor->addDay();
            }
        }

        $this->command?->info("Kalender Akademik SD Namira TA 2026/2027 berhasil disinkronkan: {$insertedOrUpdated} entri tanggal ({$sdUnit->name}, unit_id={$sdUnit->id}).");
    }
}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Al-Qur'an Metode Ummi - {{ $setting->school_name ?? $unit->name }}</title>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 12mm 13mm 12mm 13mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 9.5pt;
            color: #0f172a;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: {{ !empty($isPdf) ? '#ffffff' : '#e2e8f0' }};
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #1b4332;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .toolbar-btn {
            background: #ffffff;
            color: #1b4332;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            margin-left: 8px;
            text-decoration: none;
            display: inline-block;
        }

        .toolbar-btn-gold {
            background: #d4af37;
            color: #1b4332;
        }

        .print-page {
            background: #ffffff;
            width: {{ !empty($isPdf) ? '100%' : '215mm' }};
            min-height: {{ !empty($isPdf) ? 'auto' : '330mm' }};
            margin: {{ !empty($isPdf) ? '0' : '18px auto' }};
            padding: {{ !empty($isPdf) ? '0' : '12mm 13mm' }};
            box-shadow: {{ !empty($isPdf) ? 'none' : '0 4px 20px rgba(0, 0, 0, 0.14)' }};
            page-break-after: always;
        }

        .print-page:last-child {
            page-break-after: auto;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3.5px double #1b4332;
            margin-bottom: 14px;
        }

        .kop-table td {
            vertical-align: middle;
            padding-bottom: 10px;
        }

        .kop-title-1 {
            font-size: 13.5pt;
            font-weight: bold;
            color: #1b4332;
            margin: 0;
            letter-spacing: 0.4px;
        }

        .kop-title-2 {
            font-size: 10.5pt;
            font-weight: bold;
            color: #1b4332;
            margin: 2px 0 0 0;
        }

        .info-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #f8fafc;
            padding: 9px 12px;
            margin-bottom: 14px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .info-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }

        .info-label {
            color: #475569;
            width: 19%;
            white-space: nowrap;
        }

        .info-sep {
            color: #475569;
            width: 2%;
            text-align: center;
        }

        .info-val {
            width: 29%;
            color: #0f172a;
        }

        .section-block {
            margin-bottom: 13px;
        }

        .section-bar {
            background: #1b4332;
            color: #ffffff;
            padding: 5.5px 11px;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            border: 1px solid #1b4332;
            border-left: 4px solid #d4af37;
        }

        .score-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8pt;
        }

        .score-table th {
            background: #23533f;
            color: #ffffff;
            border: 1px solid #1b4332;
            padding: 5px 7px;
            font-weight: bold;
            text-align: left;
        }

        .score-table td {
            border: 1px solid #334155;
            padding: 4.5px 7px;
            vertical-align: top;
        }

        .sub-cat-row td {
            background: #d1fae5;
            color: #1b4332;
            font-weight: bold;
            font-size: 8.5pt;
            letter-spacing: 0.4px;
            border-left: 3px solid #2d6a4f;
            padding: 4px 9px;
        }

        .narrative {
            font-style: italic;
            font-size: 8.4pt;
            color: #1e293b;
            line-height: 1.35;
        }

        .grade-cell {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            vertical-align: middle !important;
        }

        .grade-d {
            color: #dc2626;
        }

        .rapor-footer {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-top: 8px;
        }

        .id-strip {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid #1b4332;
            margin-bottom: 10px;
        }

        .id-strip td {
            padding-top: 5px;
            font-size: 7.5pt;
        }

        .note-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 11px;
            margin-bottom: 12px;
        }

        .note-title {
            font-size: 8.6pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1b4332;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin: 0 0 6px 0;
        }

        .note-line {
            border-bottom: 1px solid #cbd5e1;
            height: 18px;
            margin-bottom: 4px;
        }

        .legend-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .legend-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #f8fafc;
            padding: 7px 10px;
            width: 245px;
            font-size: 8.4pt;
        }

        .legend-box h4 {
            font-size: 8.6pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1b4332;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin: 0 0 4px 0;
        }

        .legend-box p {
            margin: 1.5px 0;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 9pt;
            margin-bottom: 18px;
        }

        .sig-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 18px;
            position: relative;
        }

        .sig-space {
            height: 56px;
            position: relative;
        }

        .sig-name {
            font-weight: bold;
            border-top: 1px solid #1e293b;
            padding-top: 3px;
            width: 185px;
            margin: 0 auto;
            position: relative;
            z-index: 20;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
            .print-page {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    @if(empty($isPdf))
    <div class="toolbar no-print">
        <div>
            <strong style="font-size:14px;">Pratinjau Rapor Al-Qur'an Metode Ummi (F4)</strong>
            <span style="font-size:12px; opacity:0.85; margin-left:10px;">Total: {{ count($reports) }} Siswa &bull; {{ $activeYear->name }} ({{ $semesterLabel }})</span>
        </div>
        <div>
            <button type="button" class="toolbar-btn toolbar-btn-gold" onclick="window.print()">Cetak / Print F4</button>
            <button type="button" class="toolbar-btn" onclick="window.close()">Tutup</button>
        </div>
    </div>
    @endif

    @php
        $schoolName = strtoupper($setting->school_name ?: $unit->name);
        $schoolAddress = $setting->school_address ?: ($unit->address ?: 'Jl. Panglima Sudirman No. 170, Kraksaan');
        $cityName = $setting->city_name ?: 'Kraksaan';
        $kepalaTpq = $setting->kepala_tpq ?: '-';
        $kepalaTpqTitle = $setting->kepala_tpq_title ?: ('Kepala TPQ Ummi ' . $unit->name);
        $kepalaSekolah = $setting->kepala_sekolah ?: '-';
        $ttdCfg = $setting->ttd_config ?? [];
        $tpqW = (int) ($ttdCfg['tpq_width'] ?? 100);
        $tpqX = (int) ($ttdCfg['tpq_offset_x'] ?? 0);
        $tpqY = (int) ($ttdCfg['tpq_offset_y'] ?? 0);
        $kepsekW = (int) ($ttdCfg['kepsek_width'] ?? 100);
        $kepsekX = (int) ($ttdCfg['kepsek_offset_x'] ?? 0);
        $kepsekY = (int) ($ttdCfg['kepsek_offset_y'] ?? 0);
        $imgTpq = !empty($isPdf) ? ($ttdTpqSrc ?? null) : $setting?->ttd_kepala_tpq_url;
        $imgKepsek = !empty($isPdf) ? ($ttdKepsekSrc ?? null) : $setting?->ttd_kepala_sekolah_url;
    @endphp

    @foreach($reports as $report)
    <div class="print-page">
        {{-- KOP SURAT 2 LOGO --}}
        <table class="kop-table">
            <tr>
                <td style="width: 78px; text-align: left;">
                    <img src="{{ $unitLogoUrl }}" alt="Logo Unit" style="height: 68px; width: auto;">
                </td>
                <td style="text-align: center; padding: 0 8px 10px 8px;">
                    <div class="kop-title-1">LAPORAN PERKEMBANGAN DAN EVALUASI</div>
                    <div class="kop-title-2">PENCAPAIAN PEMBELAJARAN AL-QUR'AN METODE UMMI</div>
                    <div class="kop-title-2">{{ $schoolName }}</div>
                </td>
                <td style="width: 78px; text-align: right;">
                    <img src="{{ $ummiLogoUrl }}" alt="Logo Ummi" style="height: 68px; width: auto;">
                </td>
            </tr>
        </table>

        {{-- IDENTITAS SISWA --}}
        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td class="info-label">Nama Peserta Didik</td>
                    <td class="info-sep">:</td>
                    <td class="info-val" style="font-weight:bold; text-transform:uppercase;">{{ $report['student_name'] }}</td>
                    <td class="info-label">Kelas</td>
                    <td class="info-sep">:</td>
                    <td class="info-val" style="font-weight:bold;">{{ $report['classroom_name'] }}</td>
                </tr>
                <tr>
                    <td class="info-label">NISN / NIS</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $report['nisn'] !== '-' ? $report['nisn'] : $report['nis'] }}</td>
                    <td class="info-label">Semester</td>
                    <td class="info-sep">:</td>
                    <td class="info-val" style="font-weight:bold;">{{ $semesterLabel }}</td>
                </tr>
                <tr>
                    <td class="info-label">Sekolah</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $schoolName }}</td>
                    <td class="info-label">Tahun Pelajaran</td>
                    <td class="info-sep">:</td>
                    <td class="info-val" style="font-weight:bold;">{{ $activeYear->name }}</td>
                </tr>
                <tr>
                    <td class="info-label">Alamat</td>
                    <td class="info-sep">:</td>
                    <td class="info-val">{{ $schoolAddress }}</td>
                    <td class="info-label">Guru Al-Qur'an</td>
                    <td class="info-sep">:</td>
                    <td class="info-val" style="font-weight:bold;">{{ $report['teacher_name'] }}</td>
                </tr>
            </table>
        </div>

        {{-- TABEL MATERI POKOK --}}
        <div class="section-block">
            <div class="section-bar">Materi Pelajaran {{ $report['level_name'] }}</div>
            <table class="score-table">
                <thead>
                    <tr>
                        <th style="width: 34px; text-align: center;">No</th>
                        <th style="width: 30%;">Materi</th>
                        <th>Keterangan</th>
                        <th style="width: 48px; text-align: center;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['materi_rows'] as $idx => $row)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td class="narrative">{{ $row['narrative'] }}</td>
                        <td class="grade-cell {{ $row['grade'] === 'D' ? 'grade-d' : '' }}">{{ $row['grade'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; font-style:italic; color:#64748b; padding:9px;">
                            Belum ada materi yang dinilai pada semester ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TABEL HAFALAN & CAPAIAN IBADAH (HANYA TAMPIL JIKA ADA YANG DINILAI) --}}
        @if($report['has_hafalan'])
        <div class="section-block">
            <div class="section-bar">Hafalan {{ $report['level_name'] }}@if(!empty($report['grade_level'])) &amp; Materi Kelas {{ $report['grade_level'] }}@endif</div>
            <table class="score-table">
                <thead>
                    <tr>
                        <th style="width: 34px; text-align: center;">No</th>
                        <th style="width: 30%;">Hafalan / Materi</th>
                        <th>Keterangan</th>
                        <th style="width: 48px; text-align: center;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($report['surah_rows']) > 0)
                        <tr class="sub-cat-row"><td colspan="4">Surah Pendek ({{ $report['level_name'] }})</td></tr>
                        @foreach($report['surah_rows'] as $idx => $row)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="narrative">{{ $row['narrative'] }}</td>
                            <td class="grade-cell {{ $row['grade'] === 'D' ? 'grade-d' : '' }}">{{ $row['grade'] }}</td>
                        </tr>
                        @endforeach
                    @endif

                    @if(count($report['doa_rows']) > 0)
                        <tr class="sub-cat-row"><td colspan="4">Doa Harian @if(!empty($report['grade_level']))(Kelas {{ $report['grade_level'] }})@endif</td></tr>
                        @foreach($report['doa_rows'] as $idx => $row)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="narrative">{{ $row['narrative'] }}</td>
                            <td class="grade-cell {{ $row['grade'] === 'D' ? 'grade-d' : '' }}">{{ $row['grade'] }}</td>
                        </tr>
                        @endforeach
                    @endif

                    @if(count($report['hadist_rows']) > 0)
                        <tr class="sub-cat-row"><td colspan="4">Hadits @if(!empty($report['grade_level']))(Kelas {{ $report['grade_level'] }})@endif</td></tr>
                        @foreach($report['hadist_rows'] as $idx => $row)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="narrative">{{ $row['narrative'] }}</td>
                            <td class="grade-cell {{ $row['grade'] === 'D' ? 'grade-d' : '' }}">{{ $row['grade'] }}</td>
                        </tr>
                        @endforeach
                    @endif

                    @if(!empty($report['niat_sholat_rows']) && count($report['niat_sholat_rows']) > 0)
                        <tr class="sub-cat-row"><td colspan="4">Niat Sholat &amp; Bersuci @if(!empty($report['grade_level']))(Kelas {{ $report['grade_level'] }})@endif</td></tr>
                        @foreach($report['niat_sholat_rows'] as $idx => $row)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="narrative">{{ $row['narrative'] }}</td>
                            <td class="grade-cell {{ $row['grade'] === 'D' ? 'grade-d' : '' }}">{{ $row['grade'] }}</td>
                        </tr>
                        @endforeach
                    @endif

                    @if(!empty($report['bacaan_sholat_rows']) && count($report['bacaan_sholat_rows']) > 0)
                        <tr class="sub-cat-row"><td colspan="4">Bacaan Sholat @if(!empty($report['grade_level']))(Kelas {{ $report['grade_level'] }})@endif</td></tr>
                        @foreach($report['bacaan_sholat_rows'] as $idx => $row)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="narrative">{{ $row['narrative'] }}</td>
                            <td class="grade-cell {{ $row['grade'] === 'D' ? 'grade-d' : '' }}">{{ $row['grade'] }}</td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        @endif

        {{-- FOOTER RAPOR --}}
        <div class="rapor-footer">
            {{-- ID Strip --}}
            <table class="id-strip">
                <tr>
                    <td style="font-weight:bold; color:#2d6a4f; text-transform:uppercase; letter-spacing:0.4px;">
                        {{ $schoolName }} &mdash; Laporan Al-Qur'an Metode Ummi
                    </td>
                    <td style="text-align:right; color:#64748b;">
                        {{ $report['student_name'] }} &bull; Kls. {{ $report['classroom_name'] }} &bull; {{ $report['level_name'] }} &bull; {{ $semesterLabel }} {{ $activeYear->name }}
                    </td>
                </tr>
            </table>

            {{-- Catatan Guru --}}
            <div class="note-box">
                <div class="note-title">Catatan Guru Al-Qur'an</div>
                @if(!empty($report['teacher_note']))
                    <div style="font-size: 9pt; color: #1e293b; line-height: 1.6; white-space: pre-wrap; min-height: 54px;">{{ $report['teacher_note'] }}</div>
                @else
                    <div class="note-line"></div>
                    <div class="note-line"></div>
                    <div class="note-line"></div>
                @endif
            </div>

            {{-- Keterangan Nilai & Tanggal --}}
            <table class="legend-wrap">
                <tr>
                    <td style="vertical-align: top;">
                        <div class="legend-box">
                            <h4>Keterangan Nilai</h4>
                            <p><strong style="color:#1b4332;">A</strong> = Nilai 91 - 95 (Sangat Lancar / Tartil)</p>
                            <p><strong style="color:#1b4332;">B</strong> = Nilai 81 - 90 (Lancar / Cukup Tartil)</p>
                            <p><strong style="color:#1b4332;">C</strong> = Nilai 71 - 80 (Kurang Lancar / Perlu Bimbingan)</p>
                            <p><strong style="color:#dc2626;">D</strong> = Nilai 65 - 70 (Belum Lancar / Mengulang)</p>
                            <p style="margin-top:4px; padding-top:3px; border-top:1px solid #e2e8f0; font-size:7.6pt; font-style:italic; color:#64748b;">
                                * Hanya materi yang telah dinilai yang ditampilkan
                            </p>
                        </div>
                    </td>
                    <td style="vertical-align: bottom; text-align: right; font-style: italic; color: #475569; font-size: 9pt;">
                        {{ $cityName }}, {{ $reportDate }}
                    </td>
                </tr>
            </table>

            {{-- TTD Baris 1: Wali Murid & Guru Al-Qur'an --}}
            <table class="sig-table">
                <tr>
                    <td>
                        <div>Wali Murid,</div>
                        <div class="sig-space"></div>
                        <div class="sig-name" style="border-top: none; font-weight: normal;">( .................................... )</div>
                    </td>
                    <td>
                        <div>Guru Al-Qur'an,</div>
                        <div class="sig-space"></div>
                        <div class="sig-name">{{ $report['teacher_name'] }}</div>
                    </td>
                </tr>
            </table>

            {{-- TTD Baris 2: Kepala TPQ & Kepala Sekolah --}}
            <table class="sig-table" style="margin-bottom: 0;">
                <tr>
                    <td>
                        <div>Mengetahui,</div>
                        <div>{{ $kepalaTpqTitle }}</div>
                        <div class="sig-space">
                            @if($imgTpq)
                                <img src="{{ $imgTpq }}" alt="TTD Kepala TPQ" style="width: {{ $tpqW }}px; max-height: 66px; position: absolute; bottom: {{ -6 - $tpqY }}px; left: 50%; margin-left: {{ -($tpqW / 2) + $tpqX }}px; z-index: 10;">
                            @endif
                        </div>
                        <div class="sig-name">{{ $kepalaTpq }}</div>
                    </td>
                    <td>
                        <div>Mengetahui,</div>
                        <div>Kepala Sekolah</div>
                        <div class="sig-space">
                            @if($imgKepsek)
                                <img src="{{ $imgKepsek }}" alt="TTD Kepala Sekolah" style="width: {{ $kepsekW }}px; max-height: 66px; position: absolute; bottom: {{ -6 - $kepsekY }}px; left: 50%; margin-left: {{ -($kepsekW / 2) + $kepsekX }}px; z-index: 10;">
                            @endif
                        </div>
                        <div class="sig-name">{{ $kepalaSekolah }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    @endforeach
</body>
</html>

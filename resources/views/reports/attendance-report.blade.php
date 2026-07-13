<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Absensi Karyawan - {{ $unit->name ?? 'Yayasan Namira' }}</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #1e293b;
        }
        
        /* Header */
        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-logo-cell {
            width: 70px;
            vertical-align: middle;
        }
        
        .header-logo-box {
            width: 55px;
            height: 55px;
            background: #0f766e;
            border-radius: 8px;
            text-align: center;
            line-height: 55px;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header-info-cell {
            vertical-align: middle;
            padding-left: 15px;
        }
        
        .school-foundation {
            font-size: 10px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
            margin-bottom: 2px;
        }
        
        .school-contact {
            font-size: 8px;
            color: #64748b;
        }
        
        /* Report Meta Title */
        .report-title-section {
            margin-bottom: 15px;
        }
        
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .report-subtitle {
            font-size: 10px;
            color: #475569;
            font-weight: 500;
        }
        
        /* Filter Panel Badge */
        .filters-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        
        .filters-title {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .filter-pill {
            display: inline-block;
            background: #e2e8f0;
            color: #334155;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            margin-right: 8px;
            font-weight: bold;
        }
        
        /* Stats Summary Box */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            width: 12.5%;
        }
        
        .stat-card.active-present {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }
        
        .stat-card.active-late {
            background: #fff1f2;
            border-color: #fecdd3;
        }
        
        .stat-card.active-permit {
            background: #fffbeb;
            border-color: #fde68a;
        }
        
        .stat-number {
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
        }
        
        .stat-card.active-present .stat-number { color: #047857; }
        .stat-card.active-late .stat-number { color: #be123c; }
        .stat-card.active-permit .stat-number { color: #b45309; }
        
        .stat-label {
            font-size: 7px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
        }
        
        /* Main Roster Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .data-table th {
            background: #0f172a;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
            padding: 6px 4px;
            border: 1px solid #1e293b;
            text-align: center;
        }
        
        .data-table th.left-align,
        .data-table td.left-align {
            text-align: left;
        }
        
        .data-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8px;
        }
        
        .data-table tr.zebra {
            background: #f8fafc;
        }
        
        .data-table tr.total-row {
            background: #e2e8f0;
            font-weight: bold;
        }
        
        .data-table tr.total-row td {
            border-top: 2px solid #0f172a;
            color: #0f172a;
            font-weight: bold;
            font-size: 8px;
        }
        
        /* Percentage coloring */
        .pct-high { color: #047857; font-weight: bold; }
        .pct-med { color: #b45309; font-weight: bold; }
        .pct-low { color: #be123c; font-weight: bold; }
        
        /* Footer */
        .footer-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 8px;
            color: #64748b;
        }
        
        .footer-sign-section {
            width: 100%;
            margin-top: 30px;
        }
        
        .sign-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .sign-box {
            width: 33%;
            text-align: center;
            font-size: 9px;
        }
        
        .sign-space {
            height: 45px;
        }
        
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        
        .footer-pages {
            position: fixed;
            bottom: -5mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            font-size: 7.5px;
            color: #94a3b8;
        }
        
        .page-number:before {
            content: "Halaman " counter(page);
        }
    </style>
</head>
<body>
    
    <!-- School Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    @if($unit && $unit->logo && file_exists(storage_path('app/public/' . $unit->logo)))
                        <img src="{{ storage_path('app/public/' . $unit->logo) }}" style="width: 55px; height: 55px; object-fit: contain;">
                    @else
                        <div class="header-logo-box">
                            {{ strtoupper(substr($unit->code ?? 'YN', 0, 2)) }}
                        </div>
                    @endif
                </td>
                <td class="header-info-cell">
                    <div class="school-foundation">Yayasan Namira School Foundation</div>
                    <div class="school-name">{{ $unit->name ?? 'Kantor Pusat Yayasan Namira' }}</div>
                    <div class="school-contact">
                        @if($unit && $unit->address) <span>Alamat: {{ $unit->address }}</span> @endif
                        @if($unit && $unit->phone) <span style="margin-left: 10px;">Telp: {{ $unit->phone }}</span> @endif
                        @if($unit && $unit->email) <span style="margin-left: 10px;">Email: {{ $unit->email }}</span> @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Title Section -->
    <div class="report-title-section">
        <h1 class="report-title">Laporan Rekapitulasi Kehadiran Guru & Staf</h1>
        <div class="report-subtitle">Periode Kerja: {{ $monthName }}</div>
    </div>

    <!-- Active Filters -->
    <div class="filters-panel">
        <div class="filters-title">Filter Laporan Aktif:</div>
        <div>
            @if(count($activeFilters) > 0)
                @foreach($activeFilters as $key => $val)
                    <span class="filter-pill">{{ $key }}: {{ $val }}</span>
                @endforeach
            @else
                <span class="filter-pill">Semua Data (Tanpa Filter)</span>
            @endif
        </div>
    </div>

    <!-- Summary Stats -->
    <table class="stats-table">
        <tr>
            <td class="stat-card" style="padding-left: 0;">
                <div class="stat-number">{{ $stats['total_employees'] ?? count($recapData) }}</div>
                <div class="stat-label">Total Pegawai</div>
            </td>
            <td class="stat-card">
                <div class="stat-number">{{ $workDays }}</div>
                <div class="stat-label">Hari Kerja</div>
            </td>
            <td class="stat-card active-present">
                <div class="stat-number">{{ $totals['hadir'] }}</div>
                <div class="stat-label">Total Hadir</div>
            </td>
            <td class="stat-card active-late">
                <div class="stat-number">{{ $totals['telat'] }}</div>
                <div class="stat-label">Terlambat</div>
            </td>
            <td class="stat-card active-permit">
                <div class="stat-number">{{ $totals['izin'] }}</div>
                <div class="stat-label">Izin</div>
            </td>
            <td class="stat-card" style="background: #eff6ff; border-color: #bfdbfe;">
                <div class="stat-number" style="color: #1d4ed8;">{{ $totals['sakit'] }}</div>
                <div class="stat-label">Sakit</div>
            </td>
            <td class="stat-card" style="background: #fef2f2; border-color: #fca5a5;">
                <div class="stat-number" style="color: #b91c1c;">{{ $totals['alpha'] }}</div>
                <div class="stat-label">Alpha</div>
            </td>
            <td class="stat-card" style="background: #f0fdfa; border-color: #99f6e4; padding-right: 0;">
                <div class="stat-number" style="color: #0d9488;">{{ $avgAttendance }}%</div>
                <div class="stat-label">Avg Hadir</div>
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 10%;">NIP / NUPTK</th>
                <th class="left-align" style="width: 20%;">Nama Karyawan / Guru</th>
                <th class="left-align" style="width: 12%;">Jabatan</th>
                <th class="left-align" style="width: 18%;">Mata Pelajaran</th>
                <th class="left-align" style="width: 12%;">Unit Kerja</th>
                <th style="width: 4%;">Hadir</th>
                <th style="width: 4%;">Telat</th>
                <th style="width: 4%;">Izin</th>
                <th style="width: 4%;">Sakit</th>
                <th style="width: 4%;">Cuti</th>
                <th style="width: 4%;">Alpha</th>
                <th style="width: 6%;">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recapData as $index => $row)
            <tr class="{{ $index % 2 === 1 ? 'zebra' : '' }}">
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['nip'] }}</td>
                <td class="left-align" style="font-weight: bold; color: #0f172a;">{{ $row['name'] }}</td>
                <td class="left-align">{{ $row['jabatan'] }}</td>
                <td class="left-align">{{ $row['subjects'] }}</td>
                <td class="left-align">{{ $row['unit_name'] }}</td>
                <td>{{ $row['hadir'] }}</td>
                <td>{{ $row['terlambat'] }}</td>
                <td>{{ $row['izin'] }}</td>
                <td>{{ $row['sakit'] }}</td>
                <td>{{ $row['cuti'] }}</td>
                <td>{{ $row['alpha'] }}</td>
                <td class="{{ $row['percentage'] >= 90 ? 'pct-high' : ($row['percentage'] >= 75 ? 'pct-med' : 'pct-low') }}">
                    {{ $row['percentage'] }}%
                </td>
            </tr>
            @endforeach
            
            <tr class="total-row">
                <td colspan="2">TOTAL REKAP</td>
                <td class="left-align">{{ count($recapData) }} Orang</td>
                <td></td>
                <td></td>
                <td></td>
                <td>{{ $totals['hadir'] }}</td>
                <td>{{ $totals['telat'] }}</td>
                <td>{{ $totals['izin'] }}</td>
                <td>{{ $totals['sakit'] }}</td>
                <td>{{ $totals['cuti'] }}</td>
                <td>{{ $totals['alpha'] }}</td>
                <td class="{{ $avgAttendance >= 90 ? 'pct-high' : ($avgAttendance >= 75 ? 'pct-med' : 'pct-low') }}">
                    {{ $avgAttendance }}%
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Signature / Sign Area -->
    <div class="footer-sign-section">
        <table class="sign-table">
            <tr>
                <td class="sign-box">
                    <div>Mengetahui,</div>
                    @if($unit)
                        <div style="margin-top: 3px;">Kepala Unit {{ $unit->name }}</div>
                        <div class="sign-space"></div>
                        <div class="sign-name">
                            {{ $unit->principal ? $unit->principal->name : '..........................' }}
                        </div>
                        <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">
                            NIP: {{ ($unit->principal && $unit->principal->teacher_profile) ? $unit->principal->teacher_profile->nip : '..........................' }}
                        </div>
                    @else
                        <div style="margin-top: 3px;">Ketua Yayasan Namira</div>
                        <div class="sign-space"></div>
                        <div class="sign-name">..........................</div>
                        <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">NIP: ..............................</div>
                    @endif
                </td>
                <td class="sign-box">
                    &nbsp;
                </td>
                <td class="sign-box">
                    <div>Probolinggo, {{ Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                    <div style="margin-top: 3px;">Dibuat Oleh: Staf Administrasi</div>
                    <div class="sign-space"></div>
                    <div class="sign-name">{{ $printedBy }}</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">Sistem SuperApp Namira</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Fixed Footer -->
    <div class="footer-pages">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: left;">Sistem ERP SuperApp Namira v1.2.0 • Laporan Resmi Kehadiran</td>
                <td style="text-align: right; font-weight: bold; color: #0f172a;">
                    Waktu Cetak: {{ $printDate }} • <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

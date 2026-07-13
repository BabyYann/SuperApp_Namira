<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Kehadiran Individu - {{ $employee->name }}</title>
    <style>
        @page {
            margin: 20mm 15mm 25mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        
        /* Header */
        .header {
            border-bottom: 3px solid #0d9488;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-content {
            display: table;
            width: 100%;
        }
        
        .logo-container {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0d9488 0%, #134e4a 100%);
            border-radius: 10px;
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            line-height: 70px;
        }
        
        .org-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 15px;
        }
        
        .org-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
            margin-bottom: 2px;
        }
        
        .org-subtitle {
            font-size: 11px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .org-address {
            font-size: 9px;
            color: #888;
        }
        
        /* Title Section */
        .title-section {
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
            padding: 15px;
            border-radius: 8px;
        }
        
        .title {
            font-size: 14px;
            font-weight: bold;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 11px;
            color: #666;
        }
        
        /* Employee Card */
        .employee-card {
            width: 100%;
            margin-bottom: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
        }
        
        .employee-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .employee-table td {
            padding: 3px 0;
            font-size: 10px;
            vertical-align: top;
        }
        
        .employee-table td.label {
            color: #666;
            font-weight: bold;
            width: 120px;
        }
        
        .employee-table td.val {
            color: #333;
            font-weight: bold;
        }
        
        /* Stats Summary */
        .stats-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            table-layout: fixed;
        }
        
        .stat-box {
            display: table-cell;
            text-align: center;
            padding: 10px 5px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .stat-box.hadir { background: #d1fae5; border-color: #a7f3d0; }
        .stat-box.telat { background: #fee2e2; border-color: #fca5a5; }
        .stat-box.izin { background: #fef3c7; border-color: #fde047; }
        .stat-box.sakit { background: #dbeafe; border-color: #bfdbfe; }
        .stat-box.dinas { background: #e0e7ff; border-color: #c7d2fe; }
        .stat-box.pct { background: #f1f5f9; border-color: #cbd5e1; }
        
        .stat-number {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
        }
        
        .stat-label {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #64748b;
            margin-top: 2px;
        }
        
        /* Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table.data-table thead tr {
            background: #0d9488;
            color: white;
        }
        
        table.data-table th {
            padding: 8px 6px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #0f766e;
        }
        
        table.data-table tbody tr {
            border: 1px solid #e5e7eb;
        }
        
        table.data-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        table.data-table td {
            padding: 6px 6px;
            text-align: center;
            font-size: 9px;
            border: 1px solid #e5e7eb;
        }
        
        /* Status Badges in print */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge.present { background: #d1fae5; color: #065f46; }
        .badge.late { background: #fee2e2; color: #991b1b; }
        .badge.sick { background: #dbeafe; color: #1e40af; }
        .badge.permit { background: #fef3c7; color: #92400e; }
        .badge.business_trip { background: #e0e7ff; color: #3730a3; }
        .badge.not_recorded { background: #f1f5f9; color: #475569; }

        /* Footer signatures */
        .footer-sig {
            margin-top: 40px;
            width: 100%;
            display: table;
        }
        
        .sig-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            font-size: 10px;
        }
        
        .sig-space {
            height: 60px;
        }
        
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Kop Surat -->
    <div class="header">
        <div class="header-content">
            <div class="logo-container">
                @if($unit && $unit->logo)
                    <img src="{{ public_path('storage/' . $unit->logo) }}" class="logo" alt="Logo">
                @else
                    <div class="logo-placeholder">N</div>
                @endif
            </div>
            <div class="org-info">
                <div class="org-name">YAYASAN NAMIRA SCHOOL</div>
                <div class="org-subtitle">{{ $unit ? $unit->name : 'Satuan Pendidikan Namira' }}</div>
                <div class="org-address">{{ $unit ? $unit->address : 'Jalan Pasar 1 No. 58, Tanjung Sari, Medan Selayang' }}</div>
            </div>
        </div>
    </div>

    <!-- Title -->
    <div class="title-section">
        <div class="title">Rapor Kehadiran Karyawan Individu</div>
        <div class="subtitle">Periode Bulan: {{ $monthName }}</div>
    </div>

    <!-- Identitas Pegawai -->
    <div class="employee-card">
        <table class="employee-table">
            <tr>
                <td class="label">Nama Pegawai</td>
                <td class="val">: {{ $employee->name }}</td>
            </tr>
            <tr>
                <td class="label">Email / ID</td>
                <td class="val">: {{ $employee->email }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan / Role</td>
                <td class="val">: {{ ucwords(str_replace('_', ' ', $employee->roles->first()?->name ?? '-')) }}</td>
            </tr>
            <tr>
                <td class="label">Unit Kerja</td>
                <td class="val">: {{ $unit ? $unit->name : '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- Summary Stats Box -->
    <div class="stats-row">
        <div class="stat-box hadir" style="width: 16%;">
            <div class="stat-number">{{ $stats['present'] }}</div>
            <div class="stat-label">Hadir</div>
        </div>
        <div class="stat-box telat" style="width: 16%;">
            <div class="stat-number">{{ $stats['late'] }}</div>
            <div class="stat-label">Telat</div>
        </div>
        <div class="stat-box izin" style="width: 16%;">
            <div class="stat-number">{{ $stats['permit'] }}</div>
            <div class="stat-label">Izin</div>
        </div>
        <div class="stat-box sakit" style="width: 16%;">
            <div class="stat-number">{{ $stats['sick'] }}</div>
            <div class="stat-label">Sakit</div>
        </div>
        <div class="stat-box dinas" style="width: 16%;">
            <div class="stat-number">{{ $stats['business_trip'] }}</div>
            <div class="stat-label">Dinas</div>
        </div>
        <div class="stat-box pct" style="width: 20%;">
            <div class="stat-number">{{ $stats['attendance_percentage'] }}%</div>
            <div class="stat-label">Disiplin</div>
        </div>
    </div>

    @if($stats['total_late_minutes'] > 0)
        <p style="font-size: 9px; font-weight: bold; color: #b91c1c; margin-bottom: 10px;">
            ⚠️ Akumulasi keterlambatan bulan ini: {{ $stats['total_late_minutes'] }} menit.
        </p>
    @endif

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 20%;">Tanggal</th>
                <th style="width: 15%;">Jam Masuk</th>
                <th style="width: 15%;">Jam Pulang</th>
                <th style="width: 15%;">Telat (Menit)</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 15%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $att)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}</td>
                    <td style="font-weight: bold;">{{ $att->check_in_time ? substr($att->check_in_time, 0, 5) : '-' }}</td>
                    <td style="font-weight: bold;">{{ $att->check_out_time ? substr($att->check_out_time, 0, 5) : '-' }}</td>
                    <td style="color: {{ $att->late_minutes > 0 ? '#b91c1c' : '#333' }}; font-weight: {{ $att->late_minutes > 0 ? 'bold' : 'normal' }}">
                        {{ $att->late_minutes > 0 ? '+' . $att->late_minutes . 'm' : '-' }}
                    </td>
                    <td>
                        <span class="badge {{ $att->status }}">
                            {{ $att->status === 'present' ? 'Hadir' : ($att->status === 'late' ? 'Telat' : ($att->status === 'sick' ? 'Sakit' : ($att->status === 'permit' ? 'Izin' : ($att->status === 'business_trip' ? 'Dinas' : 'Belum Absen')))) }}
                        </span>
                    </td>
                    <td style="font-style: italic; font-size: 8px;">{{ $att->note ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #888; padding: 15px 0;">Tidak ada catatan kehadiran untuk bulan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="footer-sig">
        <div class="sig-col">
            <p>Pegawai Bersangkutan</p>
            <div class="sig-space"></div>
            <p class="sig-name">{{ $employee->name }}</p>
            <p>ID: {{ $employee->email }}</p>
        </div>
        <div class="sig-col">
            <p>Medan, {{ now()->translatedFormat('d F Y') }}</p>
            <p>Kepala Unit / HRD Yayasan</p>
            <div class="sig-space"></div>
            <p class="sig-name">( ..................................... )</p>
            <p>Yayasan Namira School</p>
        </div>
    </div>
</body>
</html>

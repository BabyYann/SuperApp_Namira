<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Kenaikan Kelas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            color: #0d9488;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        .meta {
            margin-bottom: 15px;
            font-size: 10px;
            color: #666;
        }
        .meta-row {
            display: flex;
            margin-bottom: 3px;
        }
        .meta-label {
            width: 120px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #0d9488;
            color: white;
            font-size: 10px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-naik {
            background-color: #dcfce7;
            color: #166534;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }
        .status-tinggal {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }
        .status-lulus {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }
        .status-pindah {
            background-color: #f3e8ff;
            color: #7c3aed;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }
        .status-keluar {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 9px;
            color: #999;
        }
        .summary {
            margin-top: 15px;
            background: #f0fdfa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #99f6e4;
        }
        .summary-title {
            font-weight: bold;
            color: #0d9488;
            margin-bottom: 5px;
        }
        .summary-grid {
            display: flex;
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .summary-label {
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAP KENAIKAN KELAS</h1>
        <h2>{{ $unit ? $unit->name : 'Semua Unit' }}</h2>
    </div>

    <div class="meta">
        <div class="meta-row">
            <span class="meta-label">Tahun Ajaran Tujuan:</span>
            <span>{{ $academicYear ? $academicYear->name : 'Semua Tahun' }}</span>
        </div>
        @if($filters['status'])
        <div class="meta-row">
            <span class="meta-label">Filter Status:</span>
            <span>{{ $statusLabels[$filters['status']] ?? $filters['status'] }}</span>
        </div>
        @endif
        <div class="meta-row">
            <span class="meta-label">Total Data:</span>
            <span>{{ $promotions->count() }} siswa</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Dicetak:</span>
            <span>{{ $generatedAt }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 120px;">Nama Siswa</th>
                <th style="width: 80px;">NIS</th>
                <th style="width: 100px;">Dari Kelas</th>
                <th style="width: 100px;">Ke Kelas</th>
                <th style="width: 70px;">Status</th>
                <th style="width: 100px;">Catatan</th>
                <th style="width: 80px;">Diproses Oleh</th>
                <th style="width: 80px;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($promotions as $index => $promo)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $promo->student->full_name ?? '-' }}</td>
                <td>{{ $promo->student->nis ?? '-' }}</td>
                <td>{{ $promo->fromClassroom->name ?? '-' }} ({{ $promo->fromAcademicYear->name ?? '' }})</td>
                <td>{{ $promo->toClassroom->name ?? '-' }}</td>
                <td style="text-align: center;">
                    <span class="status-{{ $promo->status }}">{{ $statusLabels[$promo->status] ?? $promo->status }}</span>
                </td>
                <td>{{ $promo->notes ?? '-' }}</td>
                <td>{{ $promo->promotedBy->name ?? '-' }}</td>
                <td>{{ $promo->promoted_at ? \Carbon\Carbon::parse($promo->promoted_at)->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px; color: #999;">Tidak ada data promosi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $countByStatus = $promotions->groupBy('status')->map->count();
    @endphp
    
    <div class="summary">
        <div class="summary-title">Ringkasan:</div>
        <table style="border: none; margin-top: 5px;">
            <tr style="background: none;">
                @foreach($statusLabels as $key => $label)
                <td style="border: none; text-align: center; padding: 5px 15px;">
                    <div style="font-size: 16px; font-weight: bold;">{{ $countByStatus[$key] ?? 0 }}</div>
                    <div style="font-size: 9px; color: #666;">{{ $label }}</div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>

    <div class="footer">
        Dokumen ini digenerate oleh SuperApp Yayasan Namira
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Login Guru Pavlov Center, Daycare & PAUD/TK Namira</title>
    <style>
        @page {
            margin: 1.2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f766e;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: bold;
            color: #0f766e;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            font-size: 9.5px;
            color: #64748b;
            margin: 0;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f766e;
            margin-top: 14px;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #ccfbf1;
            text-transform: uppercase;
        }
        .info-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 5px;
            padding: 6px 10px;
            margin-bottom: 10px;
        }
        .info-box p {
            font-size: 9px;
            color: #166534;
            margin: 2px 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #0f766e;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace, Courier, monospace;
            font-size: 8.5px;
        }
        .page-break {
            page-break-before: always;
        }
        .footer {
            margin-top: 10px;
            text-align: right;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>YAYASAN NAMIRA PROBOLINGGO</h1>
        <p style="font-weight: bold; color: #0f172a; font-size: 10.5px;">DAFTAR AKUN LOGIN GURU PAVLOV CENTER, DAYCARE (TPA), & PAUD/TK</p>
        <p>Sistem Informasi Manajemen Terpadu (SuperApp Namira)</p>
    </div>

    <div class="info-box">
        <p><strong>Ketentuan Password Default Login:</strong></p>
        <p>1. Akun dengan <strong>NIY</strong> (Nomor Induk Yayasan): Password = <strong>NIY</strong>.</p>
        <p>2. Akun tanpa NIY (Email Baru / Dummy): Password = <strong>guru123</strong>.</p>
    </div>

    <div class="section-title">1. Pavlov Center Namira Kraksaan (22 Guru)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 30%;">Nama Guru</th>
                <th style="width: 35%;">Email (Username)</th>
                <th style="width: 15%;" class="text-center">NIY / Password</th>
                <th style="width: 15%;" class="text-center">No. HP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pavlovTeachers as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $item['nama'] }}</strong></td>
                <td class="font-mono">{{ $item['email'] }}</td>
                <td class="text-center font-mono"><strong>{{ $item['NIY'] ?: 'guru123' }}</strong></td>
                <td class="text-center font-mono">{{ $item['no_hp'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="section-title">2. Daycare (TPA) Namira Kraksaan & Dringu (15 Guru)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;">Nama Guru</th>
                <th style="width: 18%;">Unit</th>
                <th style="width: 27%;">Email (Username)</th>
                <th style="width: 13%;" class="text-center">NIY / Password</th>
                <th style="width: 12%;" class="text-center">No. HP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($daycareTeachers as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $item['nama'] }}</strong></td>
                <td>{{ $item['unit'] }}</td>
                <td class="font-mono">{{ $item['email'] }}</td>
                <td class="text-center font-mono"><strong>{{ $item['NIY'] ?: 'guru123' }}</strong></td>
                <td class="text-center font-mono">{{ $item['no_hp'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">3. PAUD / Playgroup & TK Namira Kraksaan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;">Nama Guru</th>
                <th style="width: 18%;">Unit</th>
                <th style="width: 27%;">Email (Username)</th>
                <th style="width: 13%;" class="text-center">NIY / Password</th>
                <th style="width: 12%;" class="text-center">No. HP</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paudTeachers as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $item['nama'] }}</strong></td>
                <td>{{ $item['unit'] }}</td>
                <td class="font-mono">{{ $item['email'] }}</td>
                <td class="text-center font-mono"><strong>{{ $item['NIY'] ?: 'guru123' }}</strong></td>
                <td class="text-center font-mono">{{ $item['no_hp'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d-m-Y H:i:s') }} | SuperApp Yayasan Namira Probolinggo
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun Login Guru SD Namira</title>
    <style>
        @page {
            margin: 1.2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px solid #064e3b;
            margin-bottom: 14px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            color: #064e3b;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }
        .info-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 14px;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-box td {
            font-size: 9.5px;
            color: #166534;
            padding: 2px 0;
        }
        .info-box strong {
            color: #064e3b;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th {
            background-color: #064e3b;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #064e3b;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
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
            font-size: 9px;
        }
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 8.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>YAYASAN NAMIRA PROBOLINGGO</h1>
        <p style="font-weight: bold; color: #0f172a; font-size: 11px;">DAFTAR AKUN LOGIN GURU SD NAMIRA</p>
        <p>Sistem Informasi Manajemen Terpadu (SuperApp Namira)</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td width="50%"><strong>URL Login Portal:</strong> https://namiraschool.com/login</td>
                <td width="50%"><strong>Ketentuan Password Default:</strong> Nomor NIY Guru</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 8.5px; color: #15803d; margin-top: 3px;">
                    * Disarankan agar setiap Bapak/Ibu Guru segera mengubah password setelah berhasil melakukan login pertama kali.
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="32%">Nama Lengkap Guru</th>
                <th width="33%">Email (Username Login)</th>
                <th width="15%" class="text-center">NIY (Password)</th>
                <th width="15%" class="text-center">No. WhatsApp</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $index => $t)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $t['nama'] }}</strong></td>
                <td class="font-mono">{{ $t['email'] }}</td>
                <td class="text-center font-mono" style="font-weight: bold; color: #064e3b;">{{ $t['NIY'] ?: 'guru123' }}</td>
                <td class="text-center font-mono">{{ $t['no_hp'] ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem SuperApp Namira &bull; {{ date('d F Y') }}
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Mutasi Aset - {{ $transfer->inventory->code }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #1e293b; line-height: 1.5; margin: 20px; }
        .header { text-align: center; border-bottom: 3px double #0d9488; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; color: #0d9488; margin: 0; text-transform: uppercase; }
        .header p { font-size: 9pt; color: #64748b; margin: 2px 0 0 0; }
        .title { text-align: center; margin: 20px 0; }
        .title h2 { font-size: 13pt; text-decoration: underline; margin: 0; text-transform: uppercase; color: #0f172a; }
        .title p { font-size: 9pt; color: #475569; margin-top: 4px; }
        .table-info { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table-info th, .table-info td { border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 10pt; text-align: left; }
        .table-info th { background-color: #f0fdf4; color: #0f766e; }
        .signatures { margin-top: 40px; width: 100%; border-collapse: collapse; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; font-size: 10pt; padding: 10px; }
        .sign-space { height: 70px; }
        .footer-note { font-size: 8pt; color: #94a3b8; margin-top: 30px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>YAYASAN NAMIRA KRAKSAAN</h1>
        <p>Sistem Informasi Manajemen Sarana & Prasarana (SuperApp Namira)</p>
    </div>

    <div class="title">
        <h2>BERITA ACARA MUTASI / PEMINDAHAN ASET</h2>
        <p>Nomor Registrasi Mutasi: BA-MUTASI/{{ $transfer->id }}/{{ date('Y') }}</p>
    </div>

    <p>Pada hari ini, tanggal <strong>{{ $dateFormatted }}</strong>, telah dilaksanakan mutasi / pemindahan aset inventaris sarana dan prasarana dengan rincian sebagai berikut:</p>

    <table class="table-info">
        <tr>
            <th width="30%">Nama Barang</th>
            <td><strong>{{ $transfer->inventory->name }}</strong> @if($transfer->inventory->brand) ({{ $transfer->inventory->brand }}) @endif</td>
        </tr>
        <tr>
            <th>Kode Inventaris</th>
            <td><code>{{ $transfer->inventory->code }}</code></td>
        </tr>
        <tr>
            <th>Sumber Dana</th>
            <td>{{ $transfer->inventory->funding_source == 'BOS' ? 'Dana BOS' : 'Dana Yayasan' }}</td>
        </tr>
        <tr>
            <th>Unit & Lokasi Asal</th>
            <td>{{ $transfer->fromUnit->name ?? '-' }} @if($transfer->fromRoom) ({{ $transfer->fromRoom->name }}) @endif</td>
        </tr>
        <tr>
            <th>Unit & Lokasi Tujuan</th>
            <td><strong>{{ $transfer->toUnit->name ?? '-' }}</strong> @if($transfer->toRoom) (<strong>{{ $transfer->toRoom->name }}</strong>) @endif</td>
        </tr>
        <tr>
            <th>Alasan Mutasi</th>
            <td>{{ $transfer->reason }}</td>
        </tr>
    </table>

    <p>Demikian Berita Acara Mutasi ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya dan dicatat secara resmi pada sistem Sarpras Yayasan Namira.</p>

    <table class="signatures">
        <tr>
            <td>
                Pihak Penyerah (Asal)<br><br>
                <div class="sign-space"></div>
                <strong>({{ $transfer->transferredBy->name ?? 'Petugas Mutasi' }})</strong><br>
                <span>Unit {{ $transfer->fromUnit->name ?? 'Asal' }}</span>
            </td>
            <td>
                Pihak Penerima (Tujuan)<br><br>
                <div class="sign-space"></div>
                <strong>(...................................)</strong><br>
                <span>Unit {{ $transfer->toUnit->name ?? 'Tujuan' }}</span>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan secara elektronik oleh SuperApp Namira pada {{ date('d/m/Y H:i:s') }}. Validitas data terverifikasi pada database sistem.
    </div>

</body>
</html>

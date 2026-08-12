<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Penghapusan Aset - {{ $disposal->inventory->code }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #1e293b; line-height: 1.5; margin: 20px; }
        .header { text-align: center; border-bottom: 3px double #e11d48; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; color: #e11d48; margin: 0; text-transform: uppercase; }
        .header p { font-size: 9pt; color: #64748b; margin: 2px 0 0 0; }
        .title { text-align: center; margin: 20px 0; }
        .title h2 { font-size: 13pt; text-decoration: underline; margin: 0; text-transform: uppercase; color: #0f172a; }
        .title p { font-size: 9pt; color: #475569; margin-top: 4px; }
        .table-info { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table-info th, .table-info td { border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 10pt; text-align: left; }
        .table-info th { background-color: #fff1f2; color: #be123c; }
        .signatures { margin-top: 40px; width: 100%; border-collapse: collapse; }
        .signatures td { width: 33%; text-align: center; vertical-align: top; font-size: 9.5pt; padding: 5px; }
        .sign-space { height: 65px; }
        .footer-note { font-size: 8pt; color: #94a3b8; margin-top: 30px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 9pt; background: #ffe4e6; color: #9f1239; }
    </style>
</head>
<body>

    <div class="header">
        <h1>YAYASAN NAMIRA KRAKSAAN</h1>
        <p>Sistem Informasi Manajemen Sarana & Prasarana (SuperApp Namira)</p>
    </div>

    <div class="title">
        <h2>BERITA ACARA PENGHAPUSAN / AFKIR ASET INVENTARIS</h2>
        <p>Nomor Registrasi Penghapusan: BA-DISPOSAL/{{ $disposal->id }}/{{ date('Y') }}</p>
    </div>

    <p>Pada hari ini, tanggal <strong>{{ $dateFormatted }}</strong>, bertempat di <strong>{{ $disposal->unit->name ?? 'Yayasan Namira' }}</strong>, telah disetujui penghapusan / pengapkitan aset inventaris dari daftar buku inventaris aktif dengan pertimbangan teknis sebagai berikut:</p>

    <table class="table-info">
        <tr>
            <th width="30%">Nama Barang</th>
            <td><strong>{{ $disposal->inventory->name }}</strong> @if($disposal->inventory->brand) ({{ $disposal->inventory->brand }}) @endif</td>
        </tr>
        <tr>
            <th>Kode Inventaris</th>
            <td><code>{{ $disposal->inventory->code }}</code></td>
        </tr>
        <tr>
            <th>Jenis Kategori</th>
            <td>{{ $disposal->inventory->category->name ?? 'Aset' }}</td>
        </tr>
        <tr>
            <th>Sumber Dana & Tahun</th>
            <td>{{ $disposal->inventory->funding_source == 'BOS' ? 'Dana BOS' : 'Dana Yayasan' }} (Perolehan: {{ $disposal->inventory->year_acquired }})</td>
        </tr>
        <tr>
            <th>Jenis Penghapusan</th>
            <td><span class="badge">{{ strtoupper(str_replace('_', ' ', $disposal->disposal_type)) }}</span></td>
        </tr>
        <tr>
            <th>Alasan & Pertimbangan</th>
            <td>{{ $disposal->reason }}</td>
        </tr>
    </table>

    <p>Dengan diterbitkannya Berita Acara ini, barang tersebut secara resmi dikeluarkan dari daftar aset aktif {{ $disposal->unit->name ?? 'Yayasan Namira' }} dan tidak lagi dihitung dalam total laporan inventaris.</p>

    <table class="signatures">
        <tr>
            <td>
                Koordinator Sarpras<br><br>
                <div class="sign-space"></div>
                <strong>({{ $disposal->requestedBy->name ?? 'Koordinator Sarpras' }})</strong>
            </td>
            <td>
                Kepala Sekolah Unit<br><br>
                <div class="sign-space"></div>
                <strong>(...................................)</strong>
            </td>
            <td>
                Pengurus Yayasan<br><br>
                <div class="sign-space"></div>
                <strong>({{ $disposal->approvedBy->name ?? 'Yayasan Namira' }})</strong>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen resmi diterbitkan secara elektronik oleh SuperApp Namira pada {{ date('d/m/Y H:i:s') }}. Validitas data terverifikasi pada database sistem.
    </div>

</body>
</html>

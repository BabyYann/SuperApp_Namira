<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Tagihan - {{ $student->full_name }}</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 14px; }
        .recipient { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #f4f4f4; font-weight: bold; }
        .total-row td { background-color: #f9f9f9; font-weight: bold; font-size: 16px; }
        .footer { text-align: right; margin-top: 50px; }
        .signature { margin-top: 80px; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h1>YAYASAN NAMIRA</h1>
        <p>Jl. Contoh No. 123, Kota Surabaya, Jawa Timur</p>
        <p>Telp: (031) 1234567 | Email: keu@namiraschool.sch.id</p>
    </div>

    <div class="recipient">
        <p>
            Nomor: <strong>KEU/{{ date('m/Y') }}/{{ $student->nis }}</strong><br>
            Hal: <strong>Pemberitahuan Tunggakan Administrasi</strong><br>
            Tanggal: {{ $date }}
        </p>
        <br>
        <p>Yth. Bapak/Ibu Orang Tua/Wali dari:</p>
        <table>
            <tr><td style="width: 120px;">Nama Siswa</td><td>: <strong>{{ $student->full_name }}</strong></td></tr>
            <tr><td>NIS</td><td>: {{ $student->nis }}</td></tr>
            <tr><td>Kelas</td><td>: {{ $student->classroom->name ?? '-' }}</td></tr>
        </table>
    </div>

    <p>Dengan hormat,</p>
    <p>Bersama surat ini, kami memberitahukan bahwa berdasarkan catatan bagian keuangan kami per tanggal {{ $date }}, masih terdapat kewajiban administrasi yang belum terselesaikan dengan rincian sebagai berikut:</p>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Jenis Tagihan</th>
                <th>Bulan/Periode</th>
                <th style="text-align: right;">Jumlah Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bills as $index => $bill)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $bill->financeType->name ?? $bill->description }}</td>
                <td>{{ $bill->billing_date->translatedFormat('F Y') }}</td>
                <td style="text-align: right;">Rp {{ number_format($bill->final_amount - $bill->paid_amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL TUNGGAKAN</td>
                <td style="text-align: right;">Rp {{ number_format($total_arrears, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p>Kami mohon agar kewajiban tersebut dapat segera diselesaikan melalui transfer ke:</p>
    <div style="background: #f9f9f9; padding: 15px; border: 1px dashed #999; display: inline-block;">
        <strong>Bank Jatim</strong><br>
        Nomor VA Siswa: <strong>{{ $student->va_number ?? 'Hubungi TU' }}</strong>
    </div>

    <p>Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>

    <div class="footer">
        <p>Surabaya, {{ $date }}<br>Bagian Keuangan,</p>
        <div class="signature">Bendahara Yayasan</div>
    </div>
</body>
</html>

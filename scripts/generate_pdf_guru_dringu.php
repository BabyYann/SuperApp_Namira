<?php

require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$tkTeachers = [
    ['no' => 1, 'nama' => 'Surya Dini Yusonia', 'unit' => 'TK Namira Dringu', 'email' => 'surya@namira.school', 'pass' => 'guru123'],
    ['no' => 2, 'nama' => 'Selawati Masruroh', 'unit' => 'TK Namira Dringu', 'email' => 'selawati@namira.school', 'pass' => 'guru123'],
    ['no' => 3, 'nama' => 'Helyas Vintan Agesti', 'unit' => 'TK Namira Dringu', 'email' => 'helyas@namira.school', 'pass' => 'guru123'],
    ['no' => 4, 'nama' => 'Islavia Feria Devi', 'unit' => 'TK Namira Dringu', 'email' => 'islavia@namira.school', 'pass' => 'guru123'],
    ['no' => 5, 'nama' => 'Ruli Puji Lestari', 'unit' => 'TK Namira Dringu', 'email' => 'ruli@namira.school', 'pass' => 'guru123'],
    ['no' => 6, 'nama' => 'Salfiya', 'unit' => 'TK Namira Dringu', 'email' => 'salfiya@namira.school', 'pass' => 'guru123'],
    ['no' => 7, 'nama' => 'Nabila Cipta Navira Savitri', 'unit' => 'TK Namira Dringu', 'email' => 'nabila@namira.school', 'pass' => 'guru123'],
    ['no' => 8, 'nama' => 'Dwi Ayu Rosidah', 'unit' => 'TK Namira Dringu', 'email' => 'dwi.rosidah@namira.school', 'pass' => 'guru123'],
    ['no' => 9, 'nama' => 'Deswinta Febrianti', 'unit' => 'TK Namira Dringu', 'email' => 'deswinta@namira.school', 'pass' => 'guru123'],
    ['no' => 10, 'nama' => 'Riska Ariyani Pratiwi', 'unit' => 'TK Namira Dringu', 'email' => 'riska@namira.school', 'pass' => 'guru123'],
];

$kbTeachers = [
    ['no' => 1, 'nama' => 'Dwi Maulianita', 'unit' => 'KB Namira Dringu', 'email' => 'dwi@namira.school', 'pass' => 'guru123'],
    ['no' => 2, 'nama' => 'Fatimatus Zahro', 'unit' => 'KB Namira Dringu', 'email' => 'fatimatus@namira.school', 'pass' => 'guru123'],
    ['no' => 3, 'nama' => 'Agustyana Dyah Pitaloka', 'unit' => 'KB Namira Dringu', 'email' => 'agustyana@namira.school', 'pass' => 'guru123'],
    ['no' => 4, 'nama' => 'Ine Meilina Putri', 'unit' => 'KB Namira Dringu', 'email' => 'ine@namira.school', 'pass' => 'guru123'],
    ['no' => 5, 'nama' => 'Rensia Yuliati Pratama', 'unit' => 'KB Namira Dringu', 'email' => 'rensia@namira.school', 'pass' => 'guru123'],
];

$tpaTeachers = [
    ['no' => 1, 'nama' => 'Nabilla Ilamalia', 'unit' => 'Daycare / TPA Dringu', 'email' => 'nabilla.ilamalia@namira.school', 'pass' => 'guru123'],
    ['no' => 2, 'nama' => 'Silvia Anggrayni', 'unit' => 'Daycare / TPA Dringu', 'email' => 'silvia@namira.school', 'pass' => 'guru123'],
];

$allCards = array_merge($tkTeachers, $kbTeachers, $tpaTeachers);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Akun Login Guru TK KB Namira Dringu</title>
    <style>
        @page {
            margin: 12mm 15mm 12mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 15px;
            color: #064e3b;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 2px 0 0 0;
            font-size: 13px;
            color: #334155;
            font-weight: 700;
        }
        .header p {
            margin: 3px 0 0 0;
            font-size: 9.5px;
            color: #64748b;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 13px;
            color: #0f172a;
            font-weight: 800;
            text-decoration: underline;
        }
        .doc-title p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #475569;
        }
        .info-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 10px;
        }
        .info-box strong {
            color: #14532d;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #064e3b;
            background-color: #f1f5f9;
            padding: 4px 8px;
            border-left: 3px solid #064e3b;
            margin: 10px 0 6px 0;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
        }
        th {
            background-color: #064e3b;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: "Courier New", Courier, monospace;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            background-color: #e2e8f0;
            color: #334155;
        }
        .page-break {
            page-break-before: always;
        }
        
        /* Kartu Slip Potong Per Guru */
        .cards-container {
            width: 100%;
        }
        .slip-card {
            border: 1.5px dashed #064e3b;
            border-radius: 6px;
            padding: 9px 12px;
            margin-bottom: 10px;
            background-color: #ffffff;
        }
        .slip-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .slip-header .inst-title {
            font-size: 10px;
            font-weight: 800;
            color: #064e3b;
            text-transform: uppercase;
        }
        .slip-header .slip-title {
            font-size: 9px;
            color: #64748b;
            float: right;
            font-weight: bold;
        }
        .slip-body table {
            margin: 0;
            border: none;
        }
        .slip-body td {
            border: none;
            padding: 2px 4px;
            font-size: 10px;
        }
        .slip-footer {
            margin-top: 5px;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px dotted #e2e8f0;
            padding-top: 3px;
        }
    </style>
</head>
<body>

    <!-- ================= HALAMAN 1: TABEL REKAPITULASI RESMI ================= -->
    <div class="header">
        <h1>YAYASAN PENDIDIKAN NAMIRA KOTA PROBOLINGGO</h1>
        <h2>UNIT TK & KB NAMIRA DRINGU</h2>
        <p>Alamat: Kec. Dringu, Kab. Probolinggo - Jawa Timur | Portal Resmi: namiraschool.com</p>
    </div>

    <div class="doc-title">
        <h3>REKAPITULASI DATA AKUN & AKSES LOGIN GURU</h3>
        <p>Tahun Ajaran 2026/2027 &bull; Sistem Informasi SuperApp Namira</p>
    </div>

    <div class="info-box">
        <strong>PANDUAN LOGIN RESMI:</strong><br>
        1. Buka browser (Chrome/Edge/Safari) dan akses: <span class="font-mono">https://namiraschool.com/login</span><br>
        2. Masukkan <strong>Email Resmi</strong> dan <strong>Kata Sandi Standar</strong> yang tertera pada tabel di bawah.<br>
        3. Setelah berhasil masuk ke Dashboard, guru dapat memperbarui kata sandi mandiri pada menu Profil Pengguna.
    </div>

    <!-- Bagian 1: TK Namira Dringu -->
    <div class="section-title">1. Daftar Akun Guru TK Namira Dringu (10 Tenaga Pendidik)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 32%;">Nama Lengkap Guru</th>
                <th style="width: 20%;">Unit Sekolah</th>
                <th style="width: 28%;">Email / Akses Login</th>
                <th style="width: 15%;" class="text-center">Kata Sandi Default</th>
            </tr>
        </thead>
        <tbody>';

foreach ($tkTeachers as $t) {
    $html .= '
            <tr>
                <td class="text-center font-mono">' . $t['no'] . '</td>
                <td><strong>' . htmlspecialchars($t['nama']) . '</strong></td>
                <td>' . htmlspecialchars($t['unit']) . '</td>
                <td class="font-mono" style="color: #064e3b;">' . htmlspecialchars($t['email']) . '</td>
                <td class="text-center font-mono" style="background-color: #fefce8; color: #854d0e;">' . htmlspecialchars($t['pass']) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <!-- Bagian 2: KB Namira Dringu -->
    <div class="section-title">2. Daftar Akun Guru KB Namira Dringu (5 Tenaga Pendidik)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 32%;">Nama Lengkap Guru</th>
                <th style="width: 20%;">Unit Sekolah</th>
                <th style="width: 28%;">Email / Akses Login</th>
                <th style="width: 15%;" class="text-center">Kata Sandi Default</th>
            </tr>
        </thead>
        <tbody>';

foreach ($kbTeachers as $k) {
    $html .= '
            <tr>
                <td class="text-center font-mono">' . $k['no'] . '</td>
                <td><strong>' . htmlspecialchars($k['nama']) . '</strong></td>
                <td>' . htmlspecialchars($k['unit']) . '</td>
                <td class="font-mono" style="color: #064e3b;">' . htmlspecialchars($k['email']) . '</td>
                <td class="text-center font-mono" style="background-color: #fefce8; color: #854d0e;">' . htmlspecialchars($k['pass']) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <!-- Bagian 3: Daycare / TPA Dringu -->
    <div class="section-title">3. Daftar Akun Staf & Pendidik TPA / Daycare Dringu (2 Tenaga Pendidik)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 32%;">Nama Lengkap Guru</th>
                <th style="width: 20%;">Unit Sekolah</th>
                <th style="width: 28%;">Email / Akses Login</th>
                <th style="width: 15%;" class="text-center">Kata Sandi Default</th>
            </tr>
        </thead>
        <tbody>';

foreach ($tpaTeachers as $d) {
    $html .= '
            <tr>
                <td class="text-center font-mono">' . $d['no'] . '</td>
                <td><strong>' . htmlspecialchars($d['nama']) . '</strong></td>
                <td>' . htmlspecialchars($d['unit']) . '</td>
                <td class="font-mono" style="color: #064e3b;">' . htmlspecialchars($d['email']) . '</td>
                <td class="text-center font-mono" style="background-color: #fefce8; color: #854d0e;">' . htmlspecialchars($d['pass']) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <table style="border: none; margin-top: 15px;">
        <tr style="border: none; background: transparent;">
            <td style="border: none; width: 60%;"></td>
            <td style="border: none; width: 40%; text-align: center; font-size: 10px;">
                Probolinggo, ' . date('d F Y') . '<br>
                <strong>Yayasan Pendidikan Namira</strong><br>
                Administrator Sistem & IT<br><br><br><br>
                <u><strong>Tim Pengelola Data</strong></u>
            </td>
        </tr>
    </table>

    <!-- ================= HALAMAN 2: SLIP KARTU DISTRIBUSI (POTONGAN) ================= -->
    <div class="page-break"></div>

    <div class="header">
        <h1>SLIP KARTU AKSES LOGIN GURU (SIAP POTONG)</h1>
        <p>Gunting sesuai garis putus-putus dan bagikan kepada masing-masing guru yang bersangkutan.</p>
    </div>';

// Render slips in pairs or groups
foreach ($allCards as $index => $c) {
    $html .= '
    <div class="slip-card">
        <div class="slip-header">
            <span class="slip-title">KARTU AKSES RESMI</span>
            <span class="inst-title">YAYASAN NAMIRA &bull; ' . htmlspecialchars($c['unit']) . '</span>
        </div>
        <div class="slip-body">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 28%; font-weight: bold; color: #475569;">Nama Guru</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 69%; font-size: 11px; font-weight: bold; color: #064e3b;">' . htmlspecialchars($c['nama']) . '</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #475569;">Alamat Portal</td>
                    <td>:</td>
                    <td class="font-mono" style="color: #2563eb;">https://namiraschool.com/login</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #475569;">Email Akun</td>
                    <td>:</td>
                    <td class="font-mono"><strong>' . htmlspecialchars($c['email']) . '</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #475569;">Kata Sandi Awal</td>
                    <td>:</td>
                    <td class="font-mono" style="color: #b45309; font-size: 11px; font-weight: bold;">' . htmlspecialchars($c['pass']) . '</td>
                </tr>
            </table>
        </div>
        <div class="slip-footer">
            &bull; Harap segera perbarui kata sandi Anda setelah berhasil login pertama kali di menu profil.
        </div>
    </div>';
}

$html .= '
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();

$filePath = __DIR__ . '/public/downloads/DATA_AKUN_LOGIN_GURU_TK_KB_NAMIRA_DRINGU.pdf';
file_put_contents($filePath, $output);

echo "PDF Berhasil Dibuat di: " . $filePath . "\n";
echo "Ukuran File: " . round(strlen($output) / 1024, 2) . " KB\n";

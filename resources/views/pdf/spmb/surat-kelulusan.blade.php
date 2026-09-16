<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keputusan Penerimaan SPMB - {{ $applicant->registration_number }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000000;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000000;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 16pt;
            font-weight: bold;
            margin: 2px 0 0 0;
            color: #064e3b;
        }
        .header p {
            font-size: 9pt;
            margin: 2px 0 0 0;
            font-style: italic;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-title h3 {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            text-transform: uppercase;
        }
        .doc-title .num {
            font-size: 10pt;
            margin-top: 2px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        .content-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .content-table td.lbl {
            width: 28%;
        }
        .content-table td.col {
            width: 3%;
        }
        .content-table td.val {
            width: 69%;
            font-weight: bold;
        }
        .status-box {
            border: 2px solid #064e3b;
            background-color: #f0fdf4;
            text-align: center;
            padding: 10px;
            margin: 15px 0;
            border-radius: 6px;
        }
        .status-box .text {
            font-size: 13pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .payment-info {
            background-color: #fafafa;
            border: 1px solid #e5e5e5;
            padding: 10px 14px;
            margin: 12px 0;
            font-size: 10pt;
        }
        .payment-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-info td {
            padding: 2px 0;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .sign-space {
            height: 60px;
        }
    </style>
</head>
<body>

    <!-- Kop Surat -->
    <div class="header">
        <h1>YAYASAN NAMIRA KOTA PROBOLINGGO</h1>
        <h2>SD ISLAM TERPADU NAMIRA</h2>
        <p>Jl. Mahakam No. 1, Kec. Kedopok, Kota Probolinggo - Jawa Timur | Email: sd@namiraschool.com | Telp/WA: {{ $setting->contact_whatsapp ?? '082332922521' }}</p>
    </div>

    <!-- Judul Dokumen -->
    <div class="doc-title">
        <h3>SURAT KETERANGAN PENERIMAAN SISWA BARU</h3>
        <div class="num">Nomor: {{ $applicant->registration_number }}/SPMB-SD/{{ date('Y') }}</div>
    </div>

    <p>Berdasarkan hasil seleksi administrasi, observasi perkembangan dasar, serta psikotes calon peserta didik baru Jalur Inden SD Namira Tahun Ajaran {{ $setting->academic_year ?? '2026/2027' }}, Panitia Penerimaan Peserta Didik Baru dengan ini menerangkan bahwa:</p>

    <table class="content-table">
        <tr>
            <td class="lbl">Nomor Registrasi</td>
            <td class="col">:</td>
            <td class="val">{{ $applicant->registration_number }}</td>
        </tr>
        <tr>
            <td class="lbl">Nama Calon Siswa</td>
            <td class="col">:</td>
            <td class="val">{{ strtoupper($applicant->full_name) }}</td>
        </tr>
        <tr>
            <td class="lbl">Jenis Kelamin</td>
            <td class="col">:</td>
            <td class="val">{{ $applicant->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
        </tr>
        <tr>
            <td class="lbl">Tempat, Tanggal Lahir</td>
            <td class="col">:</td>
            <td class="val">{{ $applicant->birth_place }}, {{ $applicant->birth_date ? $applicant->birth_date->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Asal TK / Sekolah</td>
            <td class="col">:</td>
            <td class="val">{{ $applicant->previous_school ?: '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Nama Orang Tua / Wali</td>
            <td class="col">:</td>
            <td class="val">{{ $applicant->father_name }} / {{ $applicant->mother_name }}</td>
        </tr>
    </table>

    <p>Dinyatakan:</p>

    <div class="status-box">
        <div class="text">★ DITERIMA SEBAGAI PESERTA DIDIK BARU ★</div>
        <div style="font-size: 10pt; color: #166534; margin-top: 2px;">SD NAMIRA TAHUN AJARAN {{ $setting->academic_year ?? '2026/2027' }}</div>
    </div>

    <p>Kepada Orang Tua / Wali murid diharapkan untuk segera menyelesaikan proses <strong>Daftar Ulang</strong> dengan ketentuan sebagai berikut:</p>

    <div class="payment-info">
        <table>
            @if($applicant->virtual_account_number)
            <tr>
                <td style="width: 35%;">Nomor Virtual Account (VA)</td>
                <td style="width: 3%;">:</td>
                <td style="font-weight: bold; font-size: 11pt; color: #064e3b;">{{ $applicant->virtual_account_number }} (Bank Jatim)</td>
            </tr>
            @endif
            @if($applicant->total_admission_fee)
            <tr>
                <td>Total Biaya Masuk / Uang Pangkal</td>
                <td>:</td>
                <td style="font-weight: bold;">Rp {{ number_format($applicant->total_admission_fee, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($applicant->min_down_payment)
            <tr>
                <td>Pembayaran Termin 1 (Min 60%)</td>
                <td>:</td>
                <td style="font-weight: bold; color: #b45309;">Rp {{ number_format($applicant->min_down_payment, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($applicant->down_payment_deadline)
            <tr>
                <td>Batas Waktu Termin 1</td>
                <td>:</td>
                <td>{{ $applicant->down_payment_deadline->translatedFormat('d F Y') }}</td>
            </tr>
            @endif
            @if($applicant->full_payment_deadline)
            <tr>
                <td>Batas Pelunasan 100%</td>
                <td>:</td>
                <td>{{ $applicant->full_payment_deadline->translatedFormat('d F Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <p>Demikian surat keputusan penerimaan ini disampaikan. Atas kepercayaan Ayah/Bunda menitipkan pendidikan putra-putri di SD Namira, kami ucapkan terima kasih.</p>

    <table class="signature-table">
        <tr>
            <td></td>
            <td>
                Probolinggo, {{ date('d F Y') }}<br>
                Kepala Sekolah SD Namira,
                <div class="sign-space"></div>
                <strong><u>Astutik, S.Pd.I</u></strong><br>
                NIY. 3180201380
            </td>
        </tr>
    </table>

</body>
</html>

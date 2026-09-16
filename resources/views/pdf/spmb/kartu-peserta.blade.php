<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Tanda Peserta SPMB - {{ $applicant->registration_number }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .card-container {
            border: 2px solid #064e3b;
            border-radius: 8px;
            padding: 16px;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #064e3b;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: bold;
            color: #064e3b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            font-size: 12px;
            font-weight: bold;
            color: #047857;
            margin: 2px 0 0 0;
        }
        .header p {
            font-size: 8.5px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .title-badge {
            background-color: #064e3b;
            color: #ffffff;
            text-align: center;
            padding: 5px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-bottom: 12px;
        }
        .reg-number-box {
            background-color: #f0fdf4;
            border: 1px dashed #16a34a;
            border-radius: 6px;
            padding: 6px;
            text-align: center;
            margin-bottom: 12px;
        }
        .reg-number-box .label {
            font-size: 8.5px;
            color: #166534;
            text-transform: uppercase;
            font-weight: bold;
        }
        .reg-number-box .val {
            font-size: 16px;
            font-weight: bold;
            color: #064e3b;
            letter-spacing: 1.5px;
        }
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .bio-table td {
            padding: 3px 4px;
            vertical-align: top;
        }
        .bio-table td.label {
            width: 25%;
            color: #475569;
            font-weight: 500;
        }
        .bio-table td.colon {
            width: 3%;
            text-align: center;
        }
        .bio-table td.value {
            width: 47%;
            color: #0f172a;
            font-weight: bold;
        }
        .photo-box {
            width: 25%;
            text-align: center;
            vertical-align: top;
        }
        .photo-frame {
            width: 95px;
            height: 125px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            display: inline-block;
            text-align: center;
            line-height: 125px;
            color: #94a3b8;
            font-size: 9px;
            overflow: hidden;
            border-radius: 4px;
        }
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .schedule-container {
            width: 100%;
            margin-bottom: 12px;
        }
        .schedule-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #064e3b;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 8px;
        }
        .schedule-box h3 {
            margin: 0 0 4px 0;
            font-size: 10px;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
        }
        .schedule-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .schedule-box td {
            font-size: 9px;
            padding: 1px 0;
        }
        .notice-box {
            background-color: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 8px;
            color: #854d0e;
            margin-bottom: 12px;
        }
        .notice-box ul {
            margin: 2px 0 0 0;
            padding-left: 14px;
        }
        .signature-table {
            width: 100%;
            margin-top: 10px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            font-size: 9px;
            vertical-align: bottom;
        }
        .sign-line {
            display: inline-block;
            width: 140px;
            border-bottom: 1px solid #1e293b;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<div class="card-container">
    <!-- Header -->
    <div class="header">
        <h1>YAYASAN NAMIRA PROBOLINGGO</h1>
        <h2>SD NAMIRA (ELEMENTARY SCHOOL)</h2>
        <p>Jl. Mahakam No. 1, Kota Probolinggo - Jawa Timur | Telp/WA: {{ $setting->contact_whatsapp ?? '082332922521' }}</p>
    </div>

    <!-- Title Badge -->
    <div class="title-badge">
        Kartu Tanda Peserta SPMB - Jalur Inden (TA {{ $setting->academic_year ?? '2026/2027' }})
    </div>

    <!-- Nomor Registrasi -->
    <div class="reg-number-box">
        <div class="label">Nomor Registrasi Calon Siswa</div>
        <div class="val">{{ $applicant->registration_number }}</div>
    </div>

    <!-- Biodata & Photo -->
    <table class="bio-table">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="colon">:</td>
            <td class="value">{{ strtoupper($applicant->full_name) }}</td>
            <td rowspan="8" class="photo-box">
                <div class="photo-frame">
                    @if($applicant->photo_path && file_exists(storage_path('app/public/' . $applicant->photo_path)))
                        <img src="{{ storage_path('app/public/' . $applicant->photo_path) }}" alt="Foto">
                    @else
                        Pas Foto 3x4<br>(Latar Merah)
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td class="label">Nama Panggilan</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->nickname ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat, Tgl Lahir</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->birth_place }}, {{ $applicant->birth_date ? $applicant->birth_date->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Asal TK / Sekolah</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->previous_school ?: '-' }} ({{ $applicant->category === 'internal_tk' ? 'Alumni TK Namira' : 'Umum' }})</td>
        </tr>
        <tr>
            <td class="label">Nama Ayah / Ibu</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->father_name }} / {{ $applicant->mother_name }}</td>
        </tr>
        <tr>
            <td class="label">Nomor WhatsApp Ortu</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->parent_phone }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Domisili</td>
            <td class="colon">:</td>
            <td class="value">{{ $applicant->address }}</td>
        </tr>
    </table>

    <!-- Jadwal Offline -->
    <div class="schedule-container">
        <!-- 1. Jadwal Observasi Dasar -->
        <div class="schedule-box">
            <h3>1. Agenda Observasi Dasar Anak</h3>
            <table>
                <tr>
                    <td style="width: 25%; font-weight: 500;">Hari / Tanggal</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 72%; font-weight: bold;">
                        {{ $applicant->observation_date ? $applicant->observation_date->translatedFormat('l, d F Y') : 'Menunggu Penetapan Panitia' }}
                    </td>
                </tr>
                <tr>
                    <td>Waktu Pelaksanaan</td>
                    <td>:</td>
                    <td>{{ $applicant->observation_time ?: 'Akan diinfokan via WhatsApp' }}</td>
                </tr>
                <tr>
                    <td>Tempat / Ruangan</td>
                    <td>:</td>
                    <td>{{ $applicant->observation_location ?: 'Gedung SD Namira' }}</td>
                </tr>
            </table>
        </div>

        <!-- 2. Jadwal Psikotes -->
        <div class="schedule-box" style="border-left-color: #d97706;">
            <h3 style="color: #b45309;">2. Agenda Psikotes Calon Siswa</h3>
            <table>
                <tr>
                    <td style="width: 25%; font-weight: 500;">Hari / Tanggal</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 72%; font-weight: bold;">
                        {{ $applicant->psychotest_date ? $applicant->psychotest_date->translatedFormat('l, d F Y') : 'Menunggu Penetapan Panitia' }}
                    </td>
                </tr>
                <tr>
                    <td>Waktu Pelaksanaan</td>
                    <td>:</td>
                    <td>{{ $applicant->psychotest_time ?: 'Akan diinfokan via WhatsApp' }}</td>
                </tr>
                <tr>
                    <td>Tempat / Ruangan</td>
                    <td>:</td>
                    <td>{{ $applicant->psychotest_location ?: 'Gedung SD Namira' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tata Tertib & Catatan -->
    <div class="notice-box">
        <strong>Ketentuan & Tata Tertib Pelaksanaan:</strong>
        <ul>
            <li>Wajib membawa dan mencetak Kartu Tanda Peserta ini saat hadir ke sekolah.</li>
            <li>Calon siswa mengenakan pakaian bebas, rapi, menutup aurat, dan bersepatu.</li>
            <li>Harap hadir di lokasi sekolah 15 menit sebelum kegiatan dimulai.</li>
            <li>Jika berhalangan hadir pada jadwal yang tertera, segera hubungi Panitia SPMB di {{ $setting->contact_whatsapp ?? '082332922521' }}.</li>
        </ul>
    </div>

    <!-- Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td>
                Orang Tua / Wali Murid,
                <br><br>
                <span class="sign-line"></span>
                <br>
                ( {{ $applicant->father_name ?: $applicant->mother_name }} )
            </td>
            <td>
                Probolinggo, {{ date('d F Y') }}<br>
                Panitia SPMB SD Namira,
                <br><br>
                <span class="sign-line"></span>
                <br>
                ( Panitia Penerimaan Siswa Baru )
            </td>
        </tr>
    </table>
</div>

</body>
</html>

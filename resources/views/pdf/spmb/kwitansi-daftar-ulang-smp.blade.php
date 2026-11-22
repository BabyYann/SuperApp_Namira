<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran SPMB - {{ $applicant->registration_number }}</title>
    <style>
        @page {
            margin: 1.2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #111827;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .receipt-box {
            position: relative;
            border: 1.8px solid #111827;
            padding: 16px 20px 18px 20px;
            background-color: #ffffff;
        }
        .watermark {
            position: absolute;
            top: 192px;
            left: 50%;
            margin-left: -108px;
            width: 216px;
            opacity: 0.055;
            z-index: 0;
        }
        .content-layer {
            position: relative;
            z-index: 1;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.4px solid #111827;
            margin-bottom: 8px;
        }
        .kop-table td {
            padding-bottom: 8px;
            vertical-align: middle;
        }
        .school-name {
            font-size: 13.5pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
            margin: 0;
        }
        .school-addr {
            font-size: 8.2pt;
            color: #1f2937;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.4px solid #111827;
            margin-bottom: 8px;
        }
        .meta-table td {
            padding: 2.5px 2px;
            font-size: 8.8pt;
            vertical-align: top;
        }
        .meta-table tr.last-row td {
            padding-bottom: 7px;
        }
        .meta-lbl {
            width: 19%;
            text-transform: uppercase;
            color: #111827;
        }
        .meta-col {
            width: 2%;
        }
        .meta-val {
            width: 39%;
        }
        .meta-lbl-r {
            width: 22%;
            text-transform: uppercase;
            color: #111827;
        }
        .meta-val-r {
            width: 16%;
        }
        .title-center {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin: 4px 0 6px 0;
        }
        .intro-line {
            font-size: 8.8pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .items-table th {
            background-color: #d1d5db;
            border-top: 1.3px solid #111827;
            border-bottom: 1.3px solid #111827;
            padding: 5px 6px;
            font-size: 9pt;
            font-weight: bold;
            color: #111827;
        }
        .items-table td {
            padding: 4.5px 6px;
            font-size: 8.8pt;
            border-bottom: 0.75px solid #374151;
            vertical-align: middle;
        }
        .col-no {
            width: 7%;
            text-align: center;
        }
        .col-jenis {
            width: 31%;
            text-align: left;
            text-transform: uppercase;
        }
        .col-ket {
            width: 31%;
            text-align: left;
            text-transform: uppercase;
        }
        .col-rp {
            width: 6%;
            text-align: left;
            font-style: italic;
        }
        .col-amt {
            width: 25%;
            text-align: right;
            font-style: italic;
        }
        .summary-shaded td {
            background-color: #d1d5db;
            border-bottom: 0.75px solid #111827;
            font-weight: bold;
            padding: 5px 6px;
        }
        .summary-last td {
            border-bottom: 1.4px solid #111827;
        }
        .terbilang-cell {
            font-style: italic;
            font-weight: bold;
            font-size: 8.6pt;
            line-height: 1.3;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .footer-table td {
            vertical-align: bottom;
        }
        .sign-block {
            width: 46%;
            padding-left: 10px;
            font-size: 8.8pt;
        }
        .sign-space {
            height: 56px;
            vertical-align: middle;
            padding: 2px 0;
        }
        .note-block {
            width: 54%;
            text-align: left;
            padding-left: 24px;
            padding-bottom: 2px;
        }
        .note-table {
            width: 100%;
            border-collapse: collapse;
        }
        .note-table td {
            font-size: 7.5pt;
            vertical-align: top;
            line-height: 1.4;
        }
    </style>
</head>
<body>

@php
    $verifiedAtLocal = ($verifiedAt ?? now())->copy()->timezone('Asia/Jakarta');
    $logoFile = public_path('images/landing/logo-smp.png');
    $logoBase64 = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;

    $sigFile = public_path('images/spmb/ttd-bendahara-smp.png');
    $sigBase64 = file_exists($sigFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($sigFile)) : null;

    $schoolAddress = $applicant->unit?->address ?: 'Jl. Basuki Rahmad No 44A, Mangunharjo';
    $schoolPhone = $applicant->unit?->phone ?: '089516963886';
    $paidAmount = (float) ($applicant->paid_admission_amount ?? 0);
    $treasurer = $treasurerName ?? 'Nadia Pramadianti, S.M.';
@endphp

<div class="receipt-box">
    @if($logoBase64)
        <img src="{{ $logoBase64 }}" class="watermark" alt="">
    @endif

    <div class="content-layer">
        <!-- Kop Sekolah SMP Namira -->
        <table class="kop-table">
            <tr>
                @if($logoBase64)
                <td style="width: 56px;">
                    <img src="{{ $logoBase64 }}" style="width: 46px; height: 46px; object-fit: contain;" alt="Logo SMP Namira">
                </td>
                @endif
                <td style="padding-left: 8px;">
                    <div class="school-name">SMP NAMIRA KOTA PROBOLINGGO</div>
                    <div class="school-addr">{{ $schoolAddress }} Telp. {{ $schoolPhone }}</div>
                </td>
            </tr>
        </table>

        <!-- Metadata Pembayaran -->
        <table class="meta-table">
            <tr>
                <td class="meta-lbl">NOMOR KWITANSI</td>
                <td class="meta-col">:</td>
                <td class="meta-val">
                    {{ $applicant->id ?: '-' }}
                    &nbsp;&nbsp;&nbsp;
                    <em>{{ $verifiedAtLocal->format('d-M-Y') }}</em>
                </td>
                <td class="meta-lbl-r">TANGGAL PEMBAYARAN</td>
                <td class="meta-col">:</td>
                <td class="meta-val-r">{{ $verifiedAtLocal->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="meta-lbl">NAMA SISWA</td>
                <td class="meta-col">:</td>
                <td class="meta-val"><strong>{{ strtoupper($applicant->full_name) }}</strong></td>
                <td class="meta-lbl-r">JAM</td>
                <td class="meta-col">:</td>
                <td class="meta-val-r">{{ $verifiedAtLocal->format('h:i:s A') }}</td>
            </tr>
            <tr class="last-row">
                <td class="meta-lbl">ALAMAT/KELAS</td>
                <td class="meta-col">:</td>
                <td colspan="4">SPMB {{ $shortAcademicYear ?? '27/28' }} &bull; {{ $applicant->registration_number }}</td>
            </tr>
        </table>

        <!-- Judul Kwitansi -->
        <div class="title-center">KWITANSI PEMBAYARAN</div>
        <div class="intro-line">Dengan rincian pembayaran sebagai berikut:</div>

        <!-- Tabel Rincian 7 Baris (TA 2027/2028) -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th colspan="2" style="text-align: center;">Jenis Pembayaran</th>
                    <th colspan="2" style="text-align: center;">Jumlah Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receiptItems as $idx => $item)
                @php
                    $amt = (float) ($item['amount'] ?? 0);
                @endphp
                <tr>
                    <td class="col-no">{{ $idx + 1 }}</td>
                    <td class="col-jenis">{{ $item['label'] }}</td>
                    <td class="col-ket">{{ $item['sublabel'] ?: '-' }}</td>
                    <td class="col-rp">Rp</td>
                    <td class="col-amt">{{ $amt > 0 ? number_format($amt, 0, ',', '.') : '-' }}</td>
                </tr>
                @endforeach

                <tr class="summary-shaded">
                    <td colspan="2" style="background: #ffffff; border-bottom: none;"></td>
                    <td style="text-align: left;">Total Pembayaran</td>
                    <td class="col-rp">Rp</td>
                    <td class="col-amt">{{ $paidAmount > 0 ? number_format($paidAmount, 0, ',', '.') : '0' }}</td>
                </tr>
                <tr class="summary-shaded summary-last">
                    <td colspan="2" style="background: #ffffff; border-bottom: none;"></td>
                    <td style="text-align: left; vertical-align: top;">Terbilang</td>
                    <td colspan="2" class="terbilang-cell">{{ $paidTerbilang }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Bendahara & Catatan -->
        <table class="footer-table">
            <tr>
                <td class="sign-block">
                    <div>Probolinggo,&nbsp;&nbsp;&nbsp;{{ $verifiedAtLocal->translatedFormat('d F Y') }}</div>
                    <table style="width: 175px; border-collapse: collapse; margin-top: 2px;">
                        <tr>
                            <td style="text-align: center;">Bendahara</td>
                        </tr>
                        <tr>
                            <td class="sign-space" style="text-align: center;">
                                @if($sigBase64)
                                    <img src="{{ $sigBase64 }}" style="height: 54px; object-fit: contain;" alt="">
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center; font-weight: bold;">{{ $treasurer }}</td>
                        </tr>
                    </table>
                </td>
                <td class="note-block">
                    <table class="note-table">
                        <tr>
                            <td style="width: 58px; font-weight: bold;">CATATAN</td>
                            <td>
                                KWITANSI INI SAH SEBAGAI BUKTI PEMBAYARAN<br>
                                HARAP DISIMPAN DENGAN BAIK
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian Biaya Pendidikan SD Namira Kraksaan - {{ $applicant->registration_number }}</title>
    <style>
        @page { margin: 1.1cm 1.2cm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #0f172a;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .frame {
            border: 1.5px solid #064e3b;
            border-radius: 6px;
            padding: 14px 16px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .title-band {
            background-color: #064e3b;
            color: #ffffff;
            text-align: center;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 10.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .applicant-bar {
            width: 100%;
            border-collapse: collapse;
            background-color: #f0fdf4;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .applicant-bar td {
            padding: 6px 9px;
            font-size: 8.5pt;
        }
        .sec-title {
            font-size: 9pt;
            font-weight: bold;
            color: #064e3b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 8px 0 5px 0;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .fee-table th {
            background-color: #064e3b;
            color: #ffffff;
            font-size: 8pt;
            padding: 6px 7px;
            text-align: left;
            border: 1px solid #064e3b;
        }
        .fee-table th.num, .fee-table td.num {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .fee-table td {
            padding: 5.5px 7px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            font-size: 8.2pt;
        }
        .sub {
            display: block;
            font-size: 7.2pt;
            color: #64748b;
            margin-top: 1px;
        }
        .hl-col {
            background-color: #ecfdf5;
            font-weight: bold;
            color: #064e3b;
        }
        .paid-tag {
            display: inline-block;
            background-color: #d1fae5;
            color: #065f46;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            margin-top: 2px;
        }
        .obs-tag {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            margin-top: 2px;
        }
        .total-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 1.5px solid #064e3b;
        }
        .two-col {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 10px -8px;
        }
        .two-col td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 8px 10px;
            background-color: #f8fafc;
        }
        .price-big {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 11pt;
            font-weight: bold;
            color: #064e3b;
        }
        .terms-box {
            border: 1px solid #e2e8f0;
            border-left: 3.5px solid #064e3b;
            background-color: #ffffff;
            padding: 8px 10px;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        .terms-box ol, .terms-box ul {
            margin: 3px 0 0 0;
            padding-left: 16px;
        }
        .footer-note {
            text-align: center;
            font-size: 7.5pt;
            color: #475569;
            border-top: 1px solid #e2e8f0;
            padding-top: 7px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
@php
    $logoFile = public_path('images/landing/logo-sd.png');
    $logoBase64 = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;
    $isFemale = $feeGuide['applicant_gender'] === 'P';
    $fmt = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp
<div class="frame">
    <table class="header-table">
        <tr>
            @if($logoBase64)
                <td style="width: 52px; vertical-align: middle;">
                    <img src="{{ $logoBase64 }}" style="width: 46px; height: 46px; object-fit: contain;">
                </td>
            @endif
            <td style="text-align: center; vertical-align: middle;">
                <div style="font-size: 13pt; font-weight: bold; color: #064e3b;">SD NAMIRA KRAKSAAN</div>
                <div style="font-size: 8.5pt; font-weight: bold; color: #047857;">{{ $feeGuide['tagline'] }}</div>
                <div style="font-size: 7.5pt; color: #64748b;">Jl. Panglima Sudirman No. 170 Patokan Kraksaan Kab. Probolinggo</div>
            </td>
            @if($logoBase64)
                <td style="width: 52px;"></td>
            @endif
        </tr>
    </table>

    <div class="title-band">
        Rincian Biaya Pendaftaran &amp; Investasi Pendidikan TP {{ $feeGuide['academic_year'] }}
    </div>

    <table class="applicant-bar">
        <tr>
            <td style="width: 55%;">
                <strong>Calon Siswa:</strong> {{ strtoupper($applicant->full_name) }} ({{ $feeGuide['applicant_gender_label'] }})<br>
                <strong>No. Registrasi:</strong> {{ $applicant->registration_number }}
            </td>
            <td style="width: 45%; text-align: right;">
                <strong>Total Estimasi:</strong> <span style="color:#064e3b; font-weight:bold;">{{ $fmt($feeGuide['totals']['applicant_total']) }}*</span><br>
                <strong>Sisa Estimasi Disiapkan:</strong> <span style="color:#b45309; font-weight:bold;">{{ $fmt($feeGuide['totals']['remaining_estimate']) }}*</span>
                <span class="sub">(Formulir Rp 250.000 sudah terverifikasi)</span>
            </td>
        </tr>
    </table>

    <div class="sec-title">1. Rincian Biaya Awal Masuk (Murid Baru TP {{ $feeGuide['academic_year'] }})</div>
    <table class="fee-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align:center;">No</th>
                <th style="width: 39%;">Komponen Investasi Pendidikan</th>
                <th class="num" style="width: 16%;">Muslim (Putra)</th>
                <th class="num" style="width: 16%;">Muslimah (Putri)</th>
                <th style="width: 24%;">Tahapan Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($feeGuide['initial_items'] as $item)
                <tr>
                    <td style="text-align:center; font-weight:bold;">{{ $item['no'] }}</td>
                    <td>
                        <strong>{{ $item['label'] }}</strong>
                        @if(!empty($item['sublabel']))
                            <span class="sub">{{ $item['sublabel'] }}</span>
                        @endif
                    </td>
                    @if($item['is_tbd'])
                        <td colspan="2" style="text-align:center; font-style:italic; color:#475569;">
                            Disampaikan saat masuk TP baru (sesuai penerbit)
                        </td>
                    @else
                        <td class="num {{ !$isFemale ? 'hl-col' : '' }}">{{ $fmt($item['putra']) }}</td>
                        <td class="num {{ $isFemale ? 'hl-col' : '' }}">{{ $fmt($item['putri']) }}</td>
                    @endif
                    <td>
                        {{ $item['stage'] }}
                        @if($item['is_paid'])
                            <br><span class="paid-tag">Sudah Dibayar &amp; Terverifikasi</span>
                        @elseif($item['is_observation_stage'])
                            <br><span class="obs-tag">Disiapkan Saat Observasi</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">
                    TOTAL ESTIMASI BIAYA AWAL MASUK
                    <span class="sub">{{ $feeGuide['totals']['note'] }}</span>
                </td>
                <td class="num {{ !$isFemale ? 'hl-col' : '' }}">{{ $fmt($feeGuide['totals']['putra']) }}*</td>
                <td class="num {{ $isFemale ? 'hl-col' : '' }}">{{ $fmt($feeGuide['totals']['putri']) }}*</td>
                <td style="font-size:7.5pt; color:#064e3b;">
                    Sisa disiapkan ({{ $feeGuide['applicant_gender_label'] }}):<br>
                    <strong>{{ $fmt($feeGuide['totals']['remaining_estimate']) }}*</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="sec-title" style="margin-top:0;">2. {{ $feeGuide['monthly_spp']['title'] }}</div>
                <div class="price-big">{{ $fmt($feeGuide['monthly_spp']['amount']) }} / bulan</div>
                <div style="font-size:7.5pt; color:#b45309; font-weight:bold; margin-bottom:4px;">({{ $feeGuide['monthly_spp']['due_note'] }})</div>
                <div style="font-size:7.8pt; color:#334155;">{{ $feeGuide['monthly_spp']['description'] }}</div>
            </td>
            <td>
                <div class="sec-title" style="margin-top:0;">3. {{ $feeGuide['monthly_savings']['title'] }}</div>
                <div class="price-big">{{ $fmt($feeGuide['monthly_savings']['total']) }} / bulan</div>
                <div style="font-size:7.5pt; color:#475569; margin-bottom:4px;">({{ $feeGuide['monthly_savings']['note'] }})</div>
                <table style="width:100%; font-size:7.8pt; border-collapse:collapse;">
                    @foreach($feeGuide['monthly_savings']['items'] as $sav)
                        <tr>
                            <td style="border:none; padding:1px 0; background:transparent;">• {{ $sav['label'] }}</td>
                            <td style="border:none; padding:1px 0; text-align:right; font-weight:bold; background:transparent;">{{ $fmt($sav['amount']) }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td style="background:#ffffff;">
                <div class="sec-title" style="margin-top:0;">Ketentuan Administrasi &amp; Pembayaran</div>
                <ol style="margin:3px 0 0 0; padding-left:15px; font-size:7.6pt; color:#334155;">
                    @foreach($feeGuide['terms'] as $term)
                        <li style="margin-bottom:2px;">{{ $term }}</li>
                    @endforeach
                </ol>
            </td>
            <td style="background:#ffffff;">
                <div class="sec-title" style="margin-top:0;">Pembayaran Via Rekening &amp; Lokasi</div>
                <p style="font-size:7.6pt; color:#334155; margin:0 0 6px 0;">{{ $feeGuide['payment_channel_note'] }}</p>
                @foreach($feeGuide['locations'] as $loc)
                    <div style="font-size:7.4pt; color:#475569; margin-bottom:2px;">
                        <strong>{{ $loc['name'] }}:</strong> {{ $loc['address'] }}
                    </div>
                @endforeach
                <div style="font-size:8pt; font-weight:bold; color:#064e3b; margin-top:5px;">
                    Layanan Kontak &amp; SPMB: WA {{ $feeGuide['contact_whatsapp'] }} ({{ $feeGuide['contact_label'] }})
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Bersama SD Namira, Menjadi Cahaya Menerangi Dunia &bull; Dicetak dari Portal Orang Tua SPMB Yayasan Pendidikan Namira
    </div>
</div>
</body>
</html>

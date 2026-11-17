<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Daftar Ulang SPMB - {{ $applicant->registration_number }}</title>
    <style>
        @page {
            margin: 1.4cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            color: #0f172a;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .outer-frame {
            border: 1.5px solid #064e3b;
            padding: 4px;
            background-color: #ffffff;
        }
        .inner-frame {
            border: 0.75px solid #b45309;
            padding: 18px 20px;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #064e3b;
            padding-bottom: 8px;
        }
        .kop-gold-rule {
            height: 2px;
            border-top: 0.75px solid #b45309;
            margin-top: 2px;
            margin-bottom: 14px;
        }
        .title-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .doc-heading {
            font-family: 'DejaVu Serif', Georgia, serif;
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 0.6px;
            margin: 0;
            text-transform: uppercase;
        }
        .doc-subheading {
            font-size: 8.5pt;
            color: #475569;
            margin: 2px 0 0 0;
        }
        .ref-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 7px 10px;
            text-align: right;
        }
        .ref-number {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: 1px solid #064e3b;
            color: #064e3b;
            background-color: #f0fdf4;
        }
        .status-pill-partial {
            border-color: #b45309;
            color: #92400e;
            background-color: #fffbeb;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-table td {
            padding: 7px 8px;
            vertical-align: top;
            border-bottom: 0.5px solid #f1f5f9;
        }
        .meta-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            display: block;
            margin-bottom: 1px;
        }
        .meta-val {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
        }
        .section-caption {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin: 0 0 6px 0;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .ledger-table th {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #ffffff;
            padding: 6px 10px;
            text-align: left;
        }
        .ledger-table th.num,
        .ledger-table td.num {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .ledger-table td {
            padding: 7px 10px;
            font-size: 9pt;
            border-bottom: 0.75px solid #e2e8f0;
        }
        .ledger-total-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1.5px solid #94a3b8;
        }
        .amount-band {
            width: 100%;
            border-collapse: collapse;
            background-color: #fafaf9;
            border: 1px solid #e7e5e4;
            border-left: 3.5px solid #b45309;
            margin-bottom: 14px;
        }
        .amount-band td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        .amount-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #78716c;
            display: block;
        }
        .amount-figure {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .terbilang-text {
            font-family: 'DejaVu Serif', Georgia, serif;
            font-style: italic;
            font-size: 9pt;
            color: #1e293b;
            margin-top: 2px;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8pt;
        }
        .history-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border-bottom: 1px solid #cbd5e1;
        }
        .history-table td {
            padding: 5px 8px;
            border-bottom: 0.5px solid #e2e8f0;
            color: #334155;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .footer-table td {
            vertical-align: top;
        }
        .stamp-box {
            border: 1px solid #064e3b;
            background-color: #f8fafc;
            padding: 8px 11px;
            width: 245px;
        }
        .stamp-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .stamp-meta {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 3px;
            line-height: 1.35;
        }
        .electronic-note {
            font-size: 7.2pt;
            color: #64748b;
            font-style: italic;
            margin-top: 6px;
            line-height: 1.35;
        }
        .sign-cell {
            text-align: center;
            width: 210px;
            font-size: 8.8pt;
        }
        .sign-gap {
            height: 44px;
        }
        .sign-line {
            border-top: 1px solid #334155;
            padding-top: 4px;
            font-weight: bold;
            color: #0f172a;
        }
    </style>
</head>
<body>

@php
    $unitCode = strtoupper($applicant->unit?->code ?? '');
    $isSmp = str_contains(strtolower($applicant->unit?->name ?? ''), 'smp') || $unitCode === 'SMP';
    $isSdKota = $unitCode === 'SD-02' || str_contains(strtolower($applicant->unit?->name ?? ''), 'kota');

    $schoolName = $isSmp
        ? ($applicant->unit?->name ?? 'SMP NAMIRA KOTA PROBOLINGGO')
        : ($isSdKota ? 'SD NAMIRA KOTA PROBOLINGGO' : 'SD NAMIRA KRAKSAAN');
    $unitLabel = $isSmp ? 'SMP Namira Kota Probolinggo' : ($isSdKota ? 'SD Namira Kota Probolinggo' : 'SD Namira Kraksaan');
    $schoolAddress = $applicant->unit?->address ?: (
        $isSmp
            ? 'Jl. KH. Ahmad Dahlan, Kota Probolinggo - Jawa Timur'
            : ($isSdKota ? 'Kota Probolinggo - Jawa Timur' : 'Kec. Kraksaan, Kabupaten Probolinggo - Jawa Timur')
    );
    $signCity = (!$isSmp && !$isSdKota) ? 'Kraksaan' : 'Probolinggo';
    $schoolEmail = $isSmp ? 'smp@namiraschool.com' : 'sd@namiraschool.com';

    $primaryColor = $isSmp ? '#1e3a8a' : '#064e3b';
    $secondaryColor = $isSmp ? '#1d4ed8' : '#047857';

    $logoFile = $isSmp ? public_path('images/landing/logo-smp.png') : public_path('images/landing/logo-sd.png');
    $logoBase64 = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;

    $dpPercentage = (int) ($setting?->min_down_payment_percentage ?? 60);
    $isFullPaymentOnly = $dpPercentage >= 100;

    $totalFee = (float) ($applicant->total_admission_fee ?? 0);
    $paidAmount = (float) ($applicant->paid_admission_amount ?? 0);
    $isFreeAdmission = $applicant->total_admission_fee !== null && $totalFee <= 0;
    $isFullyPaid = $isFreeAdmission
        || $applicant->admission_payment_status === 'paid'
        || in_array($applicant->status, ['fully_paid', 'enrolled'], true)
        || ($totalFee > 0 && $paidAmount >= $totalFee);

    $remainingAmount = $isFullyPaid ? 0 : max(0, $totalFee - $paidAmount);

    $verifiedAt = $applicant->settlement_payment_verified_at
        ?? $applicant->admission_payment_verified_at
        ?? now();

    $verifierName = $applicant->settlementPaymentVerifier?->name
        ?? $applicant->admissionPaymentVerifier?->name
        ?? 'Panitia Keuangan SPMB';

    $payerName = collect([$applicant->father_name, $applicant->mother_name])->filter()->implode(' / ') ?: 'Orang Tua / Wali Siswa';

    if ($isFreeAdmission) {
        $statusBadgeText = 'BEBAS BIAYA (LUNAS)';
        $paymentMethodText = 'Tanggungan Full / Beasiswa Penuh';
        $paymentStageDesc = 'Penetapan Bebas Biaya Daftar Ulang (Tanggungan Full)';
    } elseif ($isFullyPaid) {
        $statusBadgeText = 'LUNAS 100%';
        $paymentMethodText = $applicant->virtual_account_number
            ? 'Virtual Account: ' . $applicant->virtual_account_number . ($isSmp ? '' : ' (Bank Jatim)')
            : 'Transfer Bank / Verifikasi Keuangan';
        $paymentStageDesc = $isFullPaymentOnly
            ? 'Pelunasan Biaya Daftar Ulang (100%)'
            : 'Pembayaran & Pelunasan Biaya Daftar Ulang (100%)';
    } else {
        $statusBadgeText = "TERMIN 1 (DP {$dpPercentage}%)";
        $paymentMethodText = $applicant->virtual_account_number
            ? 'Virtual Account: ' . $applicant->virtual_account_number . ($isSmp ? '' : ' (Bank Jatim)')
            : 'Transfer Bank / Verifikasi Keuangan';
        $paymentStageDesc = "Pembayaran Daftar Ulang Termin 1 (Minimal {$dpPercentage}%)";
    }

    $verifiedHistory = collect($applicant->payment_history ?? [])
        ->filter(fn ($item) => ($item['status'] ?? '') === 'verified')
        ->values();
@endphp

<div class="outer-frame" style="border-color: {{ $primaryColor }};">
    <div class="inner-frame">
        <!-- Kop Sekolah -->
        <table class="kop-table" style="border-bottom-color: {{ $primaryColor }};">
            <tr>
                @if($logoBase64)
                <td style="width: 58px; vertical-align: middle; text-align: left;">
                    <img src="{{ $logoBase64 }}" style="width: 50px; height: 50px; object-fit: contain;">
                </td>
                @endif
                <td style="vertical-align: middle; text-align: left; padding-left: 6px;">
                    <div style="font-size: 9pt; font-weight: bold; color: #b45309; letter-spacing: 0.8px; text-transform: uppercase;">
                        YAYASAN PENDIDIKAN NAMIRA
                    </div>
                    <div style="font-size: 13pt; font-weight: bold; color: {{ $primaryColor }}; margin-top: 1px;">
                        {{ strtoupper($schoolName) }}
                    </div>
                    <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                        {{ $schoolAddress }} &bull; Email: {{ $schoolEmail }} &bull; WA: {{ $setting->contact_whatsapp ?? '082332922521' }}
                    </div>
                </td>
            </tr>
        </table>
        <div class="kop-gold-rule"></div>

        <!-- Judul & Nomor Kwitansi -->
        <table class="title-table">
            <tr>
                <td style="vertical-align: middle; width: 58%;">
                    <div class="doc-heading" style="color: {{ $primaryColor }};">Kwitansi Resmi</div>
                    <div class="doc-subheading">
                        Bukti Pembayaran Daftar Ulang SPMB Tahun Ajaran {{ $setting->academic_year ?? '2027/2028' }}
                    </div>
                </td>
                <td style="vertical-align: middle; width: 42%;">
                    <div class="ref-box">
                        <div class="ref-number">{{ $receiptNumber }}</div>
                        <span class="status-pill {{ !$isFullyPaid ? 'status-pill-partial' : '' }}" style="{{ $isFullyPaid ? "border-color: {$primaryColor}; color: {$primaryColor};" : '' }}">
                            {{ $statusBadgeText }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Metadata Siswa & Pembayaran -->
        <table class="meta-table">
            <tr>
                <td style="width: 54%;">
                    <span class="meta-label">Telah Diterima Dari (Orang Tua / Wali)</span>
                    <span class="meta-val">{{ $payerName }}</span>
                </td>
                <td style="width: 46%;">
                    <span class="meta-label">Nomor Registrasi</span>
                    <span class="meta-val" style="font-family: 'DejaVu Sans Mono', monospace;">{{ $applicant->registration_number }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="meta-label">Nama Calon Peserta Didik</span>
                    <span class="meta-val">{{ strtoupper($applicant->full_name) }}</span>
                </td>
                <td>
                    <span class="meta-label">Unit &amp; Jalur Pendaftaran</span>
                    <span class="meta-val">{{ $unitLabel }} &bull; {{ $applicant->category_label }}</span>
                </td>
            </tr>
            <tr>
                <td style="border-bottom: none;">
                    <span class="meta-label">Metode / Nomor Rekening</span>
                    <span class="meta-val">{{ $paymentMethodText }}</span>
                </td>
                <td style="border-bottom: none;">
                    <span class="meta-label">Tanggal Verifikasi</span>
                    <span class="meta-val">{{ $verifiedAt->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</span>
                </td>
            </tr>
        </table>

        <!-- Rincian Tagihan & Pembayaran -->
        <div class="section-caption">Rincian Tagihan &amp; Pembayaran Daftar Ulang</div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="background-color: {{ $primaryColor }}; width: 68%;">Keterangan</th>
                    <th class="num" style="background-color: {{ $primaryColor }}; width: 32%;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $paymentStageDesc }}</td>
                    <td class="num">Rp {{ number_format($totalFee, 0, ',', '.') }}</td>
                </tr>
                @if(!$isFreeAdmission && !$isFullPaymentOnly)
                <tr>
                    <td style="color: #475569;">Ketentuan Pembayaran Minimum Termin 1 ({{ $dpPercentage }}%)</td>
                    <td class="num" style="color: #475569;">Rp {{ number_format((float) ($applicant->min_down_payment ?? 0), 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="ledger-total-row">
                    <td>Total Pembayaran Diterima &amp; Terverifikasi</td>
                    <td class="num" style="color: {{ $primaryColor }};">Rp {{ number_format($paidAmount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="border-bottom: none; color: #475569;">Sisa Kewajiban Daftar Ulang</td>
                    <td class="num" style="border-bottom: none; font-weight: bold; color: {{ $remainingAmount > 0 ? '#b45309' : $secondaryColor }};">
                        Rp {{ number_format($remainingAmount, 0, ',', '.') }}
                        @if($remainingAmount <= 0)
                            (Lunas)
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Nominal & Terbilang -->
        <table class="amount-band">
            <tr>
                <td style="width: 34%; border-right: 0.75px solid #e7e5e4;">
                    <span class="amount-label">Jumlah Diterima</span>
                    <div class="amount-figure">Rp {{ number_format($paidAmount, 0, ',', '.') }}</div>
                </td>
                <td style="width: 66%;">
                    <span class="amount-label">Terbilang</span>
                    <div class="terbilang-text">{{ $paidTerbilang }}</div>
                </td>
            </tr>
        </table>

        @if($verifiedHistory->isNotEmpty())
        <div class="section-caption">Catatan Verifikasi Tahapan</div>
        <table class="history-table">
            <thead>
                <tr>
                    <th style="width: 48%;">Tahap Pembayaran</th>
                    <th style="width: 26%;">Waktu Verifikasi</th>
                    <th style="width: 26%;">Petugas Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($verifiedHistory as $hist)
                <tr>
                    <td>
                        {{ $hist['title'] ?? 'Pembayaran Daftar Ulang' }}
                        @if(isset($hist['verified_amount']))
                            (Rp {{ number_format((float) $hist['verified_amount'], 0, ',', '.') }})
                        @endif
                    </td>
                    <td>
                        {{ !empty($hist['verified_at']) ? \Carbon\Carbon::parse($hist['verified_at'])->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') : '-' }} WIB
                    </td>
                    <td>{{ $hist['verifier_name'] ?? $verifierName }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Footer Validasi Digital & Tanda Tangan -->
        <table class="footer-table">
            <tr>
                <td style="width: 58%;">
                    <div class="stamp-box" style="border-color: {{ $primaryColor }};">
                        <div class="stamp-title" style="color: {{ $primaryColor }};">
                            &#10003; Terverifikasi &mdash; {{ $statusBadgeText }}
                        </div>
                        <div class="stamp-meta">
                            Diverifikasi oleh: <strong>{{ $verifierName }}</strong><br>
                            Waktu pencatatan: {{ $verifiedAt->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
                        </div>
                    </div>
                    <div class="electronic-note">
                        Kwitansi ini diterbitkan secara elektronik oleh sistem resmi SPMB {{ $unitLabel }}<br>
                        dan sah sebagai bukti pembayaran daftar ulang tanpa tanda tangan basah.
                    </div>
                </td>
                <td class="sign-cell" style="width: 42%;">
                    <div>{{ $signCity }}, {{ $verifiedAt->timezone('Asia/Jakarta')->translatedFormat('d F Y') }}</div>
                    <div style="color: #475569;">Panitia Keuangan SPMB {{ $isSmp ? 'SMP Namira' : ($isSdKota ? 'SD Namira Kota' : 'SD Namira') }}</div>
                    <div class="sign-gap"></div>
                    <div class="sign-line">{{ $verifierName }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>

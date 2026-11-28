<?php

namespace App\Modules\SPMB\Services;

use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;

class SpmbSdKraksaanFeeService
{
    public const FORMULIR = 250000;

    public const PSIKOTES_OBSERVASI = 200000;

    public const SERAGAM_PUTRA = 1300000;

    public const SERAGAM_PUTRI = 1415000;

    public const INFAQ_PENDIDIKAN = 3500000;

    public const OPERASIONAL_PENDIDIKAN = 2000000;

    public const TOTAL_PUTRA = 7250000;

    public const TOTAL_PUTRI = 7365000;

    public const BULANAN_SPP_BEKAL = 500000;

    public const BULANAN_RIHLAH = 15000;

    public const BULANAN_TUTUP_TAHUN = 10000;

    public const BULANAN_KOMITE = 5000;

    public const BULANAN_TABUNGAN_TOTAL = 30000;

    /**
     * Strictly match SD Namira Kraksaan (unit code 'SD', excluding SD-02 Kota, SMP, TK, KB).
     */
    public function isSdKraksaanApplicant(SpmbApplicant $applicant): bool
    {
        $code = strtoupper(trim((string) ($applicant->unit?->code ?? '')));

        return $code === 'SD';
    }

    /**
     * Determine whether applicant has already paid & been verified for initial registration.
     */
    public function hasPaidRegistration(SpmbApplicant $applicant): bool
    {
        if ($applicant->registration_payment_verified_at !== null) {
            return true;
        }

        return in_array($applicant->status, [
            'verified',
            'scheduled',
            'accepted',
            'reserve',
            'partial_paid',
            'fully_paid',
            'enrolled',
        ], true);
    }

    /**
     * Eligibility gate: ONLY SD Namira Kraksaan active TP 2027/2028 applicants whose payment is verified.
     */
    public function isEligibleForFeeGuide(SpmbApplicant $applicant, ?SpmbSetting $setting = null): bool
    {
        if (! $this->isSdKraksaanApplicant($applicant)) {
            return false;
        }

        if ($setting && $applicant->academic_year && $setting->isFutureIndenYear($applicant->academic_year)) {
            return false;
        }

        return $this->hasPaidRegistration($applicant);
    }

    /**
     * Build the official TP 2027/2028 cost breakdown payload for SD Namira Kraksaan parents.
     */
    public function buildFeeGuide(SpmbApplicant $applicant, ?SpmbSetting $setting = null): ?array
    {
        if (! $this->isEligibleForFeeGuide($applicant, $setting)) {
            return null;
        }

        $isFemale = strtoupper((string) $applicant->gender) === 'P';
        $applicantTotal = $isFemale ? self::TOTAL_PUTRI : self::TOTAL_PUTRA;
        $alreadyPaidRegistration = self::FORMULIR;
        $remainingEstimate = max(0, $applicantTotal - $alreadyPaidRegistration);

        $selectionDate = $applicant->observation_date ?? $setting?->selection_date;
        $selectionDateLabel = $selectionDate
            ? $selectionDate->locale('id')->translatedFormat('l, d F Y')
            : null;

        return [
            'unit_name' => 'SD Namira Kraksaan',
            'tagline' => 'Berilmu · Berakhlak · Berprestasi',
            'academic_year' => '2027/2028',
            'applicant_gender' => $isFemale ? 'P' : 'L',
            'applicant_gender_label' => $isFemale ? 'Muslimah (Putri)' : 'Muslim (Putra)',
            'selection_date_label' => $selectionDateLabel,
            'totals' => [
                'putra' => self::TOTAL_PUTRA,
                'putri' => self::TOTAL_PUTRI,
                'applicant_total' => $applicantTotal,
                'already_paid_registration' => $alreadyPaidRegistration,
                'remaining_estimate' => $remainingEstimate,
                'note' => '*Di luar biaya buku paket kurikulum',
            ],
            'initial_items' => [
                [
                    'no' => 1,
                    'key' => 'formulir',
                    'label' => 'Formulir Pendaftaran + Souvenir Eksklusif',
                    'sublabel' => null,
                    'putra' => self::FORMULIR,
                    'putri' => self::FORMULIR,
                    'stage' => 'Saat Awal Pendaftaran',
                    'is_paid' => true,
                    'is_observation_stage' => false,
                    'is_tbd' => false,
                ],
                [
                    'no' => 2,
                    'key' => 'psikotes_observasi',
                    'label' => 'Psikotes & Observasi Kesiapan Belajar',
                    'sublabel' => 'Pemetaan kepribadian, kemandirian & potensi',
                    'putra' => self::PSIKOTES_OBSERVASI,
                    'putri' => self::PSIKOTES_OBSERVASI,
                    'stage' => $selectionDateLabel ? "Saat Jadwal Observasi ({$selectionDateLabel})" : 'Saat Jadwal Observasi',
                    'is_paid' => false,
                    'is_observation_stage' => true,
                    'is_tbd' => false,
                ],
                [
                    'no' => 3,
                    'key' => 'seragam',
                    'label' => 'Paket Seragam Sekolah & Atribut Lengkap',
                    'sublabel' => '5 Pasang Seragam, Jilbab/Kopyah, Topi, Dasi, Sabuk',
                    'putra' => self::SERAGAM_PUTRA,
                    'putri' => self::SERAGAM_PUTRI,
                    'stage' => 'Setelah Lolos Observasi',
                    'is_paid' => false,
                    'is_observation_stage' => false,
                    'is_tbd' => false,
                ],
                [
                    'no' => 4,
                    'key' => 'infaq_pendidikan',
                    'label' => 'Infaq Pendidikan',
                    'sublabel' => 'Pengembangan sarana prasarana & mutu belajar',
                    'putra' => self::INFAQ_PENDIDIKAN,
                    'putri' => self::INFAQ_PENDIDIKAN,
                    'stage' => 'Min. 60% Saat Daftar Ulang',
                    'is_paid' => false,
                    'is_observation_stage' => false,
                    'is_tbd' => false,
                ],
                [
                    'no' => 5,
                    'key' => 'operasional_pendidikan',
                    'label' => 'Biaya Operasional Pendidikan (2 Semester / 1 Th)',
                    'sublabel' => 'Kegiatan kurikuler, kesiswaan & asesmen',
                    'putra' => self::OPERASIONAL_PENDIDIKAN,
                    'putri' => self::OPERASIONAL_PENDIDIKAN,
                    'stage' => 'Diangsur s.d. Juni 2027',
                    'is_paid' => false,
                    'is_observation_stage' => false,
                    'is_tbd' => false,
                ],
                [
                    'no' => 6,
                    'key' => 'buku_paket',
                    'label' => 'Biaya Buku Paket Kurikulum',
                    'sublabel' => 'Disampaikan saat masuk TP baru (sesuai penerbit)',
                    'putra' => null,
                    'putri' => null,
                    'stage' => 'Maksimal Juni 2027',
                    'is_paid' => false,
                    'is_observation_stage' => false,
                    'is_tbd' => true,
                ],
            ],
            'monthly_spp' => [
                'title' => 'Biaya Bulanan: SPP & Bekal Sehat Bergizi Terpadu',
                'amount' => self::BULANAN_SPP_BEKAL,
                'due_note' => 'Jatuh tempo tgl 7, mulai Juli 2027',
                'description' => 'Mencakup operasional belajar mengajar reguler serta penyediaan katering makanan sehat, higienis, dan bergizi setiap hari aktif sekolah.',
            ],
            'monthly_savings' => [
                'title' => 'Tabungan Rihlah, Tutup Akhir Tahun, & Komite',
                'total' => self::BULANAN_TABUNGAN_TOTAL,
                'note' => 'Tahap pembayaran per bulan',
                'items' => [
                    ['label' => 'Rihlah', 'amount' => self::BULANAN_RIHLAH],
                    ['label' => 'Tutup Akhir Tahun', 'amount' => self::BULANAN_TUTUP_TAHUN],
                    ['label' => 'Komite', 'amount' => self::BULANAN_KOMITE],
                ],
            ],
            'terms' => [
                'Formulir wajib dikembalikan maksimal 7 hari kerja sejak awal mendaftar.',
                'Calon siswa diterima wajib membayar awal minimal 60% daftar ulang.',
                'Pelunasan Biaya Operasional & Infaq paling lambat akhir Juni 2027.',
                'Seluruh biaya yang dibayarkan tidak dapat ditarik kembali.',
                'Pembayaran bisa melalui via transfer Bank JATIM.',
            ],
            'required_documents' => [
                '1 Lembar FC KIA / Surat Keterangan Lahir',
                '1 Lembar FC Akta Kelahiran resmi',
                '1 Lembar FC Kartu Keluarga (KK) & KTP Ortu',
                '4 Lembar Pasfoto 3×4 (Latar Belakang Merah)',
            ],
            'payment_channel_note' => 'Pembayaran awal daftar ulang disalurkan via transfer. Petunjuk transaksi transfer & verifikasi akan diinformasikan resmi oleh panitia setelah calon siswa dinyatakan diterima.',
            'locations' => [
                ['name' => 'Gedung 1', 'address' => 'Jl. Panglima Sudirman No. 170 Patokan Kraksaan Kab. Probolinggo'],
                ['name' => 'Gedung 2', 'address' => 'Jl. IR. H. Juanda No. 371, Kp. Arab Patokan Kraksaan Kab. Probolinggo'],
            ],
            'contact_whatsapp' => '0822-3323-2258',
            'contact_label' => 'Panitia SPMB SD Namira Kraksaan',
        ];
    }
}

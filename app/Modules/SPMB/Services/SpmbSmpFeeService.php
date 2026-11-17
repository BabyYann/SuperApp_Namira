<?php

namespace App\Modules\SPMB\Services;

use App\Modules\SPMB\Models\SpmbApplicant;
use App\Modules\SPMB\Models\SpmbSetting;

class SpmbSmpFeeService
{
    public const PSIKOTES = 250000;

    public const SERAGAM_PUTRA = 1525000;

    public const SERAGAM_PUTRI = 1830000;

    public const RUTIN_BULANAN = 650000;

    public const INFAQ_KEGIATAN = 2000000;

    public const PENGEMBANGAN_INDEN = 6000000;

    public const PENGEMBANGAN_WAITING_LIST = 7000000;

    public const ALUMNI_SD_DISCOUNT = 1000000;

    public const ADIK_KANDUNG_DISCOUNT = 500000;

    public const MAHAD_TOTAL = 1650000;

    /**
     * Check whether an applicant belongs to the SMP unit.
     */
    public function isSmpApplicant(SpmbApplicant $applicant): bool
    {
        $code = strtoupper($applicant->unit?->code ?? '');
        $name = strtolower($applicant->unit?->name ?? '');

        return $code === 'SMP' || str_contains($name, 'smp');
    }

    /**
     * Format academic year shorthand (e.g. "2027/2028" -> "27/28").
     */
    public function shortAcademicYear(?SpmbSetting $setting): string
    {
        $year = $setting?->academic_year ?: '2027/2028';
        if (preg_match('/(\d{4})\/(\d{4})/', $year, $m)) {
            return substr($m[1], -2).'/'.substr($m[2], -2);
        }

        return $year;
    }

    /**
     * Build the 7 standard SMP TA 2027/2028 breakdown items for an applicant.
     */
    public function buildDefaultItems(
        SpmbApplicant $applicant,
        ?SpmbSetting $setting = null,
        ?bool $withAlumniDiscount = null,
        bool $withSiblingDiscount = false,
        bool $withMahad = false,
        ?bool $asInden = null
    ): array {
        $isFemale = strtoupper((string) $applicant->gender) === 'P';
        $isInden = $asInden ?? (bool) $applicant->is_inden;
        $isAlumniSd = $withAlumniDiscount ?? in_array($applicant->category, ['internal_sd', 'internal'], true);

        $pengembangan = $isInden ? self::PENGEMBANGAN_INDEN : self::PENGEMBANGAN_WAITING_LIST;
        $notes = [$isInden ? 'INDEN' : 'WAITING LIST'];

        if ($isAlumniSd) {
            $pengembangan = max(0, $pengembangan - self::ALUMNI_SD_DISCOUNT);
            $notes[] = 'POT. ALUMNI SD';
        }
        if ($withSiblingDiscount) {
            $pengembangan = max(0, $pengembangan - self::ADIK_KANDUNG_DISCOUNT);
            $notes[] = 'POT. ADIK KANDUNG';
        }

        $shortYear = $this->shortAcademicYear($setting);

        return [
            [
                'key' => 'psikotes',
                'label' => 'PSIKOTES & OBSERVASI',
                'sublabel' => 'SELEKSI MASUK',
                'amount' => self::PSIKOTES,
            ],
            [
                'key' => 'seragam',
                'label' => 'SERAGAM & ATRIBUT',
                'sublabel' => $isFemale ? 'PEREMPUAN' : 'LAKI-LAKI',
                'amount' => $isFemale ? self::SERAGAM_PUTRI : self::SERAGAM_PUTRA,
            ],
            [
                'key' => 'rutin_bulanan',
                'label' => 'RUTIN BULANAN',
                'sublabel' => 'SPP, MAKAN, TAB. & KOMITE',
                'amount' => self::RUTIN_BULANAN,
            ],
            [
                'key' => 'infaq_kegiatan',
                'label' => 'INFAQ KEGIATAN',
                'sublabel' => "AWAL TA {$shortYear}",
                'amount' => self::INFAQ_KEGIATAN,
            ],
            [
                'key' => 'infaq_pengembangan',
                'label' => 'INFAQ PENGEMBANGAN',
                'sublabel' => implode(' • ', $notes),
                'amount' => $pengembangan,
            ],
            [
                'key' => 'buku_paket',
                'label' => 'BUKU PAKET PENDAMPING',
                'sublabel' => 'DIINFORMASIKAN JUNI 2027',
                'amount' => 0,
            ],
            [
                'key' => 'mahad',
                'label' => "INFAQ MA'HAD (ASRAMA)",
                'sublabel' => $withMahad ? 'ASRAMA PUTRA/PUTRI' : '-',
                'amount' => $withMahad ? self::MAHAD_TOTAL : 0,
            ],
        ];
    }

    /**
     * Resolve final 7 receipt rows for an SMP applicant, ensuring sum matches paid amount.
     */
    public function resolveReceiptItems(SpmbApplicant $applicant, ?SpmbSetting $setting = null): array
    {
        $paidAmount = (float) ($applicant->paid_admission_amount ?? 0);

        if (is_array($applicant->fee_breakdown) && ! empty($applicant->fee_breakdown['items'])) {
            $storedItems = collect($applicant->fee_breakdown['items'])
                ->map(fn ($item) => [
                    'key' => (string) ($item['key'] ?? 'custom'),
                    'label' => (string) ($item['label'] ?? '-'),
                    'sublabel' => (string) ($item['sublabel'] ?? '-'),
                    'amount' => max(0, (float) ($item['amount'] ?? 0)),
                ])
                ->values()
                ->all();

            $storedSum = array_sum(array_column($storedItems, 'amount'));
            if (abs($storedSum - $paidAmount) < 1) {
                return $storedItems;
            }
        }

        if ($paidAmount <= 0) {
            $items = $this->buildDefaultItems($applicant, $setting);
            foreach ($items as &$item) {
                $item['amount'] = 0;
                $item['sublabel'] = $item['key'] === 'infaq_pengembangan'
                    ? 'TANGGUNGAN FULL / BEBAS BIAYA'
                    : '-';
            }

            return $items;
        }

        // Try exact standard combinations matching PDF TA 2027/2028
        $indenChoices = [(bool) $applicant->is_inden, ! (bool) $applicant->is_inden];
        $alumniDefault = in_array($applicant->category, ['internal_sd', 'internal'], true);
        $alumniChoices = [$alumniDefault, ! $alumniDefault];

        foreach ($indenChoices as $asInden) {
            foreach ($alumniChoices as $withAlumni) {
                foreach ([false, true] as $withSibling) {
                    foreach ([false, true] as $withMahad) {
                        $candidate = $this->buildDefaultItems($applicant, $setting, $withAlumni, $withSibling, $withMahad, $asInden);
                        $sum = array_sum(array_column($candidate, 'amount'));
                        if (abs($sum - $paidAmount) < 1) {
                            return $candidate;
                        }
                    }
                }
            }
        }

        // Custom / scholarship / partial nominal: preserve fixed school fees when possible, adjust Pengembangan
        $items = $this->buildDefaultItems($applicant, $setting);
        $fixedKeys = ['psikotes', 'seragam', 'rutin_bulanan', 'infaq_kegiatan'];
        $fixedSum = collect($items)->whereIn('key', $fixedKeys)->sum('amount');

        if ($paidAmount >= $fixedSum) {
            $remainder = $paidAmount - $fixedSum;
            $basePengembangan = collect($items)->firstWhere('key', 'infaq_pengembangan')['amount'] ?? self::PENGEMBANGAN_INDEN;

            if ($remainder >= ($basePengembangan + self::MAHAD_TOTAL)) {
                $mahadAmount = self::MAHAD_TOTAL;
                $pengembanganAmount = $remainder - $mahadAmount;
                $pengembanganNote = $pengembanganAmount > $basePengembangan ? 'DAFTAR ULANG' : 'KERINGANAN / POT. BIAYA';
            } else {
                $mahadAmount = 0;
                $pengembanganAmount = $remainder;
                $pengembanganNote = $pengembanganAmount < $basePengembangan
                    ? 'DAFTAR ULANG / KERINGANAN BIAYA'
                    : (collect($items)->firstWhere('key', 'infaq_pengembangan')['sublabel'] ?? 'INDEN');
            }

            foreach ($items as &$item) {
                if ($item['key'] === 'infaq_pengembangan') {
                    $item['amount'] = $pengembanganAmount;
                    $item['sublabel'] = $pengembanganNote;
                } elseif ($item['key'] === 'mahad') {
                    $item['amount'] = $mahadAmount;
                    $item['sublabel'] = $mahadAmount > 0 ? 'ASRAMA PUTRA/PUTRI' : '-';
                }
            }

            return $items;
        }

        // Paid amount is below fixedSum (e.g. heavy scholarship or partial down payment): allocate greedily so total always balances
        $remaining = $paidAmount;
        $priorityOrder = ['seragam', 'rutin_bulanan', 'psikotes', 'infaq_kegiatan', 'infaq_pengembangan', 'mahad', 'buku_paket'];
        $byKey = collect($items)->keyBy('key')->all();

        foreach ($priorityOrder as $key) {
            $orig = $byKey[$key]['amount'] ?? 0;
            $allocated = min($remaining, $orig);
            $byKey[$key]['amount'] = $allocated;
            if ($allocated <= 0 && $key !== 'buku_paket') {
                $byKey[$key]['sublabel'] = '-';
            }
            $remaining = max(0, $remaining - $allocated);
        }

        if ($remaining > 0) {
            $byKey['infaq_pengembangan']['amount'] += $remaining;
            $byKey['infaq_pengembangan']['sublabel'] = 'DAFTAR ULANG';
        }

        return array_values($byKey);
    }

    /**
     * Validate and normalize user-submitted breakdown payload.
     */
    public function sanitizeSubmittedBreakdown(?array $rawBreakdown): ?array
    {
        if (empty($rawBreakdown['items']) || ! is_array($rawBreakdown['items'])) {
            return null;
        }

        $items = [];
        foreach ($rawBreakdown['items'] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $items[] = [
                'key' => substr(trim((string) ($item['key'] ?? 'item')), 0, 50),
                'label' => substr(trim((string) ($item['label'] ?? 'KOMPONEN')), 0, 100),
                'sublabel' => substr(trim((string) ($item['sublabel'] ?? '-')) ?: '-', 0, 120),
                'amount' => max(0, (float) ($item['amount'] ?? 0)),
            ];
        }

        if (empty($items)) {
            return null;
        }

        return [
            'with_alumni_discount' => (bool) ($rawBreakdown['with_alumni_discount'] ?? false),
            'with_sibling_discount' => (bool) ($rawBreakdown['with_sibling_discount'] ?? false),
            'with_mahad' => (bool) ($rawBreakdown['with_mahad'] ?? false),
            'treasurer_name' => substr(trim((string) ($rawBreakdown['treasurer_name'] ?? 'Nadia Pramadianti, S.M.')) ?: 'Nadia Pramadianti, S.M.', 0, 120),
            'items' => $items,
        ];
    }
}

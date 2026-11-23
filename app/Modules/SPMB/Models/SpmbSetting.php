<?php

namespace App\Modules\SPMB\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SpmbSetting extends Model
{
    use HasFactory;

    protected $table = 'spmb_settings';

    protected $fillable = [
        'unit_id',
        'name',
        'academic_year',
        'allow_future_inden',
        'inden_academic_years',
        'closed_academic_years',
        'quota',
        'registration_fee',
        'qris_image_path',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'admission_fee_total',
        'min_down_payment_percentage',
        'is_active',
        'start_date',
        'end_date',
        'selection_date',
        'announcement_date',
        'announcement_released',
        'contact_whatsapp',
        'whatsapp_group_link',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_future_inden' => 'boolean',
        'inden_academic_years' => 'array',
        'closed_academic_years' => 'array',
        'quota' => 'integer',
        'registration_fee' => 'decimal:2',
        'admission_fee_total' => 'decimal:2',
        'min_down_payment_percentage' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'selection_date' => 'date:Y-m-d',
        'announcement_date' => 'date:Y-m-d',
        'announcement_released' => 'boolean',
    ];

    protected $appends = ['qris_image_url'];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        if ($this->qris_image_path) {
            return Storage::url($this->qris_image_path);
        }
        return null;
    }

    /**
     * Normalize and validate an academic year string (e.g. "2028-2029" -> "2028/2029").
     */
    public static function normalizeAcademicYear(?string $year): ?string
    {
        $clean = trim((string) $year);
        if ($clean === '') {
            return null;
        }

        $clean = str_replace('-', '/', $clean);
        if (! preg_match('/^(\d{4})\/(\d{4})$/', $clean, $m)) {
            return null;
        }

        $start = (int) $m[1];
        $end = (int) $m[2];
        if ($end !== $start + 1 || $start < 2020 || $start > 2050) {
            return null;
        }

        return "{$start}/{$end}";
    }

    /**
     * Extract the 4-digit start year from an academic year string (e.g. "2028/2029" -> 2028).
     */
    public static function extractStartYear(?string $year): ?int
    {
        $normalized = self::normalizeAcademicYear($year);
        if (! $normalized) {
            return null;
        }

        return (int) substr($normalized, 0, 4);
    }

    /**
     * Generate sequential future academic years after the given base year.
     */
    public static function generateNextAcademicYears(?string $baseYear, int $count = 3): array
    {
        $start = self::extractStartYear($baseYear) ?? 2027;
        $years = [];
        for ($i = 1; $i <= $count; $i++) {
            $y = $start + $i;
            $years[] = "{$y}/" . ($y + 1);
        }

        return $years;
    }

    /**
     * Resolve sanitized, strictly-future academic years open for Inden on this unit.
     */
    public function getFutureIndenAcademicYears(): array
    {
        if (! ($this->allow_future_inden ?? true)) {
            return [];
        }

        $primaryYear = self::normalizeAcademicYear($this->academic_year) ?? '2027/2028';
        $primaryStart = self::extractStartYear($primaryYear) ?? 2027;

        $raw = is_array($this->inden_academic_years)
            ? $this->inden_academic_years
            : self::generateNextAcademicYears($primaryYear, 3);

        $cleaned = [];
        foreach ($raw as $entry) {
            $norm = self::normalizeAcademicYear(is_string($entry) ? $entry : null);
            if (! $norm) {
                continue;
            }
            $entryStart = self::extractStartYear($norm);
            if ($entryStart !== null && $entryStart > $primaryStart) {
                $cleaned[$entryStart] = $norm;
            }
        }

        ksort($cleaned);

        return array_values($cleaned);
    }

    /**
     * Resolve sanitized closed/full academic years configured on this unit.
     */
    public function getClosedAcademicYears(): array
    {
        $raw = is_array($this->closed_academic_years) ? $this->closed_academic_years : [];
        $cleaned = [];

        foreach ($raw as $entry) {
            $norm = self::normalizeAcademicYear(is_string($entry) ? $entry : null);
            $start = $norm ? self::extractStartYear($norm) : null;
            if ($norm && $start !== null) {
                $cleaned[$start] = $norm;
            }
        }

        ksort($cleaned);

        return array_values($cleaned);
    }

    /**
     * Determine whether a specific academic year is closed / full for this unit.
     */
    public function isAcademicYearClosed(?string $academicYear): bool
    {
        $norm = self::normalizeAcademicYear($academicYear);
        if (! $norm) {
            return false;
        }

        return in_array($norm, $this->getClosedAcademicYears(), true);
    }

    /**
     * Determine whether the unit's primary academic year is closed / full.
     */
    public function isPrimaryAcademicYearClosed(): bool
    {
        $primary = self::normalizeAcademicYear($this->academic_year) ?? ($this->academic_year ?: '2027/2028');

        return $this->isAcademicYearClosed($primary);
    }

    /**
     * Return all OPEN & allowed academic year strings (primary + future inden years minus closed/full years).
     */
    public function getAllowedAcademicYears(): array
    {
        $primary = self::normalizeAcademicYear($this->academic_year) ?? ($this->academic_year ?: '2027/2028');
        $all = array_values(array_unique(array_merge([$primary], $this->getFutureIndenAcademicYears())));
        $closed = $this->getClosedAcademicYears();

        if (empty($closed)) {
            return $all;
        }

        return array_values(array_filter($all, fn (string $yr) => ! in_array($yr, $closed, true)));
    }

    /**
     * Return the first open academic year available for registration (or null if all years are full/closed).
     */
    public function getDefaultOpenAcademicYear(): ?string
    {
        $allowed = $this->getAllowedAcademicYears();

        return $allowed[0] ?? null;
    }

    /**
     * Determine whether a given academic year is a future Inden intake relative to this setting.
     */
    public function isFutureIndenYear(?string $academicYear): bool
    {
        $targetStart = self::extractStartYear($academicYear);
        $primaryStart = self::extractStartYear($this->academic_year);

        if ($targetStart === null || $primaryStart === null) {
            return false;
        }

        return $targetStart > $primaryStart;
    }

    /**
     * Structured academic year options for public registration forms and landing cards.
     * Closed/full years remain listed with `is_closed = true` so parents see clearly that quota is full,
     * while backend validation strictly rejects submissions for closed years.
     */
    public function getSelectableAcademicYears(?string $primaryBadge = null, ?string $primaryDescription = null): array
    {
        $primaryYear = self::normalizeAcademicYear($this->academic_year) ?? ($this->academic_year ?: '2027/2028');
        $primaryStart = self::extractStartYear($primaryYear) ?? 2027;
        $closedYears = $this->getClosedAcademicYears();
        $primaryClosed = in_array($primaryYear, $closedYears, true);

        $options = [
            [
                'value' => $primaryYear,
                'label' => "TA {$primaryYear}",
                'badge' => $primaryClosed ? 'Kuota Penuh · Tutup' : ($primaryBadge ?: 'Tahap Berjalan'),
                'description' => $primaryClosed
                    ? "Pendaftaran Tahun Ajaran {$primaryYear} sudah penuh dan ditutup."
                    : ($primaryDescription ?: "Pendaftaran aktif untuk masuk Tahun Ajaran {$primaryYear}"),
                'is_future_inden' => false,
                'is_closed' => $primaryClosed,
                'offset_years' => 0,
            ],
        ];

        foreach ($this->getFutureIndenAcademicYears() as $futureYear) {
            $futureStart = self::extractStartYear($futureYear) ?? ($primaryStart + 1);
            $offset = max(1, $futureStart - $primaryStart);
            $futureClosed = in_array($futureYear, $closedYears, true);
            $options[] = [
                'value' => $futureYear,
                'label' => "TA {$futureYear}",
                'badge' => $futureClosed
                    ? 'Kuota Penuh · Tutup'
                    : ($offset === 1 ? 'Inden Tahun Depan' : "Inden +{$offset} Tahun"),
                'description' => $futureClosed
                    ? "Kuota Jalur Inden Tahun Ajaran {$futureYear} sudah penuh dan ditutup."
                    : "Pemesanan kursi Jalur Inden untuk masuk Tahun Ajaran {$futureYear}",
                'is_future_inden' => true,
                'is_closed' => $futureClosed,
                'offset_years' => $offset,
            ];
        }

        return $options;
    }
}

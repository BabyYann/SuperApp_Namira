<?php

namespace App\Modules\Quran\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranRubricItem extends Model
{
    protected $table = 'quran_rubric_items';

    public const SCOPE_LEVEL = 'level';
    public const SCOPE_GRADE = 'grade';

    public const CATEGORY_MATERI = 'materi';
    public const CATEGORY_SURAH = 'surah_pendek';
    public const CATEGORY_DOA = 'doa_harian';
    public const CATEGORY_HADIST = 'hadist';
    public const CATEGORY_NIAT_SHOLAT = 'niat_sholat';
    public const CATEGORY_BACAAN_SHOLAT = 'bacaan_sholat';

    public const CATEGORIES = [
        self::CATEGORY_MATERI => 'Materi Pokok',
        self::CATEGORY_SURAH => 'Surah Pendek',
        self::CATEGORY_DOA => 'Doa Harian',
        self::CATEGORY_HADIST => 'Hadist',
        self::CATEGORY_NIAT_SHOLAT => 'Niat Sholat & Bersuci',
        self::CATEGORY_BACAAN_SHOLAT => 'Bacaan Sholat',
    ];

    public const LEVEL_CATEGORIES = [
        self::CATEGORY_MATERI => 'Materi Pokok',
        self::CATEGORY_SURAH => 'Surah Pendek',
    ];

    public const GRADE_CATEGORIES = [
        self::CATEGORY_HADIST => 'Hadist',
        self::CATEGORY_DOA => 'Doa Harian',
        self::CATEGORY_NIAT_SHOLAT => 'Niat Sholat & Bersuci',
        self::CATEGORY_BACAAN_SHOLAT => 'Bacaan Sholat',
    ];

    protected $fillable = [
        'unit_id',
        'scope',
        'level_id',
        'grade_level',
        'category',
        'title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(QuranLevel::class, 'level_id');
    }
}

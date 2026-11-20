<?php

namespace App\Modules\Quran\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranLevel extends Model
{
    protected $table = 'quran_levels';

    protected $fillable = [
        'unit_id',
        'name',
        'code',
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

    public function rubricItems(): HasMany
    {
        return $this->hasMany(QuranRubricItem::class, 'level_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeRubricItems(): HasMany
    {
        return $this->hasMany(QuranRubricItem::class, 'level_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(QuranGroup::class, 'level_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(QuranEnrollment::class, 'level_id');
    }
}

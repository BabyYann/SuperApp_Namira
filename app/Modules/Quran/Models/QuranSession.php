<?php

namespace App\Modules\Quran\Models;

use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSession extends Model
{
    protected $table = 'quran_sessions';

    protected $fillable = [
        'unit_id',
        'name',
        'start_time',
        'end_time',
        'days',
        'target_note',
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

    public function groups(): HasMany
    {
        return $this->hasMany(QuranGroup::class, 'session_id');
    }
}

<?php

namespace App\Modules\Yayasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'level',
        'unit_type',
        'features',
        'logo',
        'email',
        'phone',
        'address',
        'description',
        'work_start_time',
        'work_end_time',
        'late_tolerance_minutes',
        'principal_id',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function isDaycare(): bool
    {
        return $this->unit_type === 'daycare' || $this->category === 'Daycare' || ($this->features['daycare'] ?? false);
    }

    public function isFormalSchool(): bool
    {
        return $this->unit_type === 'formal_school' || in_array($this->category, ['TK', 'SD', 'SMP', 'SMA', 'SMK']) || ($this->features['academic'] ?? false);
    }

    public function hasFeature(string $feature): bool
    {
        if ($this->features && isset($this->features[$feature])) {
            return (bool) $this->features[$feature];
        }

        if ($this->isDaycare()) {
            return in_array($feature, ['daycare', 'employee', 'finance', 'public_relations']);
        }

        return in_array($feature, ['academic', 'employee', 'finance', 'sarpar', 'counseling', 'public_relations']);
    }

    public function news(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\News::class);
    }

    public function principal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'principal_id');
    }

    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Academic\Models\Student::class);
    }
}

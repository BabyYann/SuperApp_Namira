<?php

namespace App\Modules\Quran\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranGroup extends Model
{
    protected $table = 'quran_groups';

    protected $fillable = [
        'unit_id',
        'academic_year_id',
        'level_id',
        'session_id',
        'name',
        'teacher_id',
        'assistant_teacher_id',
        'room_name',
        'max_quota',
        'is_active',
    ];

    protected $casts = [
        'max_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(QuranLevel::class, 'level_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assistantTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(QuranEnrollment::class, 'group_id');
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(QuranEnrollment::class, 'group_id')
            ->where('status', 'active');
    }
}

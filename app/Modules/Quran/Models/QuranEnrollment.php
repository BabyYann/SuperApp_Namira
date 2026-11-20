<?php

namespace App\Modules\Quran\Models;

use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranEnrollment extends Model
{
    protected $table = 'quran_enrollments';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'unit_id',
        'academic_year_id',
        'student_id',
        'group_id',
        'level_id',
        'status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuranGroup::class, 'group_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(QuranLevel::class, 'level_id');
    }
}

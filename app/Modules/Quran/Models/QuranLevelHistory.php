<?php

namespace App\Modules\Quran\Models;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranLevelHistory extends Model
{
    protected $table = 'quran_level_histories';

    public const TYPE_INITIAL = 'initial';
    public const TYPE_PROMOTED = 'promoted';
    public const TYPE_TRANSFERRED = 'transferred';

    protected $fillable = [
        'unit_id',
        'academic_year_id',
        'semester',
        'student_id',
        'from_level_id',
        'to_level_id',
        'from_group_id',
        'to_group_id',
        'from_teacher_id',
        'to_teacher_id',
        'change_type',
        'changed_at',
        'exam_score',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'changed_at' => 'date',
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

    public function fromLevel(): BelongsTo
    {
        return $this->belongsTo(QuranLevel::class, 'from_level_id');
    }

    public function toLevel(): BelongsTo
    {
        return $this->belongsTo(QuranLevel::class, 'to_level_id');
    }

    public function fromGroup(): BelongsTo
    {
        return $this->belongsTo(QuranGroup::class, 'from_group_id');
    }

    public function toGroup(): BelongsTo
    {
        return $this->belongsTo(QuranGroup::class, 'to_group_id');
    }

    public function fromTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_teacher_id');
    }

    public function toTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_teacher_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

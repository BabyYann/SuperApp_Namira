<?php

namespace App\Modules\Quran\Models;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranAssessment extends Model
{
    protected $table = 'quran_assessments';

    protected $fillable = [
        'unit_id',
        'academic_year_id',
        'semester',
        'student_id',
        'level_id',
        'group_id',
        'teacher_id',
        'scores',
        'teacher_note',
        'updated_by',
    ];

    protected $casts = [
        'scores' => 'array',
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

    public function level(): BelongsTo
    {
        return $this->belongsTo(QuranLevel::class, 'level_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuranGroup::class, 'group_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

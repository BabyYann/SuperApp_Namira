<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Teacher;
use App\Modules\Yayasan\Models\AcademicYear;

class LmsClassroom extends Model
{
    protected $table = 'lms_classrooms';

    protected $fillable = [
        'classroom_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'status',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function materials()
    {
        return $this->hasMany(LmsMaterial::class, 'lms_classroom_id');
    }

    public function assignments()
    {
        return $this->hasMany(LmsAssignment::class, 'lms_classroom_id');
    }

    public function announcements()
    {
        return $this->hasMany(LmsAnnouncement::class, 'lms_classroom_id');
    }
}

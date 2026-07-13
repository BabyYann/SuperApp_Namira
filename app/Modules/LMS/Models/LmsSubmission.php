<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Academic\Models\Student;
use App\Models\User;

class LmsSubmission extends Model
{
    protected $table = 'lms_submissions';

    protected $fillable = [
        'lms_assignment_id',
        'student_id',
        'submission_text',
        'status',
        'submitted_at',
        'grade',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'grade' => 'float',
    ];

    public function assignment()
    {
        return $this->belongsTo(LmsAssignment::class, 'lms_assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function files()
    {
        return $this->hasMany(LmsSubmissionFile::class, 'lms_submission_id');
    }
}

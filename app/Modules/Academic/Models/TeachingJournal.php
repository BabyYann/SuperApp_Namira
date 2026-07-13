<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'teacher_id',
        'class_schedule_id',
        'classroom_id',
        'subject_id',
        'date',
        'start_time',
        'end_time',
        'custom_theme',
        'notes',
        'photo_path',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    
    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    // Many-to-Many with Learning Objectives (TP)
    public function learningObjectives()
    {
        return $this->belongsToMany(LearningObjective::class, 'journal_learning_objectives');
    }

    // One-to-Many with Attendance
    public function attendance()
    {
        return $this->hasMany(JournalAttendance::class);
    }
}

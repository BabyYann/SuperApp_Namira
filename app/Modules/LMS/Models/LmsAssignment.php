<?php

namespace App\Modules\LMS\Models;

use Illuminate\Database\Eloquent\Model;

class LmsAssignment extends Model
{
    protected $table = 'lms_assignments';

    protected $fillable = [
        'lms_classroom_id',
        'title',
        'description',
        'due_date',
        'max_score',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function classroom()
    {
        return $this->belongsTo(LmsClassroom::class, 'lms_classroom_id');
    }

    public function submissions()
    {
        return $this->hasMany(LmsSubmission::class, 'lms_assignment_id');
    }
}

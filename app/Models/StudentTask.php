<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Academic\Models\Student;

class StudentTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'is_completed',
        'completed_at',
        'date',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

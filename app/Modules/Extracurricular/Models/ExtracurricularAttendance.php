<?php

namespace App\Modules\Extracurricular\Models;

use App\Modules\Academic\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularAttendance extends Model
{
    use HasFactory;

    protected $table = 'extracurricular_attendances';

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(ExtracurricularSession::class, 'session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}

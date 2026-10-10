<?php

namespace App\Modules\Extracurricular\Models;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularGrade extends Model
{
    use HasFactory;

    protected $table = 'extracurricular_grades';

    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'academic_year_id',
        'semester',
        'predicate',
        'description',
        'given_by',
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'given_by');
    }
}

<?php

namespace App\Modules\Extracurricular\Models;

use App\Modules\Academic\Models\Student;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularMember extends Model
{
    use HasFactory;

    protected $table = 'extracurricular_members';

    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'academic_year_id',
        'semester',
        'joined_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'joined_date' => 'date',
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

    public function grade()
    {
        return $this->hasOne(ExtracurricularGrade::class, 'student_id', 'student_id')
            ->whereColumn('extracurricular_id', 'extracurricular_members.extracurricular_id')
            ->whereColumn('academic_year_id', 'extracurricular_members.academic_year_id')
            ->whereColumn('semester', 'extracurricular_members.semester');
    }
}

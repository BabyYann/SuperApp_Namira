<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Academic\Models\Student;

class StudentCheckin extends Model
{
    protected $table = 'student_checkins';

    protected $fillable = [
        'student_id',
        'unit_id',
        'academic_year_id',
        'scanned_by',
        'checkin_date',
        'checkin_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'checkin_date' => 'date',
    ];

    // Relations
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scannedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'scanned_by');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Yayasan\Models\SchoolUnit::class, 'unit_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\AcademicYear::class, 'academic_year_id');
    }
}

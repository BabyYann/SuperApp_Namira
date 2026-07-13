<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\AcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassPromotion extends Model
{
    protected $fillable = [
        'student_id',
        'from_classroom_id',
        'to_classroom_id',
        'from_academic_year_id',
        'to_academic_year_id',
        'status',
        'notes',
        'promoted_by',
        'promoted_at',
        'validation_summary',
        'is_rolled_back',
        'rolled_back_by',
        'rolled_back_at',
    ];

    protected $casts = [
        'promoted_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'is_rolled_back' => 'boolean',
    ];

    // Status labels in Indonesian
    public static array $statusLabels = [
        'naik' => 'Naik Kelas',
        'tinggal' => 'Tinggal Kelas',
        'lulus' => 'Lulus',
        'pindah' => 'Pindah Sekolah',
        'keluar' => 'Keluar',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? $this->status;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'from_classroom_id');
    }

    public function toClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'to_classroom_id');
    }

    public function fromAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function promotedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    public function rolledBackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }
}

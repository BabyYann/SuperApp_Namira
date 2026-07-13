<?php

namespace App\Modules\Counseling\Models;

use App\Models\User;
use App\Modules\Academic\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounselingSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'student_id',
        'counselor_id',
        'violation_id',
        'date',
        'time',
        'method',
        'status',
        'notes',
        'follow_up_action',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
    ];

    /**
     * Scope to current unit
     */
    protected static function booted()
    {
        if (session()->has('active_unit_id')) {
            static::addGlobalScope('unit', function (Builder $builder) {
                $builder->where('unit_id', session('active_unit_id'));
            });
        }
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function violation()
    {
        return $this->belongsTo(Violation::class);
    }
}

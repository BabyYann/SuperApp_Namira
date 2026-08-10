<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareGrowthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'measurement_date',
        'weight_kg',
        'height_cm',
        'head_circumference_cm',
        'notes',
        'measured_by',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }

    public function measuredBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'measured_by');
    }
}

<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareDailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date',
        'log_time',
        'category',
        'title',
        'description',
        'amount_ml',
        'portion_eaten',
        'photo',
        'caregiver_id',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }

    public function caregiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'caregiver_id');
    }
}

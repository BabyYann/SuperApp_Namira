<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareDevelopmentalJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'period_month',
        'gross_motor',
        'fine_motor',
        'language_communication',
        'cognitive',
        'socio_emotional',
        'independence',
        'caregiver_summary',
        'status',
        'created_by',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareCaregiverSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'user_id',
        'date',
        'shift_name',
        'room_name',
        'assigned_student_ids',
        'notes',
    ];

    protected $casts = [
        'assigned_student_ids' => 'array',
    ];

    public function unit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class, 'unit_id');
    }

    public function caregiver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}

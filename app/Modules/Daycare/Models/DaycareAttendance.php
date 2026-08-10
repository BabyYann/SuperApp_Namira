<?php

namespace App\Modules\Daycare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaycareAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date',
        'check_in_time',
        'dropped_off_by',
        'check_in_temp',
        'check_in_condition',
        'check_in_notes',
        'check_in_photo',
        'check_out_time',
        'picked_up_by',
        'authorized_pickup_id',
        'check_out_notes',
        'check_out_photo',
        'recorded_by',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class, 'student_id');
    }

    public function authorizedPickup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DaycareAuthorizedPickup::class, 'authorized_pickup_id');
    }

    public function recorder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }
}

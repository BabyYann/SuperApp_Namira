<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPickupRequest extends Model
{
    protected $table = 'student_pickup_requests';

    protected $fillable = [
        'student_id',
        'requested_by',
        'status',
        'latitude',
        'longitude',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'latitude'     => 'float',
        'longitude'    => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Academic\Models\Student::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }
}

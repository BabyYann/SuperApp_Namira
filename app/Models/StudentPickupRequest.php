<?php

namespace App\Models;

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
        'latitude' => 'float',
        'longitude' => 'float',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}

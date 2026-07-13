<?php

namespace App\Modules\Yayasan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'level',
        'logo',
        'email',
        'phone',
        'address',
        'description',
        'work_start_time',
        'work_end_time',
        'late_tolerance_minutes',
        'principal_id',
    ];

    public function news(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\News::class);
    }

    public function principal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'principal_id');
    }
}

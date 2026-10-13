<?php

namespace App\Modules\Employee\Models;

use App\Models\User;
use App\Modules\Yayasan\Models\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'user_id',
        'title',
        'category',
        'activity_date',
        'activity_time',
        'description',
        'photo_path',
        'location_name',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'activity_date' => 'date:Y-m-d',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function setActivityDateAttribute($value)
    {
        $this->attributes['activity_date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}

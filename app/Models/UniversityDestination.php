<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityDestination extends Model
{
    protected $fillable = [
        'unit_id', 'name', 'city', 'country', 'type', 'visit_type',
        'lat', 'lng', 'visit_date', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'is_active' => 'boolean',
        'visit_date' => 'date',
    ];

    public function unit(): BelongsTo {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}

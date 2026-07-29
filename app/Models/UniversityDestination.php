<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityDestination extends Model
{
    protected $fillable = [
        'unit_id', 'name', 'city', 'country', 'type', 'visit_type',
        'lat', 'lng', 'visit_date', 'description', 
        'approval_status', 'rejection_note', 'approved_by', 'approved_at',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'is_active' => 'boolean',
        'visit_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function unit(): BelongsTo {
        return $this->belongsTo(\App\Modules\Yayasan\Models\Unit::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'unit_id', 'name', 'role_or_title', 'quote', 'photo_path', 
        'approval_status', 'rejection_note', 'approved_by', 'approved_at',
        'is_active', 'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
